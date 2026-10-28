<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $u = trim($_POST['username'] ?? '');
    $pw = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT * FROM admin WHERE username = ?'
    );

    $stmt->execute([$u]);

    $admin = $stmt->fetch();

    if (
        $admin &&
        password_verify($pw, $admin['password'])
    ) {

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        header('Location: dashboard.php');
        exit;
    }

    $error = 'Invalid username or password.';
}

$pageTitle = 'Admin Login';
$basePath = '../';

include __DIR__ . '/../includes/header.php';

?>

<section class="admin-login">

    <div class="login-card">

        <div
            class="eyebrow"
            style="color:var(--teal)"
        >
            ADMIN AREA
        </div>

        <h1>
            Welcome back
        </h1>

        <p>
            Sign in to manage tourist destinations and website content.
        </p>


        <?php if ($error): ?>

            <div class="notice error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form method="post">

            <div class="form-group">

                <label>
                    Username
                </label>

                <input
                    class="form-control"
                    name="username"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    class="form-control"
                    type="password"
                    name="password"
                    required
                >

            </div>


            <button
                class="btn"
                style="width:100%"
                type="submit"
            >
                Login to Admin Panel
            </button>

        </form>

    </div>

</section>


<?php

include __DIR__ . '/../includes/footer.php';

?>
