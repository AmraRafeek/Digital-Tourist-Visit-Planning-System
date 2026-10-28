<?php
require_once __DIR__.'/config/database.php';

$pageTitle = 'Digital Tourist Visit Planning System';
$basePath = '';

// Get latest 6 active places
$stmt = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM places p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 1
    ORDER BY p.id DESC
    LIMIT 6
");

$places = $stmt->fetchAll();

// Get categories
$categories = $pdo->query(
    'SELECT * FROM categories ORDER BY name'
)->fetchAll();

include __DIR__.'/includes/header.php';

// Default category image class
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

<!-- HERO SECTION -->
<section class="hero">

    <div class="container hero-content">

        <div class="eyebrow">
            Explore Maradankadawala
        </div>

        <h1>
            Explore Local Treasures<br>
            Near Maradankadawala
        </h1>

        <p>
            Discover historical places, religious sites, natural beauty,
            recreational spots and more — all within 25 km.
        </p>

        <form
            class="hero-search"
            action="places.php"
            method="get"
        >

            <input
                name="q"
                placeholder="Search places..."
                aria-label="Search places"
            >

            <select name="category">

                <option value="0">
                    All Categories
                </option>

                <?php foreach ($categories as $c): ?>

                    <option value="<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button class="search-btn">
                Search
            </button>

        </form>

    </div>

</section>


<!-- CATEGORY SECTION -->
<section class="section" id="about">

    <div class="container">

        <div class="section-head">

            <div>

                <h2>
                    Explore by Category
                </h2>

                <p>
                    Find a place that matches your interests.
                </p>

            </div>

        </div>


        <div class="category-grid">

            <?php

            $icons = [
                'Religious'    => '🕌',
                'Historical'   => '🏛️',
                'Nature'       => '🍃',
                'Recreational' => '⛰️',
                'Restaurant'   => '🍴',
                'Public Place' => '🏙️'
            ];

            foreach ($categories as $c):

                $icon = $icons[$c['name']] ?? '📍';

                $slug = strtolower(
                    str_replace(' ', '-', $c['name'])
                );

            ?>

                <a
                    class="category-card"
                    href="places.php?category=<?= $c['id'] ?>"
                >

                    <div
                        class="category-icon cat-<?= htmlspecialchars($slug) ?>"
                    >
                        <?= $icon ?>
                    </div>

                    <strong>
                        <?= htmlspecialchars($c['name']) ?>
                    </strong>

                    <span>
                        Explore places
                    </span>

                </a>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<!-- FEATURED PLACES -->
<section class="section section-tight">

    <div class="container">

        <div class="section-head">

            <div>

                <h2>
                    Featured Places
                </h2>

                <p>
                    Selected destinations around the project area.
                </p>

            </div>

            <a
                class="text-link"
                href="places.php"
            >
                View all places →
            </a>

        </div>


        <div class="cards">

            <?php foreach ($places as $p): ?>

                <article class="card place-card">

                    <?php
                    // Use image uploaded from Admin.
                    // If no uploaded image exists,
                    // the category CSS image will be used.
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

                    <div
                        class="place-visual <?= placeVisualClass($p['category_name']) ?>"
                        <?php if ($imageStyle): ?>
                            style="<?= $imageStyle ?>"
                        <?php endif; ?>
                    ></div>


                    <div class="place-body">

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


                        <h3>
                            <?= htmlspecialchars($p['name']) ?>
                        </h3>


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


                        <a
                            class="btn small"
                            href="place-details.php?id=<?= $p['id'] ?>"
                        >
                            View Details
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </div>

</section>


<?php
include __DIR__.'/includes/footer.php';
?>