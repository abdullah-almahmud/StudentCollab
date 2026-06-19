<?php
declare(strict_types=1);

function seedDatabase(PDO $pdo): void
{
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $password = password_hash('demo123', PASSWORD_DEFAULT);

    $skills = [
        ['React', 'Development', '⚛️', '#61dafb', 'Build fast, interactive UIs with the most in-demand frontend library on campus.'],
        ['TypeScript', 'Development', '🔷', '#3178c6', 'Type-safe JavaScript for larger codebases.'],
        ['Python', 'Development', '🐍', '#ffd43b', 'From scripting to machine learning.'],
        ['Node.js', 'Development', '🟢', '#68a063', 'Server-side JavaScript for APIs and full-stack apps.'],
        ['Database Design', 'Development', '🗄️', '#6366f1', 'Design schemas and write optimized queries.'],
        ['UI/UX Design', 'Design', '🎨', '#ec4899', 'User research, wireframes, and polished interfaces.'],
        ['Figma', 'Design', '🖌️', '#a259ff', 'Collaborative design from wireframes to prototypes.'],
        ['Graphic Design', 'Design', '🖼️', '#fb923c', 'Logos, posters, and brand identity packages.'],
        ['Branding', 'Design', '✨', '#eab308', 'Build memorable identity for new ventures.'],
        ['Data Analysis', 'Data', '📊', '#10b981', 'Clean, analyze, and visualize datasets.'],
        ['Machine Learning', 'Data', '🤖', '#818cf8', 'Train models and integrate AI features.'],
        ['Research Methods', 'Data', '🔬', '#14b8a6', 'Survey design and statistical analysis.'],
        ['Content Writing', 'Writing', '✍️', '#fbbf24', 'Blog posts, articles, and website copy.'],
        ['Social Media', 'Marketing', '📱', '#3b82f6', 'Strategy and content for social platforms.'],
        ['Video Editing', 'Media', '🎬', '#ef4444', 'Short-form reels and YouTube videos.'],
    ];

    $skillStmt = $pdo->prepare('INSERT INTO skills (name, category, icon, accent_color, description) VALUES (?, ?, ?, ?, ?)');
    foreach ($skills as $s) {
        $skillStmt->execute($s);
    }

    $users = [
        ['Alice Chen', 'alice@demo.com', 'Computer Science', 'Project lead looking for talented collaborators on campus apps.', '#10b981', ['React', 'TypeScript', 'Node.js']],
        ['Bob Rahman', 'bob@demo.com', 'Software Engineering', 'Frontend developer passionate about accessible UI.', '#3b82f6', ['React', 'UI/UX Design', 'Figma']],
        ['Rezwan Islam', 'rezwan@demo.com', 'Computer Science', 'Full-stack student developer building real-world campus tools.', '#38bdf8', ['React', 'Python', 'UI/UX Design', 'Figma']],
        ['Tanzila Rahman', 'tanzila@demo.com', 'Information Systems', 'UX researcher and Figma designer for student startups.', '#ec4899', ['Figma', 'UI/UX Design', 'Branding']],
        ['Karim Hossain', 'karim@demo.com', 'Data Science', 'ML enthusiast working on research and analytics projects.', '#818cf8', ['Python', 'Machine Learning', 'Data Analysis']],
        ['Sadia Ahmed', 'sadia@demo.com', 'English Literature', 'Content writer and social media strategist for campus orgs.', '#fbbf24', ['Content Writing', 'Social Media', 'Branding']],
    ];

    $userStmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, major, bio, avatar_color) VALUES (?, ?, ?, ?, ?, ?)');
    $usStmt = $pdo->prepare('INSERT INTO user_skills (user_id, skill_id) SELECT ?, id FROM skills WHERE name = ?');

    foreach ($users as $u) {
        $userStmt->execute([$u[0], $u[1], $password, $u[2], $u[3], $u[4]]);
        $uid = (int)$pdo->lastInsertId();
        foreach ($u[5] as $skillName) {
            $usStmt->execute([$uid, $skillName]);
        }
    }

    $gigs = [
        [1, 'Campus Dining App UI Redesign', 'Redesign the mobile web experience for our campus dining menus. Need a polished, accessible interface.', 'freelance', '$400–600', '3–4 weeks', '', 'open', ['Figma', 'UI/UX Design']],
        [1, 'React Dashboard for Research Lab', 'Build an internal dashboard to visualize lab experiment results. Weekly sync with the team.', 'collaboration', '', '6 weeks', '2–3 people', 'open', ['React', 'TypeScript', 'Data Analysis']],
        [1, 'StudentCollab Platform MVP', 'Looking for frontend help to finish our collaboration marketplace for the Web Programming Lab.', 'collaboration', '', '4 weeks', '2–4 people', 'open', ['React', 'Node.js', 'Database Design']],
        [3, 'Portfolio Website for Photographer', 'One-page portfolio site with gallery and contact form. Clean, minimal aesthetic.', 'freelance', '$250–350', '2 weeks', '', 'open', ['React', 'UI/UX Design']],
        [3, 'Python Script for Data Cleaning', 'Automate cleaning survey CSV exports for a thesis project.', 'freelance', '$150', '1 week', '', 'open', ['Python', 'Data Analysis']],
        [4, 'Brand Identity for Campus Startup', 'Logo, color palette, and social templates for a new food delivery startup.', 'freelance', '$300–500', '2–3 weeks', '', 'open', ['Branding', 'Figma', 'Graphic Design']],
        [5, 'ML Model for Sentiment Analysis', 'Train a classifier on student feedback data. Scikit-learn preferred.', 'collaboration', '', '5 weeks', '2 people', 'open', ['Python', 'Machine Learning']],
        [5, 'Research Survey Analysis', 'Help analyze 200+ survey responses for a psychology research paper.', 'freelance', '$200', '2 weeks', '', 'open', ['Data Analysis', 'Research Methods']],
        [6, 'Blog Content for Tech Club', 'Write 4 blog posts about student projects and events.', 'freelance', '$120', '2 weeks', '', 'open', ['Content Writing']],
        [2, 'Video Edit for Hackathon Recap', 'Edit raw footage into a 2-minute highlight reel for social media.', 'freelance', '$180', '1 week', '', 'open', ['Video Editing', 'Social Media']],
    ];

    $gigStmt = $pdo->prepare('INSERT INTO gigs (owner_id, title, description, type, budget, duration, team_size, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $gsStmt = $pdo->prepare('INSERT INTO gig_skills (gig_id, skill_id) SELECT ?, id FROM skills WHERE name = ?');

    foreach ($gigs as $g) {
        $gigStmt->execute([$g[0], $g[1], $g[2], $g[3], $g[4], $g[5], $g[6], $g[7]]);
        $gid = (int)$pdo->lastInsertId();
        foreach ($g[8] as $skillName) {
            $gsStmt->execute([$gid, $skillName]);
        }
    }

    $portfolio = [
        [3, 'Campus Dining Application', 'Designed and built a mobile web layout reviewing local interactive food menus.', 'completed', ['Figma', 'UI/UX Design']],
        [3, 'StudentCollab Platform Redesign', 'Refactoring core layout and wiring features to a real database.', 'in_progress', ['React', 'Database Design']],
        [2, 'E-Commerce Product Page', 'Built a responsive React product page with cart integration.', 'completed', ['React', 'TypeScript']],
        [4, 'Startup Brand Kit', 'Complete brand identity for a campus food delivery startup.', 'completed', ['Branding', 'Figma']],
        [5, 'Sentiment Classifier', 'NLP model achieving 87% accuracy on student feedback.', 'completed', ['Python', 'Machine Learning']],
    ];

    $portStmt = $pdo->prepare('INSERT INTO portfolio_items (user_id, title, description, status) VALUES (?, ?, ?, ?)');
    $psStmt = $pdo->prepare('INSERT INTO portfolio_skills (portfolio_item_id, skill_id) SELECT ?, id FROM skills WHERE name = ?');

    foreach ($portfolio as $p) {
        $portStmt->execute([$p[0], $p[1], $p[2], $p[3]]);
        $pid = (int)$pdo->lastInsertId();
        foreach ($p[4] as $skillName) {
            $psStmt->execute([$pid, $skillName]);
        }
    }

    // Bob applied to Alice's React Dashboard (pending)
    $pdo->prepare('INSERT INTO applications (gig_id, applicant_id, message, status) VALUES (2, 2, ?, ?)')
        ->execute(['I have React and TypeScript experience from two lab projects. Would love to join!', 'pending']);

    // Rezwan applied to Campus Dining UI (pending)
    $pdo->prepare('INSERT INTO applications (gig_id, applicant_id, message, status) VALUES (1, 3, ?, ?)')
        ->execute(['I specialize in Figma and accessible UI design. Here is my portfolio link.', 'pending']);

    // Karim applied to ML gig (pending)
    $pdo->prepare('INSERT INTO applications (gig_id, applicant_id, message, status) VALUES (7, 5, ?, ?)')
        ->execute(['Built two classifiers last semester using scikit-learn. Happy to share code samples.', 'pending']);

    // Accepted application: Bob on StudentCollab MVP -> team chat
    $pdo->prepare('INSERT INTO applications (gig_id, applicant_id, message, status) VALUES (3, 2, ?, ?)')
        ->execute(['I can help with the React frontend this week.', 'accepted']);
    $pdo->exec("UPDATE gigs SET status = 'filled' WHERE id = 3");

    $convId = (int)$pdo->query("SELECT id FROM applications WHERE gig_id = 3 AND applicant_id = 2")->fetchColumn();
    $pdo->prepare('INSERT INTO conversations (application_id) VALUES (?)')->execute([$convId]);
    $conversationId = (int)$pdo->lastInsertId();

    $msgs = [
        [1, 'Hi Bob! Thanks for applying to the StudentCollab MVP project.'],
        [2, 'Thanks Alice! I reviewed the scope — I can take the opportunities browse page.'],
        [1, 'Perfect. Let us sync tomorrow after class. I will share the Figma link.'],
        [2, 'Sounds good. I will prepare a component structure draft tonight.'],
    ];
    $msgStmt = $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)');
    foreach ($msgs as $m) {
        $msgStmt->execute([$conversationId, $m[0], $m[1]]);
    }

    // Accepted: Tanzila on brand identity
    $pdo->prepare('INSERT INTO applications (gig_id, applicant_id, message, status) VALUES (6, 4, ?, ?)')
        ->execute(['I have done 3 startup brand kits this year. Can start immediately.', 'accepted']);
    $pdo->exec("UPDATE gigs SET status = 'filled' WHERE id = 6");

    $convId2 = (int)$pdo->query("SELECT id FROM applications WHERE gig_id = 6 AND applicant_id = 4")->fetchColumn();
    $pdo->prepare('INSERT INTO conversations (application_id) VALUES (?)')->execute([$convId2]);
    $conversationId2 = (int)$pdo->lastInsertId();

    $msgs2 = [
        [1, 'Welcome aboard Tanzila! Excited to work on the brand kit.'],
        [4, 'Thank you! I will send mood board options by Friday.'],
    ];
    foreach ($msgs2 as $m) {
        $msgStmt->execute([$conversationId2, $m[0], $m[1]]);
    }
}
