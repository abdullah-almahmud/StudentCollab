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
    $type = trim($_GET['type'] ?? '');
    $skill = trim($_GET['skill'] ?? '');

    $sql = 'SELECT g.*, u.name AS owner_name, u.avatar_color AS owner_color
            FROM gigs g JOIN users u ON u.id = g.owner_id WHERE g.status != "closed"';
    $params = [];

    if ($type === 'freelance' || $type === 'collaboration') {
        $sql .= ' AND g.type = ?';
        $params[] = $type;
    }
    if ($search !== '') {
        $sql .= ' AND (g.title LIKE ? OR g.description LIKE ? OR g.topic LIKE ?
                    OR EXISTS (SELECT 1 FROM gig_skills gs JOIN skills s ON s.id = gs.skill_id
                               WHERE gs.gig_id = g.id AND (s.name LIKE ? OR s.name = ?)))';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = $search;
    }
    if ($skill !== '') {
        $sql .= ' AND EXISTS (SELECT 1 FROM gig_skills gs JOIN skills s ON s.id = gs.skill_id WHERE gs.gig_id = g.id AND (s.name LIKE ? OR s.name = ?))';
        $params[] = '%' . $skill . '%';
        $params[] = $skill;
    }

    $sql .= ' ORDER BY g.created_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $gigs = $stmt->fetchAll();

    foreach ($gigs as &$g) {
        $g['skills'] = gigSkills($db, (int)$g['id']);
        $g['owner_initials'] = initials($g['owner_name']);
    }
    unset($g);

    jsonResponse(['gigs' => $gigs]);
}

if ($method === 'GET' && $action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare('SELECT g.*, u.name AS owner_name, u.avatar_color AS owner_color FROM gigs g JOIN users u ON u.id = g.owner_id WHERE g.id = ?');
    $stmt->execute([$id]);
    $gig = $stmt->fetch();
    if (!$gig) {
        jsonError('Gig not found', 404);
    }
    $gig['skills'] = gigSkills($db, $id);
    $gig['owner_initials'] = initials($gig['owner_name']);
    jsonResponse(['gig' => $gig]);
}

if ($method === 'POST' && $action === 'create') {
    $user = requireAuth();
    $body = getJsonBody();

    $title = trim($body['title'] ?? '');
    $description = trim($body['description'] ?? '');
    $type = $body['type'] ?? 'freelance';
    $budget = trim($body['budget'] ?? '');
    $duration = trim($body['duration'] ?? '');
    $teamSize = trim($body['team_size'] ?? '');
    $skillIds = $body['skill_ids'] ?? [];

    if ($title === '' || $description === '') {
        jsonError('Title and description are required');
    }
    if (!in_array($type, ['freelance', 'collaboration'], true)) {
        jsonError('Invalid gig type');
    }

    $stmt = $db->prepare('INSERT INTO gigs (owner_id, title, description, type, budget, duration, team_size) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([(int)$user['id'], $title, $description, $type, $budget, $duration, $teamSize]);
    $gigId = (int)$db->lastInsertId();

    if (is_array($skillIds)) {
        $gs = $db->prepare('INSERT OR IGNORE INTO gig_skills (gig_id, skill_id) VALUES (?, ?)');
        foreach ($skillIds as $sid) {
            $gs->execute([$gigId, (int)$sid]);
        }
    }

    jsonResponse(['id' => $gigId], 201);
}

jsonError('Unknown action', 404);
