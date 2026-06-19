<?php
declare(strict_types=1);

function jsonResponse(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonError(string $message, int $code = 400): never
{
    jsonResponse(['error' => $message], $code);
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function requireAuth(): array
{
    if (empty($_SESSION['user_id'])) {
        jsonError('Authentication required', 401);
    }
    return currentUser();
}

function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $db = getDb();
    $stmt = $db->prepare('SELECT id, name, email, major, bio, avatar_color, github_url, linkedin_url, website_url, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function userSkills(PDO $db, int $userId): array
{
    $stmt = $db->prepare(
        'SELECT s.id, s.name, s.category FROM skills s
         JOIN user_skills us ON us.skill_id = s.id
         WHERE us.user_id = ? ORDER BY s.name'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function gigSkills(PDO $db, int $gigId): array
{
    $stmt = $db->prepare(
        'SELECT s.id, s.name FROM skills s
         JOIN gig_skills gs ON gs.skill_id = s.id
         WHERE gs.gig_id = ? ORDER BY s.name'
    );
    $stmt->execute([$gigId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach ($parts as $p) {
        if ($p !== '') {
            $letters .= strtoupper($p[0]);
        }
    }
    return substr($letters, 0, 2) ?: '?';
}
