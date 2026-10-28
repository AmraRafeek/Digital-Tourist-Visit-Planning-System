<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {

    $stmt = $pdo->prepare(
        'DELETE FROM places WHERE id = ?'
    );

    $stmt->execute([$id]);
}

header('Location: places.php');
exit;
?>
