<?php
require_once __DIR__.'/config/database.php';

$pageTitle = 'Places Near Maradankadawala';
$basePath = '';

$q = trim($_GET['q'] ?? '');
$category = (int)($_GET['category'] ?? 0);

$categories = $pdo->query(
    'SELECT * FROM categories ORDER BY name'
)->fetchAll();

$sql = "
    SELECT p.*, c.name AS category_name
    FROM places p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1
";

$params = [];

if ($q !== '') {

    $sql .= "
        AND (
            p.name LIKE ?
            OR p.description LIKE ?
        )
    ";

    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($category > 0) {

    $sql .= " AND p.category_id = ?";

    $params[] = $category;
}

$sql .= " ORDER BY p.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$places = $stmt->fetchAll();

include __DIR__.'/includes/header.php';


// ==================================================
// CATEGORY-BASED FALLBACK IMAGE
// ==================================================

function placeVisualClass($category)
{
    $c = strtolower($category);

    if (strpos($c, 'historical') !== false) {
        return 'visual-historical';
    }

    if (strpos($c, 'religious') !== false) {
        return 'visual-religious';
    }

    if (strpos($c, 'restaurant') !== false) {
        return 'visual-dining';
    }

    if (strpos($c, 'public') !== false) {
        return 'visual-public';
    }

    return 'visual-nature';
}

?>

<section class="section">

    <div class="container">


        <!-- PAGE TITLE -->

        <div class="page-title">

            <h1>
                Places Near Maradankadawala
            </h1>

            <p>
                Browse selected tourist and useful local destinations
                within the project area.
            </p>

        </div>


        <!-- SEARCH AND FILTER -->

        <form
            class="toolbar"
            method="get"
        >

            <input
                class="grow"
                name="q"
                value="<?= htmlspecialchars($q) ?>"
                placeholder="Search places..."
            >


            <select name="category">

                <option value="0">
                    All Categories
                </option>

                <?php foreach ($categories as $c): ?>

                    <option
                        value="<?= $c['id'] ?>"
                        <?= $category === (int)$c['id'] ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($c['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>


            <button
                class="btn"
                type="submit"
            >
                Search
            </button>


            <a
                class="btn secondary"
                href="places.php"
            >
                Clear
            </a>

        </form>


        <!-- PLACES -->

        <?php if (!$places): ?>

            <div class="empty">

                No destinations match your search or filter.

            </div>

        <?php else: ?>

            <div class="cards">

                <?php foreach ($places as $p): ?>

                    <?php

                    /*
                     * If the administrator uploaded an image,
                     * use that image.
                     *
                     * If there is no uploaded image,
                     * use the category-based CSS image.
                     */

                    $imageStyle = '';

                    if (!empty($p['image'])) {

                        $imagePath = htmlspecialchars(
                            $p['image'],
                            ENT_QUOTES,
                            'UTF-8'
                        );

                        $imageStyle =
                            "background-image:url('" .
                            $imagePath .
                            "');";
                    }

                    ?>


                    <article class="card place-card">


                        <!-- PLACE IMAGE -->

                        <div
                            class="place-visual <?= placeVisualClass($p['category_name']) ?>"
                            <?php if ($imageStyle): ?>
                                style="<?= $imageStyle ?>"
                            <?php endif; ?>
                        ></div>


                        <!-- PLACE INFORMATION -->

                        <div class="place-body">


                            <!-- CATEGORY AND DISTANCE -->

                            <div class="place-meta">

                                <span class="tag">

                                    ● <?= htmlspecialchars(
                                        $p['category_name']
                                    ) ?>

                                </span>

                                <span class="distance">

                                    📍 <?= htmlspecialchars(
                                        $p['distance']
                                    ) ?> km

                                </span>

                            </div>


                            <!-- PLACE NAME -->

                            <h3>

                                <?= htmlspecialchars(
                                    $p['name']
                                ) ?>

                            </h3>


                            <!-- DESCRIPTION -->

                            <p>

                                <?= htmlspecialchars(
                                    mb_strimwidth(
                                        $p['description'],
                                        0,
                                        115,
                                        '…'
                                    )
                                ) ?>

                            </p>


                            <!-- VIEW DETAILS -->

                            <a
                                class="btn small"
                                href="place-details.php?id=<?= (int)$p['id'] ?>"
                            >
                                View Details
                            </a>


                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


    </div>

</section>


<?php

include __DIR__.'/includes/footer.php';

?>