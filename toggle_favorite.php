<?php
session_start();
require('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_id'])) {
    $videoId = $_POST['video_id'];
    $profileId = $_SESSION['profile_id'];
    
    $stmt = $conn->prepare("SELECT * FROM favoris WHERE id_profil = ? AND id_video = ?");
    $stmt->execute([$profileId, $videoId]);
    
    if ($stmt->fetch()) {
        $stmt = $conn->prepare("DELETE FROM favoris WHERE id_profil = ? AND id_video = ?");
        $stmt->execute([$profileId, $videoId]);
    } else {
        $stmt = $conn->prepare("INSERT INTO favoris (id_profil, id_video) VALUES (?, ?)");
        $stmt->execute([$profileId, $videoId]);
    }
}

header("Location: index.php");
exit();