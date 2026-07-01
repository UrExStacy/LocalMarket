<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$user_id    = $_SESSION['user_id'];
$listing_id = (int)($_POST['listing_id'] ?? 0);

if ($listing_id === 0) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = :id");
    $stmt->execute([':id' => $listing_id]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);

    $is_owner = $listing && (int)$listing['user_id'] === $user_id;
    $is_admin = $_SESSION['user_role'] === 'admin';

    if (!$listing || (!$is_owner && !$is_admin)) {
        header('Location: index.php');
        exit;
    }

    if (!empty($listing['image'])) {
        $path = 'images/products/' . $listing['image'];
        if (file_exists($path)) unlink($path);
    }

    $pdo->prepare("DELETE FROM listings WHERE id = :id")->execute([':id' => $listing_id]);

    if ($is_admin && !$is_owner) {
        header('Location: admin/dashboard.php?deleted=1');
    } else {
        header('Location: index.php?deleted=1');
    }
    exit;

} catch (Exception $e) {
    header('Location: index.php');
    exit;
}