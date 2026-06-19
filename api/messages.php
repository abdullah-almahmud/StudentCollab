<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db = getDb();

if ($method === 'GET' && $action === 'list') {
    $user = requireAuth();

    $stmt = $db->prepare(
        'SELECT c.id AS conversation_id, c.application_id,
                g.title AS gig_title, g.owner_id,
                a.applicant_id, a.status AS app_status,
                CASE WHEN g.owner_id = ? THEN u.name ELSE owner.name END AS partner_name,
                CASE WHEN g.owner_id = ? THEN u.major ELSE owner.major END AS partner_major,
                CASE WHEN g.owner_id = ? THEN u.avatar_color ELSE owner.avatar_color END AS partner_color,
                CASE WHEN g.owner_id = ? THEN "host" ELSE "teammate" END AS conv_type,
                (SELECT body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message,
                (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message_at
         FROM conversations c
         JOIN applications a ON a.id = c.application_id
         JOIN gigs g ON g.id = a.gig_id
         JOIN users u ON u.id = a.applicant_id
         JOIN users owner ON owner.id = g.owner_id
         WHERE g.owner_id = ? OR a.applicant_id = ?
         ORDER BY COALESCE(last_message_at, c.created_at) DESC'
    );
    $uid = (int)$user['id'];
    $stmt->execute([$uid, $uid, $uid, $uid, $uid, $uid]);
    $conversations = $stmt->fetchAll();

    foreach ($conversations as &$c) {
        $c['partner_initials'] = initials($c['partner_name']);
        $c['unread'] = 0;
    }
    unset($c);

    jsonResponse(['conversations' => $conversations]);
}

if ($method === 'GET' && $action === 'messages') {
    $user = requireAuth();
    $convId = (int)($_GET['conversation_id'] ?? 0);

    $access = $db->prepare(
        'SELECT c.id FROM conversations c
         JOIN applications a ON a.id = c.application_id
         JOIN gigs g ON g.id = a.gig_id
         WHERE c.id = ? AND (g.owner_id = ? OR a.applicant_id = ?)'
    );
    $access->execute([$convId, (int)$user['id'], (int)$user['id']]);
    if (!$access->fetch()) {
        jsonError('Conversation not found', 404);
    }

    $stmt = $db->prepare(
        'SELECT m.*, u.name AS sender_name FROM messages m
         JOIN users u ON u.id = m.sender_id
         WHERE m.conversation_id = ? ORDER BY m.created_at ASC'
    );
    $stmt->execute([$convId]);
    $messages = $stmt->fetchAll();

    $meta = $db->prepare(
        'SELECT g.title AS gig_title, g.owner_id, a.applicant_id,
                CASE WHEN g.owner_id = ? THEN u.name ELSE owner.name END AS partner_name,
                CASE WHEN g.owner_id = ? THEN "host" ELSE "teammate" END AS conv_type
         FROM conversations c
         JOIN applications a ON a.id = c.application_id
         JOIN gigs g ON g.id = a.gig_id
         JOIN users u ON u.id = a.applicant_id
         JOIN users owner ON owner.id = g.owner_id
         WHERE c.id = ?'
    );
    $meta->execute([(int)$user['id'], (int)$user['id'], $convId]);
    $info = $meta->fetch();

    jsonResponse(['messages' => $messages, 'info' => $info]);
}

if ($method === 'POST' && $action === 'send') {
    $user = requireAuth();
    $body = getJsonBody();
    $convId = (int)($body['conversation_id'] ?? 0);
    $text = trim($body['body'] ?? '');

    if ($convId <= 0 || $text === '') {
        jsonError('Message required');
    }

    $access = $db->prepare(
        'SELECT c.id FROM conversations c
         JOIN applications a ON a.id = c.application_id
         JOIN gigs g ON g.id = a.gig_id
         WHERE c.id = ? AND (g.owner_id = ? OR a.applicant_id = ?)'
    );
    $access->execute([$convId, (int)$user['id'], (int)$user['id']]);
    if (!$access->fetch()) {
        jsonError('Conversation not found', 404);
    }

    $stmt = $db->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)');
    $stmt->execute([$convId, (int)$user['id'], $text]);

    jsonResponse(['id' => (int)$db->lastInsertId()], 201);
}

if ($method === 'POST' && $action === 'start') {
    $user = requireAuth();
    $body = getJsonBody();
    $targetUserId = (int)($body['user_id'] ?? 0);

    if ($targetUserId <= 0 || $targetUserId === (int)$user['id']) {
        jsonError('Invalid user');
    }

    // Find existing accepted application conversation between users
    $stmt = $db->prepare(
        'SELECT c.id FROM conversations c
         JOIN applications a ON a.id = c.application_id
         JOIN gigs g ON g.id = a.gig_id
         WHERE a.status = "accepted"
         AND ((g.owner_id = ? AND a.applicant_id = ?) OR (g.owner_id = ? AND a.applicant_id = ?))
         LIMIT 1'
    );
    $stmt->execute([(int)$user['id'], $targetUserId, $targetUserId, (int)$user['id']]);
    $convId = $stmt->fetchColumn();

    if ($convId) {
        jsonResponse(['conversation_id' => (int)$convId]);
    }

    jsonError('Start a conversation by accepting an application first', 400);
}

jsonError('Unknown action', 404);
