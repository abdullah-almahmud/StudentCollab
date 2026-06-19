<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';

require_once __DIR__ . '/helpers.php';



$method = $_SERVER['REQUEST_METHOD'];

$action = $_GET['action'] ?? '';

$db = getDb();



function applicationExtras(PDO $db, int $appId): array

{

    $skills = $db->prepare(

        'SELECT s.id, s.name FROM application_skills aps JOIN skills s ON s.id = aps.skill_id WHERE aps.application_id = ?'

    );

    $skills->execute([$appId]);

    $portfolio = $db->prepare(

        'SELECT p.id, p.title, p.project_url FROM application_portfolio ap

         JOIN portfolio_items p ON p.id = ap.portfolio_item_id WHERE ap.application_id = ?'

    );

    $portfolio->execute([$appId]);

    return ['highlight_skills' => $skills->fetchAll(), 'highlight_projects' => $portfolio->fetchAll()];

}



function formatApplication(PDO $db, array $row): array

{

    $row['gig_skills'] = gigSkills($db, (int)$row['gig_id']);

    $row['applicant_initials'] = initials($row['applicant_name']);

    $row['owner_initials'] = initials($row['owner_name']);

    $extras = applicationExtras($db, (int)$row['id']);

    $row['highlight_skills'] = $extras['highlight_skills'];

    $row['highlight_projects'] = $extras['highlight_projects'];

    return $row;

}



if ($method === 'GET' && $action === 'list') {

    $user = requireAuth();

    $tab = $_GET['tab'] ?? 'sent';



    if ($tab === 'received') {

        $stmt = $db->prepare(

            'SELECT a.*, g.title AS gig_title, g.type AS gig_type,

                    u.name AS applicant_name, u.major AS applicant_major, u.avatar_color AS applicant_color,

                    owner.name AS owner_name,

                    c.id AS conversation_id

             FROM applications a

             JOIN gigs g ON g.id = a.gig_id

             JOIN users u ON u.id = a.applicant_id

             JOIN users owner ON owner.id = g.owner_id

             LEFT JOIN conversations c ON c.application_id = a.id

             WHERE g.owner_id = ?

             ORDER BY a.created_at DESC'

        );

        $stmt->execute([(int)$user['id']]);

    } else {

        $stmt = $db->prepare(

            'SELECT a.*, g.title AS gig_title, g.type AS gig_type,

                    u.name AS applicant_name, u.major AS applicant_major, u.avatar_color AS applicant_color,

                    owner.name AS owner_name, owner.id AS owner_id,

                    c.id AS conversation_id

             FROM applications a

             JOIN gigs g ON g.id = a.gig_id

             JOIN users u ON u.id = a.applicant_id

             JOIN users owner ON owner.id = g.owner_id

             LEFT JOIN conversations c ON c.application_id = a.id

             WHERE a.applicant_id = ?

             ORDER BY a.created_at DESC'

        );

        $stmt->execute([(int)$user['id']]);

    }



    $apps = array_map(fn($r) => formatApplication($db, $r), $stmt->fetchAll());

    jsonResponse(['applications' => $apps]);

}



if ($method === 'GET' && $action === 'form_data') {

    $user = requireAuth();

    $gigId = (int)($_GET['gig_id'] ?? 0);



    $skills = userSkills($db, (int)$user['id']);

    $gigSkillRows = gigSkills($db, $gigId);

    $gigSkillIds = array_column($gigSkillRows, 'id');



    $pStmt = $db->prepare('SELECT p.* FROM portfolio_items p WHERE p.user_id = ? ORDER BY p.created_at DESC');

    $pStmt->execute([(int)$user['id']]);

    $portfolio = $pStmt->fetchAll();

    foreach ($portfolio as &$item) {

        $ps = $db->prepare('SELECT s.name FROM portfolio_skills ps JOIN skills s ON s.id = ps.skill_id WHERE ps.portfolio_item_id = ?');

        $ps->execute([(int)$item['id']]);

        $item['skills'] = array_column($ps->fetchAll(), 'name');

    }

    unset($item);



    jsonResponse(['skills' => $skills, 'portfolio' => $portfolio, 'gig_skill_ids' => $gigSkillIds]);

}



if ($method === 'POST' && $action === 'apply') {

    $user = requireAuth();

    $body = getJsonBody();

    $gigId = (int)($body['gig_id'] ?? 0);

    $message = trim($body['message'] ?? '');

    $skillIds = $body['skill_ids'] ?? [];

    $portfolioIds = $body['portfolio_ids'] ?? [];



    if ($gigId <= 0 || $message === '') {

        jsonError('Gig and message are required');

    }



    $gig = $db->prepare('SELECT * FROM gigs WHERE id = ?');

    $gig->execute([$gigId]);

    $gigRow = $gig->fetch();

    if (!$gigRow) {

        jsonError('Gig not found', 404);

    }

    if ((int)$gigRow['owner_id'] === (int)$user['id']) {

        jsonError('You cannot apply to your own gig');

    }



    try {

        $stmt = $db->prepare('INSERT INTO applications (gig_id, applicant_id, message) VALUES (?, ?, ?)');

        $stmt->execute([$gigId, (int)$user['id'], $message]);

        $appId = (int)$db->lastInsertId();



        if (is_array($skillIds)) {

            $ss = $db->prepare('INSERT OR IGNORE INTO application_skills (application_id, skill_id) VALUES (?, ?)');

            foreach ($skillIds as $sid) {

                $ss->execute([$appId, (int)$sid]);

            }

        }

        if (is_array($portfolioIds)) {

            $pp = $db->prepare('INSERT OR IGNORE INTO application_portfolio (application_id, portfolio_item_id) VALUES (?, ?)');

            foreach ($portfolioIds as $pid) {

                $pp->execute([$appId, (int)$pid]);

            }

        }

    } catch (PDOException $e) {

        jsonError('You already applied to this gig', 409);

    }



    jsonResponse(['id' => $appId], 201);

}



if ($method === 'POST' && $action === 'update') {

    $user = requireAuth();

    $body = getJsonBody();

    $appId = (int)($body['id'] ?? 0);

    $status = $body['status'] ?? '';



    if (!in_array($status, ['accepted', 'rejected'], true)) {

        jsonError('Invalid status');

    }



    $stmt = $db->prepare(

        'SELECT a.*, g.owner_id, g.type AS gig_type FROM applications a

         JOIN gigs g ON g.id = a.gig_id WHERE a.id = ?'

    );

    $stmt->execute([$appId]);

    $app = $stmt->fetch();

    if (!$app) {

        jsonError('Application not found', 404);

    }

    if ((int)$app['owner_id'] !== (int)$user['id']) {

        jsonError('Not authorized', 403);

    }



    $db->prepare('UPDATE applications SET status = ? WHERE id = ?')->execute([$status, $appId]);



    $conversationId = null;

    if ($status === 'accepted') {

        if ($app['gig_type'] === 'freelance') {

            $db->prepare("UPDATE gigs SET status = 'filled' WHERE id = ?")->execute([(int)$app['gig_id']]);

        }



        $existing = $db->prepare('SELECT id FROM conversations WHERE application_id = ?');

        $existing->execute([$appId]);

        $conversationId = $existing->fetchColumn();

        if (!$conversationId) {

            $db->prepare('INSERT INTO conversations (application_id) VALUES (?)')->execute([$appId]);

            $conversationId = (int)$db->lastInsertId();

            $db->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)')

                ->execute([$conversationId, (int)$user['id'], 'Welcome to the team! Let us get started on this project.']);



            $db->prepare('INSERT OR IGNORE INTO core_members (user_id, member_id) VALUES (?, ?)')

                ->execute([(int)$user['id'], (int)$app['applicant_id']]);

            $db->prepare('INSERT OR IGNORE INTO core_members (user_id, member_id) VALUES (?, ?)')

                ->execute([(int)$app['applicant_id'], (int)$user['id']]);

        } else {

            $conversationId = (int)$conversationId;

        }

    }



    jsonResponse(['ok' => true, 'conversation_id' => $conversationId]);

}



jsonError('Unknown action', 404);


