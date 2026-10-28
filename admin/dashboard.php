<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Admin Dashboard';
$basePath = '../';

// ==================================================
// DASHBOARD STATISTICS
// ==================================================

$total = (int) $pdo
    ->query('SELECT COUNT(*) FROM places')
    ->fetchColumn();

$cats = (int) $pdo
    ->query('SELECT COUNT(*) FROM categories')
    ->fetchColumn();

$active = (int) $pdo
    ->query('SELECT COUNT(*) FROM places WHERE status = 1')
    ->fetchColumn();


// ==================================================
// RECENT PLACES
// ==================================================

$recent = $pdo
    ->query("
        SELECT
            p.*,
            c.name AS category_name
        FROM places p
        JOIN categories c
            ON p.category_id = c.id
        ORDER BY p.id DESC
        LIMIT 6
    ")
    ->fetchAll();


// ==================================================
// HEADER
// ==================================================

include __DIR__ . '/../includes/header.php';

?>

<section class="admin-shell">

    <div class="container admin-layout">

        <!-- ================================
             ADMIN SIDEBAR
        ================================= -->

        <aside class="admin-sidebar">

            <div class="admin-brand">
                Digital Tourist
                <br>

                <small style="opacity: .65; font-weight: 400;">
                    Admin Panel
                </small>
            </div>

            <a
                class="active"
                href="dashboard.php"
            >
                ⌂ Dashboard
            </a>

            <a href="add-place.php">
                ＋ Add Place
            </a>

            <a href="places.php">
                📍 Manage Places
            </a>

            <a href="../places.php">
                ↗ View Website
            </a>

            <a href="logout.php">
                ⇥ Logout
            </a>

        </aside>


        <!-- ================================
             MAIN CONTENT
        ================================= -->

        <main class="admin-main">

            <!-- TOP BAR -->

            <div class="admin-top">

                <div>

                    <h1>
                        Admin Dashboard
                    </h1>

                    <p
                        style="
                            font-size: 11px;
                            color: var(--muted);
                        "
                    >
                        Welcome,
                        <?= htmlspecialchars(
                            $_SESSION['admin_username'] ?? 'Admin'
                        ) ?>.
                    </p>

                </div>

                <a
                    class="btn"
                    href="add-place.php"
                >
                    ＋ Add Place
                </a>

            </div>


            <!-- ================================
                 STATISTICS
            ================================= -->

            <div class="stat-grid">

                <div class="stat">

                    <span class="stat-label">
                        Total Places
                    </span>

                    <strong>
                        <?= $total ?>
                    </strong>

                </div>


                <div class="stat">

                    <span class="stat-label">
                        Total Categories
                    </span>

                    <strong>
                        <?= $cats ?>
                    </strong>

                </div>


                <div class="stat">

                    <span class="stat-label">
                        Active Places
                    </span>

                    <strong>
                        <?= $active ?>
                    </strong>

                </div>

            </div>


            <!-- ================================
                 RECENT PLACES HEADER
            ================================= -->

            <div class="section-head">

                <div>

                    <h2>
                        Recent Places
                    </h2>

                    <p>
                        Latest destination records in the system.
                    </p>

                </div>

                <a
                    class="text-link"
                    href="places.php"
                >
                    Manage all →
                </a>

            </div>


            <!-- ================================
                 RECENT PLACES TABLE
            ================================= -->

            <div class="table-wrap">

                <table class="table">

                    <thead>

                        <tr>

                            <th>
                                Name
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Distance
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($recent as $p): ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $p['name']
                                        ) ?>
                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $p['category_name']
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $p['distance']
                                    ) ?>

                                    km

                                </td>


                                <td class="status">

                                    <?= $p['status']
                                        ? 'Active'
                                        : 'Inactive'
                                    ?>

                                </td>


                                <td>

                                    <a
                                        class="text-link"
                                        href="edit-place.php?id=<?= (int) $p['id'] ?>"
                                    >
                                        Edit
                                    </a>

                                    ·

                                    <a
                                        class="text-link"
                                        href="delete-place.php?id=<?= (int) $p['id'] ?>"
                                        onclick="return confirmDelete(
                                            '<?= htmlspecialchars(
                                                $p['name'],
                                                ENT_QUOTES
                                            ) ?>'
                                        )"
                                    >
                                        Delete
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </main>

    </div>

</section>


<script src="../assets/js/script.js"></script>

<?php

include __DIR__ . '/../includes/footer.php';

?>
