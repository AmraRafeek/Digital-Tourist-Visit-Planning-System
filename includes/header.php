<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$planCount = isset($_SESSION['trip_plan']) ? count($_SESSION['trip_plan']) : 0;

$isAdminPage = isset($basePath) && $basePath === '../' && !empty($_SESSION['admin_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($pageTitle ?? 'Digital Tourist') ?></title>

    <!-- FAVICON -->
    <link
        rel="icon"
        type="image/png"
        href="<?= htmlspecialchars($basePath ?? '') ?>Images/favicon.png"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars($basePath ?? '') ?>assets/css/style.css"
    >

</head>

<body>

<header class="site-header">

    <div class="container nav-wrap">

        <!-- LOGO + BRAND -->

        <a
            class="brand"
            href="<?= htmlspecialchars($basePath ?? '') ?>index.php"
        >

            <img
                class="brand-logo"
                src="<?= htmlspecialchars($basePath ?? '') ?>Images/logo.png"
                alt="Digital Tourist Logo"
            >

            <span>
                <strong>Digital Tourist</strong>
                <small>Visit Planning System</small>
            </span>

        </a>


        <!-- NAVIGATION -->

        <nav class="main-nav">

            <a href="<?= htmlspecialchars($basePath ?? '') ?>index.php">
                Home
            </a>

            <a href="<?= htmlspecialchars($basePath ?? '') ?>places.php">
                Places
            </a>

            <a href="<?= htmlspecialchars($basePath ?? '') ?>planner.php">
                Plan Your Trip
                <span class="nav-count">
                    <?= $planCount ?>
                </span>
            </a>

            <a href="<?= htmlspecialchars($basePath ?? '') ?>index.php#about">
                About
            </a>

        </nav>


        <!-- USER / LOGIN -->

        <div class="nav-user">

            <?php if (!empty($_SESSION['admin_id'])): ?>

                <a
                    class="user-pill"
                    href="<?= htmlspecialchars($basePath ?? '') ?>admin/dashboard.php"
                >
                    <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
                </a>

            <?php else: ?>

                <a
                    class="login-pill"
                    href="<?= htmlspecialchars($basePath ?? '') ?>admin/login.php"
                >
                    Login
                </a>

            <?php endif; ?>

        </div>

    </div>

</header>

<main>