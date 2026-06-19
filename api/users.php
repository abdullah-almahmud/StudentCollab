<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';

require_once __DIR__ . '/helpers.php';



$method = $_SERVER['REQUEST_METHOD'];

$action = $_GET['action'] ?? '';

$db = getDb();



if ($method === 'GET' && $action === 'list') {

    $search = trim($_GET['search'] ?? '');

    $skill = trim($_GET['skill'] ?? '');



    $sql = 'SELECT u.id, u.name, u.major, u.bio, u.avatar_color FROM users u WHERE 1=1';

    $params = [];



    if ($search !== '') {

        $sql .= ' AND (u.name LIKE ? OR u.major LIKE ? OR u.bio LIKE ?)';

        $params[] = '%' . $search . '%';

        $params[] = '%' . $search . '%';

        $params[] = '%' . $search . '%';

    }

    if ($skill !== '') {

        $sql .= ' AND EXISTS (SELECT 1 FROM user_skills us JOIN skills s ON s.id = us.skill_id WHERE us.user_id = u.id AND s.name LIKE ?)';

        $params[] = '%' . $skill . '%';

    }



    $sql .= ' ORDER BY u.name';

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    $users = $stmt->fetchAll();



    foreach ($users as &$u) {

        $u['skills'] = userSkills($db, (int)$u['id']);

        $u['initials'] = initials($u['name']);

    }

    unset($u);



    jsonResponse(['users' => $users]);

}



if ($method === 'GET' && $action === 'profile') {

    $id = (int)($_GET['id'] ?? 0);

    if ($id === 0) {

        $user = currentUser();

        if (!$user) {

            jsonError('Authentication required', 401);

        }

        $id = (int)$user['id'];

    }



    $stmt = $db->prepare(

        'SELECT id, name, email, major, bio, avatar_color, github_url, linkedin_url, website_url, created_at FROM users WHERE id = ?'

    );

    $stmt->execute([$id]);

    $profile = $stmt->fetch();

    if (!$profile) {

        jsonError('User not found', 404);

    }



    $profile['skills'] = userSkills($db, $id);

    $profile['initials'] = initials($profile['name']);



    $pStmt = $db->prepare('SELECT * FROM portfolio_items WHERE user_id = ? ORDER BY created_at DESC');

    $pStmt->execute([$id]);

    $items = $pStmt->fetchAll();

    foreach ($items as &$item) {

        $ps = $db->prepare('SELECT s.name FROM portfolio_skills ps JOIN skills s ON s.id = ps.skill_id WHERE ps.portfolio_item_id = ?');

        $ps->execute([(int)$item['id']]);

        $item['skills'] = array_column($ps->fetchAll(), 'name');

    }

    unset($item);

    $profile['portfolio'] = $items;



    $cm = $db->prepare(

        'SELECT u.id, u.name, u.major, u.avatar_color FROM core_members cm

         JOIN users u ON u.id = cm.member_id WHERE cm.user_id = ? ORDER BY u.name'

    );

    $cm->execute([$id]);

    $members = $cm->fetchAll();

    foreach ($members as &$m) {

        $m['initials'] = initials($m['name']);

    }

    unset($m);

    $profile['core_members'] = $members;



    $me = currentUser();

    $profile['is_own'] = $me && (int)$me['id'] === $id;



    if ($me && !$profile['is_own']) {

        $req = $db->prepare(

            'SELECT status FROM core_member_requests WHERE requester_id = ? AND recipient_id = ? ORDER BY id DESC LIMIT 1'

        );

        $req->execute([(int)$me['id'], $id]);

        $profile['core_request_status'] = $req->fetchColumn() ?: null;

    }



    if ($profile['is_own']) {

        $pending = $db->prepare(

            'SELECT r.id, u.id AS user_id, u.name, u.major, u.avatar_color

             FROM core_member_requests r JOIN users u ON u.id = r.requester_id

             WHERE r.recipient_id = ? AND r.status = "pending"'

        );

        $pending->execute([$id]);

        $requests = $pending->fetchAll();

        foreach ($requests as &$r) {

            $r['initials'] = initials($r['name']);

        }

        unset($r);

        $profile['pending_core_requests'] = $requests;

    }



    jsonResponse(['profile' => $profile]);

}



if ($method === 'POST' && $action === 'update') {

    $user = requireAuth();

    $body = getJsonBody();



    $stmt = $db->prepare(

        'UPDATE users SET name = ?, major = ?, bio = ?, github_url = ?, linkedin_url = ?, website_url = ? WHERE id = ?'

    );

    $stmt->execute([

        trim($body['name'] ?? $user['name']),

        trim($body['major'] ?? ''),

        trim($body['bio'] ?? ''),

        trim($body['github_url'] ?? ''),

        trim($body['linkedin_url'] ?? ''),

        trim($body['website_url'] ?? ''),

        (int)$user['id'],

    ]);



    jsonResponse(['ok' => true]);

}



if ($method === 'POST' && $action === 'core_request') {

    $user = requireAuth();

    $body = getJsonBody();

    $targetId = (int)($body['user_id'] ?? 0);



    if ($targetId <= 0 || $targetId === (int)$user['id']) {

        jsonError('Invalid user');

    }



    try {

        $db->prepare('INSERT INTO core_member_requests (requester_id, recipient_id) VALUES (?, ?)')

            ->execute([(int)$user['id'], $targetId]);

    } catch (PDOException) {

        jsonError('Request already sent', 409);

    }



    jsonResponse(['ok' => true], 201);

}



if ($method === 'POST' && $action === 'core_respond') {

    $user = requireAuth();

    $body = getJsonBody();

    $requestId = (int)($body['request_id'] ?? 0);

    $accept = ($body['accept'] ?? false) === true;



    $req = $db->prepare('SELECT * FROM core_member_requests WHERE id = ? AND recipient_id = ?');

    $req->execute([$requestId, (int)$user['id']]);

    $row = $req->fetch();

    if (!$row) {

        jsonError('Request not found', 404);

    }



    $status = $accept ? 'accepted' : 'rejected';

    $db->prepare('UPDATE core_member_requests SET status = ? WHERE id = ?')->execute([$status, $requestId]);



    if ($accept) {

        $db->prepare('INSERT OR IGNORE INTO core_members (user_id, member_id) VALUES (?, ?)')

            ->execute([(int)$row['requester_id'], (int)$row['recipient_id']]);

        $db->prepare('INSERT OR IGNORE INTO core_members (user_id, member_id) VALUES (?, ?)')

            ->execute([(int)$row['recipient_id'], (int)$row['requester_id']]);

    }



    jsonResponse(['ok' => true]);

}



if ($method === 'POST' && $action === 'portfolio') {

    $user = requireAuth();

    $body = getJsonBody();

    $title = trim($body['title'] ?? '');

    $description = trim($body['description'] ?? '');

    $status = $body['status'] ?? 'completed';

    $projectUrl = trim($body['project_url'] ?? '');

    $skillIds = $body['skill_ids'] ?? [];



    if ($title === '' || $description === '') {

        jsonError('Title and description are required');

    }



    $stmt = $db->prepare('INSERT INTO portfolio_items (user_id, title, description, status, project_url) VALUES (?, ?, ?, ?, ?)');

    $stmt->execute([(int)$user['id'], $title, $description, $status === 'in_progress' ? 'in_progress' : 'completed', $projectUrl]);

    $itemId = (int)$db->lastInsertId();



    if (is_array($skillIds)) {

        $ps = $db->prepare('INSERT OR IGNORE INTO portfolio_skills (portfolio_item_id, skill_id) VALUES (?, ?)');

        foreach ($skillIds as $sid) {

            $ps->execute([$itemId, (int)$sid]);

        }

    }



    jsonResponse(['id' => $itemId], 201);

}



if ($method === 'DELETE' && $action === 'portfolio') {

    $user = requireAuth();

    $id = (int)($_GET['id'] ?? 0);

    $stmt = $db->prepare('DELETE FROM portfolio_items WHERE id = ? AND user_id = ?');

    $stmt->execute([$id, (int)$user['id']]);

    jsonResponse(['ok' => true]);

}



jsonError('Unknown action', 404);


