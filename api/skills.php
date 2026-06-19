<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';

require_once __DIR__ . '/helpers.php';



$method = $_SERVER['REQUEST_METHOD'];

$action = $_GET['action'] ?? '';

$db = getDb();



if ($method === 'GET' && $action === 'list') {

    $category = trim($_GET['category'] ?? '');

    $search = trim($_GET['search'] ?? '');



    $sql = 'SELECT s.*,

            (SELECT COUNT(DISTINCT gs.gig_id) FROM gig_skills gs JOIN gigs g ON g.id = gs.gig_id WHERE gs.skill_id = s.id AND g.status != "closed") AS gig_count,

            (SELECT COUNT(DISTINCT us.user_id) FROM user_skills us WHERE us.skill_id = s.id) AS expert_count,

            (SELECT COUNT(*) FROM applications a JOIN application_skills aps ON aps.application_id = a.id WHERE aps.skill_id = s.id) AS application_count

            FROM skills s WHERE 1=1';

    $params = [];



    if ($category !== '' && $category !== 'All') {

        $sql .= ' AND s.category = ?';

        $params[] = $category === 'Data' ? 'Data' : $category;

    }

    if ($search !== '') {

        $sql .= ' AND (s.name LIKE ? OR s.description LIKE ? OR s.category LIKE ? OR s.tools LIKE ?)';

        $params[] = '%' . $search . '%';

        $params[] = '%' . $search . '%';

        $params[] = '%' . $search . '%';

        $params[] = '%' . $search . '%';

    }



    $sql .= ' ORDER BY gig_count DESC, s.name';

    $stmt = $db->prepare($sql);

    $stmt->execute($params);

    $skills = $stmt->fetchAll();



    $stats = [

        'skills' => (int)$db->query('SELECT COUNT(*) FROM skills')->fetchColumn(),

        'students' => (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),

        'gigs' => (int)$db->query("SELECT COUNT(*) FROM gigs WHERE status = 'open'")->fetchColumn(),

        'applications' => (int)$db->query('SELECT COUNT(*) FROM applications')->fetchColumn(),

        'research_topics' => (int)$db->query('SELECT COUNT(*) FROM research_topics')->fetchColumn(),

    ];



    jsonResponse(['skills' => $skills, 'stats' => $stats]);

}



if ($method === 'GET' && $action === 'analytics') {

    $trending = $db->query(

        'SELECT s.name, s.icon, s.accent_color, s.category,

                COUNT(DISTINCT gs.gig_id) AS gig_count,

                COUNT(DISTINCT us.user_id) AS expert_count

         FROM skills s

         LEFT JOIN gig_skills gs ON gs.skill_id = s.id

         LEFT JOIN user_skills us ON us.skill_id = s.id

         GROUP BY s.id

         ORDER BY gig_count DESC, expert_count DESC

         LIMIT 8'

    )->fetchAll();



    $popularProjects = $db->query(

        "SELECT g.title, g.type, g.topic, u.name AS host_name,

                GROUP_CONCAT(s.name) AS skills

         FROM gigs g

         JOIN users u ON u.id = g.owner_id

         LEFT JOIN gig_skills gs ON gs.gig_id = g.id

         LEFT JOIN skills s ON s.id = gs.skill_id

         WHERE g.status = 'open'

         GROUP BY g.id

         ORDER BY g.created_at DESC

         LIMIT 6"

    )->fetchAll();



    $researchTopics = $db->query(

        'SELECT rt.title, rt.description, rt.popularity, s.name AS skill_name, s.accent_color

         FROM research_topics rt

         LEFT JOIN skills s ON s.id = rt.skill_id

         ORDER BY rt.popularity DESC

         LIMIT 8'

    )->fetchAll();



    $categoryBreakdown = $db->query(

        "SELECT s.category, COUNT(DISTINCT gs.gig_id) AS gigs, COUNT(DISTINCT us.user_id) AS experts

         FROM skills s

         LEFT JOIN gig_skills gs ON gs.skill_id = s.id

         LEFT JOIN user_skills us ON us.skill_id = s.id

         GROUP BY s.category

         ORDER BY gigs DESC"

    )->fetchAll();



    jsonResponse([

        'trending' => $trending,

        'popular_projects' => $popularProjects,

        'research_topics' => $researchTopics,

        'category_breakdown' => $categoryBreakdown,

    ]);

}



if ($method === 'GET' && $action === 'all') {

    $skills = $db->query('SELECT id, name, category FROM skills ORDER BY name')->fetchAll();

    jsonResponse(['skills' => $skills]);

}



jsonError('Unknown action', 404);


