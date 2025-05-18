<?php
session_start();
require('db.php');

if (isset($_SESSION['profile_id'])) {
    $stmt = $conn->prepare("DELETE FROM favoris WHERE id_profil = ?");
    $stmt->execute([$_SESSION['profile_id']]);
}

header("Location: index.php");
exit();