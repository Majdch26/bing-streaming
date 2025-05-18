<?php
session_start();
require('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_id'])) {
    $stmt = $conn->prepare("DELETE FROM favoris WHERE id_profil = ? AND id_video = ?");
    $stmt->execute([$_SESSION['profile_id'], $_POST['video_id']]);
}

header("Location: index.php");
exit();