<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';
getDb();
require_once __DIR__ . '/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'me') {
    $user = currentUser();
    if (!$user) {
        jsonResponse(['user' => null]);
    }
    $db = getDb();
    $user['skills'] = userSkills($db, (int)$user['id']);
    $user['initials'] = initials($user['name']);
    jsonResponse(['user' => $user]);
}

if ($method === 'POST' && $action === 'register') {
    $body = getJsonBody();
    $name = trim($body['name'] ?? '');
    $email = trim(strtolower($body['email'] ?? ''));
    $password = $body['password'] ?? '';
    $major = trim($body['major'] ?? '');
    $bio = trim($body['bio'] ?? '');
    $skillIds = $body['skill_ids'] ?? [];

    if ($name === '' || $email === '' || strlen($password) < 6) {
        jsonError('Name, email, and password (6+ chars) are required');
    }

    $db = getDb();
    $exists = $db->prepare('SELECT id FROM users WHERE email = ?');
    $exists->execute([$email]);
    if ($exists->fetch()) {
        jsonError('Email already registered', 409);
    }

    $colors = ['#10b981', '#3b82f6', '#ec4899', '#f59e0b', '#818cf8', '#38bdf8'];
    $color = $colors[array_rand($colors)];

    $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, major, bio, avatar_color) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $major, $bio, $color]);
    $userId = (int)$db->lastInsertId();

    if (is_array($skillIds)) {
        $us = $db->prepare('INSERT OR IGNORE INTO user_skills (user_id, skill_id) VALUES (?, ?)');
        foreach ($skillIds as $sid) {
            $us->execute([$userId, (int)$sid]);
        }
    }

    $_SESSION['user_id'] = $userId;
    $user = currentUser();
    $user['skills'] = userSkills($db, $userId);
    $user['initials'] = initials($user['name']);
    jsonResponse(['user' => $user], 201);
}

if ($method === 'POST' && $action === 'login') {
    $body = getJsonBody();
    $email = trim(strtolower($body['email'] ?? ''));
    $password = $body['password'] ?? '';

    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        jsonError('Invalid email or password', 401);
    }

    $_SESSION['user_id'] = (int)$row['id'];
    $user = currentUser();
    $user['skills'] = userSkills($db, (int)$row['id']);
    $user['initials'] = initials($user['name']);
    jsonResponse(['user' => $user]);
}

if ($method === 'POST' && $action === 'logout') {
    session_destroy();
    jsonResponse(['ok' => true]);
}

jsonError('Unknown action', 404);
