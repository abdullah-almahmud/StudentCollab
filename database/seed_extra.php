<?php
declare(strict_types=1);

function seedExtraData(PDO $pdo): void
{
    $gigCount = (int)$pdo->query('SELECT COUNT(*) FROM gigs')->fetchColumn();
    if ($gigCount >= 25) {
        return;
    }

    enrichUsers($pdo);
    enrichSkills($pdo);
    addResearchTopics($pdo);
    addMoreGigs($pdo);
    enrichPortfolio($pdo);
    addCoreMembers($pdo);
}

function enrichUsers(PDO $pdo): void
{
    $links = [
        1 => ['https://github.com/alicechen-dev', 'https://linkedin.com/in/alicechen', ''],
        2 => ['https://github.com/bobrahman', 'https://linkedin.com/in/bobrahman', 'https://bob.dev'],
        3 => ['https://github.com/rezwanislam', 'https://linkedin.com/in/rezwanislam', 'https://rezwan.dev'],
        4 => ['https://github.com/tanzila-design', 'https://linkedin.com/in/tanzilar', ''],
        5 => ['https://github.com/karimml', '', ''],
        6 => ['https://github.com/sadiawrites', 'https://linkedin.com/in/sadiaahmed', ''],
    ];
    $stmt = $pdo->prepare('UPDATE users SET github_url = ?, linkedin_url = ?, website_url = ? WHERE id = ?');
    foreach ($links as $id => $l) {
        $stmt->execute([$l[0], $l[1], $l[2], $id]);
    }
}

function enrichSkills(PDO $pdo): void
{
    $meta = [
        'React' => ['Hooks, Redux, Next.js, Vite', 'Start with components → state → hooks → full apps'],
        'TypeScript' => ['Generics, Interfaces, TSConfig', 'Learn JS basics → add types → build typed APIs'],
        'Python' => ['Pandas, NumPy, Flask, Django', 'Syntax → data structures → libraries → projects'],
        'Node.js' => ['Express, REST APIs, Socket.io', 'JS fundamentals → Express → database integration'],
        'UI/UX Design' => ['Figma, Wireframes, User flows', 'Research → wireframe → prototype → test'],
        'Figma' => ['Auto-layout, Components, Prototyping', 'Basics → components → design systems'],
        'Machine Learning' => ['Scikit-learn, TensorFlow, NLP', 'Stats → Python → sklearn → deep learning'],
        'Data Analysis' => ['Excel, Pandas, Matplotlib', 'Spreadsheets → Python → visualization → insights'],
        'Research Methods' => ['Surveys, SPSS, Qualitative coding', 'Literature review → design → analysis → write-up'],
    ];
    $stmt = $pdo->prepare('UPDATE skills SET tools = ?, learning_path = ? WHERE name = ?');
    foreach ($meta as $name => $m) {
        $stmt->execute([$m[0], $m[1], $name]);
    }
}

function addResearchTopics(PDO $pdo): void
{
    if ((int)$pdo->query('SELECT COUNT(*) FROM research_topics')->fetchColumn() > 0) {
        return;
    }
    $topics = [
        ['Campus Mental Health Survey Analysis', 'Research Methods', 'Statistical analysis of student wellbeing surveys across departments.', 12],
        ['NLP for Course Review Sentiment', 'Machine Learning', 'Building classifiers on student feedback text data.', 9],
        ['Accessible UI Patterns for Mobile Web', 'UI/UX Design', 'WCAG-compliant design patterns for campus apps.', 8],
        ['Blockchain in Academic Credentials', 'Database Design', 'Exploring decentralized verification for transcripts.', 6],
        ['Social Media Impact on Study Habits', 'Data Analysis', 'Correlational study using survey and usage data.', 11],
        ['React Performance on Low-End Devices', 'React', 'Benchmarking render performance for campus kiosks.', 7],
        ['Brand Identity for Student Startups', 'Branding', 'Case studies of successful campus venture branding.', 5],
        ['Video Content for STEM Outreach', 'Video Editing', 'Creating engaging reels for science communication.', 4],
    ];
    $stmt = $pdo->prepare('INSERT INTO research_topics (title, skill_id, description, popularity) SELECT ?, id, ?, ? FROM skills WHERE name = ?');
    foreach ($topics as $t) {
        $stmt->execute([$t[0], $t[2], $t[3], $t[1]]);
    }
}

function addMoreGigs(PDO $pdo): void
{
    $extra = [
        [1, 'React Native Campus Events App', 'Mobile app for listing and RSVPing to campus events. React Native experience preferred.', 'collaboration', '', '8 weeks', '3 people', 'open', 'React', 'Campus app development'],
        [1, 'TypeScript API Client Library', 'Build a typed SDK for our lab equipment API.', 'freelance', '$350', '3 weeks', '', 'open', 'TypeScript', ''],
        [2, 'React Component Library for Design System', 'Create reusable React components from Figma specs.', 'collaboration', '', '5 weeks', '2 people', 'open', 'React', 'Design system'],
        [2, 'Figma Prototype for E-Learning Platform', 'High-fidelity prototype with interactive flows.', 'freelance', '$280', '2 weeks', '', 'open', 'Figma', ''],
        [3, 'Full-Stack React + Node Portfolio', 'Need help deploying React portfolio with Node backend.', 'freelance', '$300', '2 weeks', '', 'open', 'React', ''],
        [3, 'Python Data Pipeline for Lab', 'ETL pipeline using Python and pandas for experiment logs.', 'collaboration', '', '4 weeks', '2 people', 'open', 'Python', 'Lab data pipeline'],
        [5, 'Machine Learning Research Paper Support', 'Assist with ML methodology section for IEEE submission.', 'collaboration', '', '6 weeks', '1 person', 'open', 'Machine Learning', 'IEEE ML paper'],
        [5, 'Data Analysis for Psychology Thesis', 'SPSS and Python analysis for thesis chapter 4.', 'freelance', '$220', '3 weeks', '', 'open', 'Data Analysis', 'Psychology thesis data'],
        [5, 'Research Methods Literature Review', 'Help compile and synthesize 30+ papers for review chapter.', 'freelance', '$180', '2 weeks', '', 'open', 'Research Methods', 'Literature review'],
        [4, 'UI/UX Audit for Student Portal', 'Heuristic evaluation and redesign recommendations.', 'freelance', '$400', '3 weeks', '', 'open', 'UI/UX Design', ''],
        [6, 'Content Writing for Research Blog', 'Write accessible summaries of campus research papers.', 'freelance', '$150', '2 weeks', '', 'open', 'Content Writing', 'Research communication'],
        [2, 'Node.js Backend for Hackathon Project', 'REST API with authentication for hackathon MVP.', 'collaboration', '', '3 weeks', '2 people', 'open', 'Node.js', 'Hackathon API'],
        [1, 'Database Design for Alumni Network', 'Schema design and SQLite implementation for alumni DB.', 'collaboration', '', '4 weeks', '2 people', 'open', 'Database Design', 'Alumni database'],
        [4, 'Graphic Design for Research Poster', 'Design A0 research poster for conference presentation.', 'freelance', '$120', '1 week', '', 'open', 'Graphic Design', ''],
        [6, 'Social Media Campaign for Tech Club', 'Plan and create content calendar for semester events.', 'freelance', '$200', '4 weeks', '', 'open', 'Social Media', ''],
        [3, 'Video Editing for Research Demo', 'Edit 5-minute research demo video with captions.', 'freelance', '$160', '1 week', '', 'open', 'Video Editing', 'Research demo video'],
    ];

    $gigStmt = $pdo->prepare('INSERT INTO gigs (owner_id, title, description, type, budget, duration, team_size, status, topic) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $gsStmt = $pdo->prepare('INSERT OR IGNORE INTO gig_skills (gig_id, skill_id) SELECT ?, id FROM skills WHERE name = ?');

    foreach ($extra as $g) {
        $gigStmt->execute([$g[0], $g[1], $g[2], $g[3], $g[4], $g[5], $g[6], $g[7], $g[9] ?? '']);
        $gid = (int)$pdo->lastInsertId();
        $gsStmt->execute([$gid, $g[8]]);
    }
}

function enrichPortfolio(PDO $pdo): void
{
    $urls = [
        ['Rezwan Islam', 'Campus Dining Application', 'https://github.com/rezwanislam/dining-app'],
        ['Rezwan Islam', 'StudentCollab Platform Redesign', 'https://github.com/rezwanislam/studentcollab'],
        ['Bob Rahman', 'E-Commerce Product Page', 'https://github.com/bobrahman/ecommerce-ui'],
        ['Karim Hossain', 'Sentiment Classifier', 'https://github.com/karimml/sentiment-nlp'],
    ];
    $stmt = $pdo->prepare(
        'UPDATE portfolio_items SET project_url = ?
         WHERE user_id = (SELECT id FROM users WHERE name = ?) AND title = ?'
    );
    foreach ($urls as $u) {
        $stmt->execute([$u[2], $u[0], $u[1]]);
    }
}

function addCoreMembers(PDO $pdo): void
{
    if ((int)$pdo->query('SELECT COUNT(*) FROM core_members')->fetchColumn() > 0) {
        return;
    }
    $pairs = [[1, 2], [1, 3], [3, 2]];
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO core_members (user_id, member_id) VALUES (?, ?)');
    foreach ($pairs as $p) {
        $stmt->execute($p);
        $stmt->execute([$p[1], $p[0]]);
    }
}
