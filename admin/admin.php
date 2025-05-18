<?php
session_start();
include('../db.php');

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check admin status
$stmt = $conn->prepare("SELECT is_admin FROM utilisateur WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !$user['is_admin']) {
    header("Location: ../home.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle different form submissions based on which form was submitted
    if (isset($_POST['add_video'])) {
        try {
            // First check if video with same title and language already exists
            $checkStmt = $conn->prepare("SELECT id FROM video WHERE titre = ? AND langue_originale = ?");
            $checkStmt->execute([
                $_POST['titre'],
                $_POST['langue_originale']
            ]);
            
            if ($checkStmt->fetch()) {
                throw new Exception("A video with this title and language combination already exists.");
            }

            // Add new video
            $stmt = $conn->prepare("INSERT INTO video (titre, categorie, description, date_publication, langue_originale, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['titre'],
                $_POST['categorie'],
                $_POST['description'],
                $_POST['date_publication'],
                $_POST['langue_originale'],
                $_POST['image']
            ]);
            $video_id = $conn->lastInsertId();
            
            if ($_POST['type'] == 'film') {
                $stmt = $conn->prepare("INSERT INTO film (id_video, duree, url_video) VALUES (?, ?, ?)");
                $stmt->execute([$video_id, $_POST['duree'], $_POST['url_video']]);
            } else {
                $stmt = $conn->prepare("INSERT INTO serie (id_video, duree) VALUES (?, ?)");
                $stmt->execute([$video_id, $_POST['duree']]);
            }
            
            $success = "Video added successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['add_episode'])) {
        // Add new episode
        try {
            $stmt = $conn->prepare("INSERT INTO episode (id_serie, numero_episode, numero_saison, url_video, duree, description, titre, image_episode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id_serie'],
                $_POST['numero_episode'],
                $_POST['numero_saison'],
                $_POST['url_video'],
                $_POST['duree'],
                $_POST['description'],
                $_POST['titre'],
                $_POST['image_episode']
            ]);
            $success = "Episode added successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['add_subtitle'])) {
        // Add new subtitle
        try {
            $stmt = $conn->prepare("INSERT INTO soustitre (id_video_film, id_episode, langue, url_fichier) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_POST['id_video_film'] ?: null,
                $_POST['id_episode'] ?: null,
                $_POST['langue'],
                $_POST['url_fichier']
            ]);
            $success = "Subtitle added successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['add_subscription'])) {
        // Add new subscription
        try {
            $stmt = $conn->prepare("INSERT INTO abonnement (nom, prix, nb_profile_max, date_debut, date_fin) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['nom'],
                $_POST['prix'],
                $_POST['nb_profile_max'],
                $_POST['date_debut'],
                $_POST['date_fin']
            ]);
            $success = "Subscription plan added successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['update_subscription'])) {
        // Update subscription
        try {
            $stmt = $conn->prepare("UPDATE abonnement SET nom = ?, prix = ?, nb_profile_max = ?, date_debut = ?, date_fin = ? WHERE id = ?");
            $stmt->execute([
                $_POST['nom'],
                $_POST['prix'],
                $_POST['nb_profile_max'],
                $_POST['date_debut'],
                $_POST['date_fin'],
                $_POST['id']
            ]);
            $success = "Subscription plan updated successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['delete_subscription'])) {
        // Delete subscription
        try {
            $stmt = $conn->prepare("DELETE FROM abonnement WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $success = "Subscription plan deleted successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['update_user'])) {
        // Update user
        try {
            $stmt = $conn->prepare("UPDATE utilisateur SET nom = ?, email = ?, id_abonnement = ?, abonnement_actif = ? WHERE id = ?");
            $stmt->execute([
                $_POST['nom'],
                $_POST['email'],
                $_POST['id_abonnement'],
                isset($_POST['abonnement_actif']) ? 1 : 0,
                $_POST['id']
            ]);
            $success = "User updated successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        try {
            $stmt = $conn->prepare("DELETE FROM utilisateur WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $success = "User deleted successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['update_video'])) {
        // Update video
        try {
            $stmt = $conn->prepare("UPDATE video SET titre = ?, categorie = ?, description = ?, date_publication = ?, langue_originale = ?, image = ? WHERE id = ?");
            $stmt->execute([
                $_POST['titre'],
                $_POST['categorie'],
                $_POST['description'],
                $_POST['date_publication'],
                $_POST['langue_originale'],
                $_POST['image'],
                $_POST['id']
            ]);
            
            // Update film/serie specific data
            $videoType = $conn->query("SELECT CASE WHEN f.id_video IS NOT NULL THEN 'film' ELSE 'serie' END as type FROM video v LEFT JOIN film f ON v.id = f.id_video WHERE v.id = " . $_POST['id'])->fetchColumn();
            
            if ($videoType == 'film') {
                $stmt = $conn->prepare("UPDATE film SET duree = ?, url_video = ? WHERE id_video = ?");
                $stmt->execute([$_POST['duree'], $_POST['url_video'], $_POST['id']]);
            } else {
                $stmt = $conn->prepare("UPDATE serie SET duree = ? WHERE id_video = ?");
                $stmt->execute([$_POST['duree'], $_POST['id']]);
            }
            
            $success = "Video updated successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['update_episode'])) {
        // Update episode
        try {
            $stmt = $conn->prepare("UPDATE episode SET id_serie = ?, numero_episode = ?, numero_saison = ?, url_video = ?, duree = ?, description = ?, titre = ?, image_episode = ? WHERE id = ?");
            $stmt->execute([
                $_POST['id_serie'],
                $_POST['numero_episode'],
                $_POST['numero_saison'],
                $_POST['url_video'],
                $_POST['duree'],
                $_POST['description'],
                $_POST['titre'],
                $_POST['image_episode'],
                $_POST['id']
            ]);
            $success = "Episode updated successfully!";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get data for display
$videos = $conn->query("SELECT v.*, CASE WHEN f.id_video IS NOT NULL THEN 'film' ELSE 'serie' END as type FROM video v LEFT JOIN film f ON v.id = f.id_video LEFT JOIN serie s ON v.id = s.id_video")->fetchAll();
$series = $conn->query("SELECT v.id, v.titre FROM video v JOIN serie s ON v.id = s.id_video")->fetchAll();
$episodes = $conn->query("SELECT e.*, v.titre as serie_title FROM episode e JOIN serie s ON e.id_serie = s.id_video JOIN video v ON s.id_video = v.id")->fetchAll();
$subtitles = $conn->query("SELECT s.*, COALESCE(v.titre, e.titre) as video_title FROM soustitre s LEFT JOIN film f ON s.id_video_film = f.id_video LEFT JOIN video v ON f.id_video = v.id LEFT JOIN episode e ON s.id_episode = e.id")->fetchAll();
$subscriptions = $conn->query("SELECT * FROM abonnement")->fetchAll();
$users = $conn->query("SELECT u.*, a.nom as abonnement_nom FROM utilisateur u JOIN abonnement a ON u.id_abonnement = a.id")->fetchAll();
$profiles = $conn->query("SELECT p.*, u.nom as user_name FROM profils p JOIN utilisateur u ON p.id_utilisateur = u.id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00A8E1;
            --primary-dark: #0088b9;
            --secondary: #f4c430;
            --secondary-dark: #e6b82e;
            --dark: #0f0f0f;
            --darker: #0a0a0a;
            --light: #ffffff;
            --gray: #a2a2a2;
            --dark-gray: #2a2a2a;
            --light-gray: #e0e0e0;
            --success: #46d369;
            --error: #ff4d4d;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: var(--darker);
            color: var(--light);
            min-height: 100vh;
            background-image: linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.85)),
                            url('https://m.media-amazon.com/images/M/MV5BZjBlMjIxYzgtOGYyOC00NzY3LTg1ZWMtM2U0NzE0OTQ2YjRlXkEyXkFqcGdeQXVyMTUzMTg2ODkz._V1_.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            /*padding: 30px 20px;*/
            animation: fadeIn 0.5s ease;
            padding-top: 120px; /* Increased from 30px */
            margin-top: 0;
        }
        
        .admin-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .admin-title {
            font-size: 2.5rem;
            margin-bottom: 40px;
            color: var(--light);
            font-weight: 600;
            position: relative;
            display: inline-block;
        }
        
        .admin-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--secondary);
        }
        
        .admin-section {
            background-color: rgba(26, 26, 26, 0.9);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border-top: 3px solid var(--secondary);
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 25px;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }
        
        .tab {
            padding: 10px 20px;
            background: none;
            border: none;
            color: var(--gray);
            font-weight: 600;
            cursor: pointer;
            position: relative;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .tab:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .tab.active {
            color: var(--light);
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -11px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--secondary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .data-table th {
            background-color: rgba(44, 44, 44, 0.5);
            color: var(--secondary);
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background-color: rgba(44, 44, 44, 0.3);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-button {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .edit-button {
            background-color: var(--primary);
            color: var(--light);
        }
        
        .edit-button:hover {
            background-color: var(--primary-dark);
        }
        
        .delete-button {
            background-color: var(--error);
            color: var(--light);
        }
        
        .delete-button:hover {
            background-color: #cc0000;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--light-gray);
            font-size: 0.95rem;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            background-color: rgba(44, 44, 44, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            background-color: rgba(44, 44, 44, 1);
            box-shadow: 0 0 0 3px rgba(0, 168, 225, 0.2);
        }
        
        .submit-button {
            background-color: var(--secondary);
            color: var(--dark);
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .submit-button:hover {
            background-color: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 196, 48, 0.3);
        }
        
        .success-message {
            color: var(--success);
            margin-bottom: 20px;
            padding: 15px;
            background-color: rgba(70, 211, 105, 0.1);
            border-radius: 8px;
            animation: fadeIn 0.5s ease;
        }
        
        .error-message {
            color: var(--error);
            margin-bottom: 20px;
            padding: 15px;
            background-color: rgba(255, 77, 77, 0.1);
            border-radius: 8px;
            animation: shake 0.5s ease;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: rgba(44, 44, 44, 0.5);
            padding: 20px;
            border-radius: 8px;
            border-left: 3px solid var(--primary);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .image-preview img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
            margin-top: 8px;
            border: 1px solid var(--dark-gray);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background-color: var(--dark);
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-title {
            font-size: 1.8rem;
            color: var(--light);
        }
        
        .close-modal {
            background: none;
            border: none;
            color: var(--gray);
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .close-modal:hover {
            color: var(--light);
        }
        
        /* Mobile styles */
        @media (max-width: 768px) {
            .admin-title {
                font-size: 2rem;
            }
            
            .admin-section {
                padding: 25px 20px;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                flex: 1 0 auto;
                text-align: center;
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .admin-title {
                font-size: 1.8rem;
            }
            
            .section-title {
                font-size: 1.3rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .admin-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
            padding: 15px 30px;
            background-color: #f4c430;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .admin-logo {
            height: 55px;
        }

        .admin-logout {
            color: #000;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .admin-logout:hover {
            background-color: rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <nav class="admin-navbar">
        <a>
            <img class="admin-logo" src="../images/logo8.png" alt="BING Logo">
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="../login.php" class="admin-logout">Log Out</a>
        <?php endif; ?>
    </nav>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Admin Dashboard</h1>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="admin-section">
            <h2 class="section-title">
                <i class="fas fa-chart-line"></i> Quick Stats
            </h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($videos); ?></div>
                    <div class="stat-label">Total Videos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($series); ?></div>
                    <div class="stat-label">TV Series</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($episodes); ?></div>
                    <div class="stat-label">Episodes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($users); ?></div>
                    <div class="stat-label">Users</div>
                </div>
            </div>
        </div>
        
        <!-- Main Admin Content -->
        <div class="admin-section">
            <div class="tabs">
                <button class="tab active" data-tab="videos">Videos</button>
                <button class="tab" data-tab="episodes">Episodes</button>
                <button class="tab" data-tab="subtitles">Subtitles</button>
                <button class="tab" data-tab="subscriptions">Subscriptions</button>
                <button class="tab" data-tab="users">Users</button>
                <button class="tab" data-tab="profiles">Profiles</button>
            </div>
            
            <!-- Videos Tab -->
            <div class="tab-content active" id="videos-tab">
                <h3 class="section-title">
                    <i class="fas fa-film"></i> Manage Videos
                </h3>
                
                <button class="submit-button" id="addVideoBtn">
                    <i class="fas fa-plus"></i> Add New Video
                </button>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Release Date</th>
                            <th>Language</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $video): ?>
                            <tr>
                                <td><?php echo $video['id']; ?></td>
                                <td><?php echo htmlspecialchars($video['titre']); ?></td>
                                <td><?php echo ucfirst($video['type']); ?></td>
                                <td><?php echo htmlspecialchars($video['categorie']); ?></td>
                                <td><?php echo $video['date_publication']; ?></td>
                                <td><?php echo htmlspecialchars($video['langue_originale']); ?></td>
                                <td>
                                    <?php if ($video['image']): ?>
                                        <img src="<?php echo htmlspecialchars($video['image']); ?>" alt="Thumbnail" style="max-width: 50px; max-height: 50px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" onclick="editVideo(<?php echo $video['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-button delete-button" onclick="confirmDelete('video', <?php echo $video['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Episodes Tab -->
            <div class="tab-content" id="episodes-tab">
                <h3 class="section-title">
                    <i class="fas fa-tv"></i> Manage Episodes
                </h3>
                
                <button class="submit-button" id="addEpisodeBtn">
                    <i class="fas fa-plus"></i> Add New Episode
                </button>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Series</th>
                            <th>Season</th>
                            <th>Episode</th>
                            <th>Duration</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($episodes as $episode): ?>
                            <tr>
                                <td><?php echo $episode['id']; ?></td>
                                <td><?php echo htmlspecialchars($episode['titre']); ?></td>
                                <td><?php echo htmlspecialchars($episode['serie_title']); ?></td>
                                <td><?php echo $episode['numero_saison']; ?></td>
                                <td><?php echo $episode['numero_episode']; ?></td>
                                <td><?php echo floor($episode['duree'] / 60) . 'm ' . ($episode['duree'] % 60) . 's'; ?></td>
                                <td>
                                    <?php if ($episode['image_episode']): ?>
                                        <img src="<?php echo htmlspecialchars($episode['image_episode']); ?>" alt="Thumbnail" style="max-width: 50px; max-height: 50px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" onclick="editEpisode(<?php echo $episode['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-button delete-button" onclick="confirmDelete('episode', <?php echo $episode['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Subtitles Tab -->
            <div class="tab-content" id="subtitles-tab">
                <h3 class="section-title">
                    <i class="fas fa-closed-captioning"></i> Manage Subtitles
                </h3>
                
                <button class="submit-button" id="addSubtitleBtn">
                    <i class="fas fa-plus"></i> Add New Subtitle
                </button>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Video/Episode</th>
                            <th>Language</th>
                            <th>File URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subtitles as $subtitle): ?>
                            <tr>
                                <td><?php echo $subtitle['id']; ?></td>
                                <td><?php echo htmlspecialchars($subtitle['video_title']); ?></td>
                                <td><?php echo htmlspecialchars($subtitle['langue']); ?></td>
                                <td><?php echo htmlspecialchars($subtitle['url_fichier']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" onclick="editSubtitle(<?php echo $subtitle['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-button delete-button" onclick="confirmDelete('subtitle', <?php echo $subtitle['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Subscriptions Tab -->
            <div class="tab-content" id="subscriptions-tab">
                <h3 class="section-title">
                    <i class="fas fa-crown"></i> Manage Subscriptions
                </h3>
                
                <button class="submit-button" id="addSubscriptionBtn">
                    <i class="fas fa-plus"></i> Add New Plan
                </button>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Max Profiles</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <tr>
                                <td><?php echo $subscription['id']; ?></td>
                                <td><?php echo htmlspecialchars($subscription['nom']); ?></td>
                                <td>$<?php echo number_format($subscription['prix'], 2); ?></td>
                                <td><?php echo $subscription['nb_profile_max']; ?></td>
                                <td><?php echo $subscription['date_debut']; ?></td>
                                <td><?php echo $subscription['date_fin']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" onclick="editSubscription(<?php echo $subscription['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-button delete-button" onclick="confirmDelete('subscription', <?php echo $subscription['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Users Tab -->
            <div class="tab-content" id="users-tab">
                <h3 class="section-title">
                    <i class="fas fa-users"></i> Manage Users
                </h3>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subscription</th>
                            <th>Join Date</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['abonnement_nom']); ?></td>
                                <td><?php echo $user['date_creation']; ?></td>
                                <td><?php echo $user['abonnement_actif'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" onclick="editUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-button delete-button" onclick="confirmDelete('user', <?php echo $user['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Profiles Tab -->
            <div class="tab-content" id="profiles-tab">
                <h3 class="section-title">
                    <i class="fas fa-user-circle"></i> Manage Profiles
                </h3>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>User</th>
                            <th>Age</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profiles as $profile): ?>
                            <tr>
                                <td><?php echo $profile['id']; ?></td>
                                <td><?php echo htmlspecialchars($profile['nom']); ?></td>
                                <td><?php echo htmlspecialchars($profile['user_name']); ?></td>
                                <td><?php echo $profile['age'] ?? 'N/A'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" onclick="editProfile(<?php echo $profile['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="action-button delete-button" onclick="confirmDelete('profile', <?php echo $profile['id']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Add Video Modal -->
    <div class="modal-overlay" id="videoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Video</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="titre">Title</label>
                        <input type="text" id="titre" name="titre" required>
                    </div>
                    <div class="form-group">
                        <label for="categorie">Category</label>
                        <input type="text" id="categorie" name="categorie" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="film">Movie</option>
                            <option value="serie">TV Series</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_publication">Release Date</label>
                        <input type="date" id="date_publication" name="date_publication" required>
                    </div>
                    <div class="form-group">
                        <label for="langue_originale">Original Language</label>
                        <input type="text" id="langue_originale" name="langue_originale" required>
                    </div>
                    <div class="form-group">
                        <label for="image">Image URL</label>
                        <input type="text" id="image" name="image" required>
                        <div class="image-preview" id="addVideoImagePreview"></div>
                    </div>
                    <div class="form-group">
                        <label for="duree">Duration (minutes)</label>
                        <input type="number" id="duree" name="duree" required>
                    </div>
                    <div class="form-group">
                        <label for="url_video">Video URL</label>
                        <input type="text" id="url_video" name="url_video" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <button type="submit" name="add_video" class="submit-button">
                    <i class="fas fa-save"></i> Save Video
                </button>
            </form>
        </div>
    </div>
    
    <!-- Add Episode Modal -->
    <div class="modal-overlay" id="episodeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Episode</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_serie">Series</label>
                        <select id="id_serie" name="id_serie" required>
                            <?php foreach ($series as $serie): ?>
                                <option value="<?php echo $serie['id']; ?>"><?php echo htmlspecialchars($serie['titre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="titre">Title</label>
                        <input type="text" id="titre" name="titre" required>
                    </div>
                    <div class="form-group">
                        <label for="numero_saison">Season Number</label>
                        <input type="number" id="numero_saison" name="numero_saison" required>
                    </div>
                    <div class="form-group">
                        <label for="numero_episode">Episode Number</label>
                        <input type="number" id="numero_episode" name="numero_episode" required>
                    </div>
                    <div class="form-group">
                        <label for="duree">Duration (seconds)</label>
                        <input type="number" id="duree" name="duree" required>
                    </div>
                    <div class="form-group">
                        <label for="url_video">Video URL</label>
                        <input type="text" id="url_video" name="url_video" required>
                    </div>
                    <div class="form-group">
                        <label for="image_episode">Image URL</label>
                        <input type="text" id="image_episode" name="image_episode" required>
                        <div class="image-preview" id="addEpisodeImagePreview"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <button type="submit" name="add_episode" class="submit-button">
                    <i class="fas fa-save"></i> Save Episode
                </button>
            </form>
        </div>
    </div>
    
    <!-- Add Subtitle Modal -->
    <div class="modal-overlay" id="subtitleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Subtitle</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="subtitle_type">Subtitle For</label>
                        <select id="subtitle_type" name="subtitle_type" required>
                            <option value="film">Movie</option>
                            <option value="episode">Episode</option>
                        </select>
                    </div>
                    <div class="form-group" id="film-select-group">
                        <label for="id_video_film">Movie</label>
                        <select id="id_video_film" name="id_video_film">
                            <?php 
                            $films = $conn->query("SELECT v.id, v.titre FROM video v JOIN film f ON v.id = f.id_video")->fetchAll();
                            foreach ($films as $film): ?>
                                <option value="<?php echo $film['id']; ?>"><?php echo htmlspecialchars($film['titre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="episode-select-group" style="display: none;">
                        <label for="id_episode">Episode</label>
                        <select id="id_episode" name="id_episode">
                            <?php foreach ($episodes as $episode): ?>
                                <option value="<?php echo $episode['id']; ?>"><?php echo htmlspecialchars($episode['serie_title'] . ' - S' . $episode['numero_saison'] . 'E' . $episode['numero_episode'] . ' - ' . $episode['titre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="langue">Language</label>
                        <input type="text" id="langue" name="langue" required>
                    </div>
                    <div class="form-group">
                        <label for="url_fichier">Subtitle File URL</label>
                        <input type="text" id="url_fichier" name="url_fichier" required>
                    </div>
                </div>
                <button type="submit" name="add_subtitle" class="submit-button">
                    <i class="fas fa-save"></i> Save Subtitle
                </button>
            </form>
        </div>
    </div>
    
    <!-- Add Subscription Modal -->
    <div class="modal-overlay" id="subscriptionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Subscription Plan</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom">Plan Name</label>
                        <input type="text" id="nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="prix">Price</label>
                        <input type="number" step="0.01" id="prix" name="prix" required>
                    </div>
                    <div class="form-group">
                        <label for="nb_profile_max">Max Profiles</label>
                        <input type="number" id="nb_profile_max" name="nb_profile_max" required>
                    </div>
                    <div class="form-group">
                        <label for="date_debut">Start Date</label>
                        <input type="date" id="date_debut" name="date_debut" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">End Date</label>
                        <input type="date" id="date_fin" name="date_fin" required>
                    </div>
                </div>
                <button type="submit" name="add_subscription" class="submit-button">
                    <i class="fas fa-save"></i> Save Plan
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Video Modal -->
    <div class="modal-overlay" id="editVideoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Video</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_video_id" name="id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_video_titre">Title</label>
                        <input type="text" id="edit_video_titre" name="titre" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_video_categorie">Category</label>
                        <input type="text" id="edit_video_categorie" name="categorie" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_video_type">Type</label>
                        <select id="edit_video_type" name="type" required disabled>
                            <option value="film">Movie</option>
                            <option value="serie">TV Series</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_video_date_publication">Release Date</label>
                        <input type="date" id="edit_video_date_publication" name="date_publication" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_video_langue_originale">Original Language</label>
                        <input type="text" id="edit_video_langue_originale" name="langue_originale" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_video_image">Image URL</label>
                        <input type="text" id="edit_video_image" name="image" required>
                        <div class="image-preview" id="videoImagePreview"></div>
                    </div>
                    <div class="form-group">
                        <label for="edit_video_duree">Duration (minutes)</label>
                        <input type="number" id="edit_video_duree" name="duree" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_video_url_video">Video URL</label>
                        <input type="text" id="edit_video_url_video" name="url_video" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_video_description">Description</label>
                    <textarea id="edit_video_description" name="description" required></textarea>
                </div>
                <button type="submit" name="update_video" class="submit-button">
                    <i class="fas fa-save"></i> Update Video
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Episode Modal -->
    <div class="modal-overlay" id="editEpisodeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Episode</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_episode_id" name="id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_episode_id_serie">Series</label>
                        <select id="edit_episode_id_serie" name="id_serie" required>
                            <?php foreach ($series as $serie): ?>
                                <option value="<?php echo $serie['id']; ?>"><?php echo htmlspecialchars($serie['titre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_episode_titre">Title</label>
                        <input type="text" id="edit_episode_titre" name="titre" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_episode_numero_saison">Season Number</label>
                        <input type="number" id="edit_episode_numero_saison" name="numero_saison" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_episode_numero_episode">Episode Number</label>
                        <input type="number" id="edit_episode_numero_episode" name="numero_episode" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_episode_duree">Duration (seconds)</label>
                        <input type="number" id="edit_episode_duree" name="duree" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_episode_url_video">Video URL</label>
                        <input type="text" id="edit_episode_url_video" name="url_video" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_episode_image_episode">Image URL</label>
                        <input type="text" id="edit_episode_image_episode" name="image_episode" required>
                        <div class="image-preview" id="episodeImagePreview"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_episode_description">Description</label>
                    <textarea id="edit_episode_description" name="description" required></textarea>
                </div>
                <button type="submit" name="update_episode" class="submit-button">
                    <i class="fas fa-save"></i> Update Episode
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Subscription Modal -->
    <div class="modal-overlay" id="editSubscriptionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit Subscription Plan</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_nom">Plan Name</label>
                        <input type="text" id="edit_nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_prix">Price</label>
                        <input type="number" step="0.01" id="edit_prix" name="prix" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_nb_profile_max">Max Profiles</label>
                        <input type="number" id="edit_nb_profile_max" name="nb_profile_max" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_date_debut">Start Date</label>
                        <input type="date" id="edit_date_debut" name="date_debut" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_date_fin">End Date</label>
                        <input type="date" id="edit_date_fin" name="date_fin" required>
                    </div>
                </div>
                <button type="submit" name="update_subscription" class="submit-button">
                    <i class="fas fa-save"></i> Update Plan
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit User</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_user_id" name="id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_user_nom">Name</label>
                        <input type="text" id="edit_user_nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_user_email">Email</label>
                        <input type="email" id="edit_user_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_user_id_abonnement">Subscription Plan</label>
                        <select id="edit_user_id_abonnement" name="id_abonnement" required>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <option value="<?php echo $subscription['id']; ?>"><?php echo htmlspecialchars($subscription['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="edit_user_abonnement_actif" name="abonnement_actif">
                            Active Subscription
                        </label>
                    </div>
                </div>
                <button type="submit" name="update_user" class="submit-button">
                    <i class="fas fa-save"></i> Update User
                </button>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Confirm Deletion</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="confirmation-message">
                Are you sure you want to delete this item? This action cannot be undone.
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" id="delete_id" name="id">
                <div class="confirmation-buttons">
                    <button type="submit" class="confirmation-button confirm-btn" id="confirmDeleteBtn">
                        Delete
                    </button>
                    <button type="button" class="confirmation-button cancel-btn" onclick="closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and content
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(`${tabId}-tab`).classList.add('active');
            });
        });
        
        // Modal functionality
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.classList.remove('active');
            });
            document.body.style.overflow = 'auto';
        }
        
        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        });
        
        // Open modals when buttons are clicked
        document.getElementById('addVideoBtn').addEventListener('click', () => openModal('videoModal'));
        document.getElementById('addEpisodeBtn').addEventListener('click', () => openModal('episodeModal'));
        document.getElementById('addSubtitleBtn').addEventListener('click', () => openModal('subtitleModal'));
        document.getElementById('addSubscriptionBtn').addEventListener('click', () => openModal('subscriptionModal'));
        
        // Subtitle type toggle
        document.getElementById('subtitle_type').addEventListener('change', function() {
            if (this.value === 'film') {
                document.getElementById('film-select-group').style.display = 'block';
                document.getElementById('episode-select-group').style.display = 'none';
                document.getElementById('id_video_film').setAttribute('required', '');
                document.getElementById('id_episode').removeAttribute('required');
            } else {
                document.getElementById('film-select-group').style.display = 'none';
                document.getElementById('episode-select-group').style.display = 'block';
                document.getElementById('id_episode').setAttribute('required', '');
                document.getElementById('id_video_film').removeAttribute('required');
            }
        });
        
        // Image preview for add video modal
        document.getElementById('image').addEventListener('input', function() {
            const preview = document.getElementById('addVideoImagePreview');
            preview.innerHTML = this.value ? 
                `<img src="${this.value}" alt="Preview">` : 
                'No image selected';
        });
        
        // Image preview for add episode modal
        document.getElementById('image_episode').addEventListener('input', function() {
            const preview = document.getElementById('addEpisodeImagePreview');
            preview.innerHTML = this.value ? 
                `<img src="${this.value}" alt="Preview">` : 
                'No image selected';
        });
        
        // Edit video
        function editVideo(id) {
            fetch(`get_video.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const video = data.data;
                        document.getElementById('edit_video_id').value = video.id;
                        document.getElementById('edit_video_titre').value = video.titre;
                        document.getElementById('edit_video_categorie').value = video.categorie;
                        document.getElementById('edit_video_type').value = video.type;
                        document.getElementById('edit_video_date_publication').value = video.date_publication;
                        document.getElementById('edit_video_langue_originale').value = video.langue_originale;
                        document.getElementById('edit_video_image').value = video.image;
                        document.getElementById('edit_video_duree').value = video.duree;
                        document.getElementById('edit_video_url_video').value = video.url_video || '';
                        document.getElementById('edit_video_description').value = video.description;
                        
                        // Update image preview
                        const preview = document.getElementById('videoImagePreview');
                        preview.innerHTML = video.image ? 
                            `<img src="${video.image}" alt="Preview">` : 
                            'No image selected';
                        
                        openModal('editVideoModal');
                    } else {
                        alert('Error loading video data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching video data:', error);
                    alert('Error loading video data');
                });
        }
        
        // Edit episode
        function editEpisode(id) {
            fetch(`get_episode.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const episode = data.data;
                        document.getElementById('edit_episode_id').value = episode.id;
                        document.getElementById('edit_episode_id_serie').value = episode.id_serie;
                        document.getElementById('edit_episode_titre').value = episode.titre;
                        document.getElementById('edit_episode_numero_saison').value = episode.numero_saison;
                        document.getElementById('edit_episode_numero_episode').value = episode.numero_episode;
                        document.getElementById('edit_episode_duree').value = episode.duree;
                        document.getElementById('edit_episode_url_video').value = episode.url_video;
                        document.getElementById('edit_episode_image_episode').value = episode.image_episode;
                        document.getElementById('edit_episode_description').value = episode.description;
                        
                        // Update image preview
                        const preview = document.getElementById('episodeImagePreview');
                        preview.innerHTML = episode.image_episode ? 
                            `<img src="${episode.image_episode}" alt="Preview">` : 
                            'No image selected';
                        
                        openModal('editEpisodeModal');
                    } else {
                        alert('Error loading episode data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching episode data:', error);
                    alert('Error loading episode data');
                });
        }
        
        // Edit subscription
        function editSubscription(id) {
            fetch(`get_subscription.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const subscription = data.data;
                        document.getElementById('edit_id').value = subscription.id;
                        document.getElementById('edit_nom').value = subscription.nom;
                        document.getElementById('edit_prix').value = subscription.prix;
                        document.getElementById('edit_nb_profile_max').value = subscription.nb_profile_max;
                        document.getElementById('edit_date_debut').value = subscription.date_debut;
                        document.getElementById('edit_date_fin').value = subscription.date_fin;
                        openModal('editSubscriptionModal');
                    } else {
                        alert('Error loading subscription data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching subscription data:', error);
                    alert('Error loading subscription data');
                });
        }
        
        // Edit user
        function editUser(id) {
            fetch(`get_user.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const user = data.data;
                        document.getElementById('edit_user_id').value = user.id;
                        document.getElementById('edit_user_nom').value = user.nom;
                        document.getElementById('edit_user_email').value = user.email;
                        document.getElementById('edit_user_id_abonnement').value = user.id_abonnement;
                        document.getElementById('edit_user_abonnement_actif').checked = user.abonnement_actif;
                        openModal('editUserModal');
                    } else {
                        alert('Error loading user data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching user data:', error);
                    alert('Error loading user data');
                });
        }
        
        // Confirm delete
        function confirmDelete(type, id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').action = `delete_${type}.php`;
            document.getElementById('confirmDeleteBtn').name = `delete_${type}`;
            openModal('confirmModal');
        }
        
        // These functions would be implemented similarly for other edit functionality
        function editSubtitle(id) {
            // Implement fetch to get subtitle data and populate form
            console.log('Edit subtitle:', id);
        }
        
        function editProfile(id) {
            // Implement fetch to get profile data and populate form
            console.log('Edit profile:', id);
        }
    </script>
</body>
</html>