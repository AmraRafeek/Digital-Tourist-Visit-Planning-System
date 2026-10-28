<?php

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Place Details';
$basePath  = '';


// ======================================================
// GET PLACE ID
// ======================================================

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: places.php');
    exit;
}


// ======================================================
// GET PLACE
// ======================================================

$stmt = $pdo->prepare("
    SELECT
        p.*,
        c.name AS category_name
    FROM places p
    INNER JOIN categories c
        ON c.id = p.category_id
    WHERE p.id = ?
      AND p.status = 1
    LIMIT 1
");

$stmt->execute([$id]);

$place = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$place) {
    include __DIR__ . '/includes/header.php';
    ?>

    <section class="section">
        <div class="container">

            <div class="place-not-found">

                <h2>Place not found</h2>

                <p>
                    The destination you are looking for is not available.
                </p>

                <a href="places.php" class="btn">
                    Back to Places
                </a>

            </div>

        </div>
    </section>

    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}


// ======================================================
// HELPER
// ======================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ======================================================
// PLACE VALUES
// ======================================================

$name = $place['name'] ?? '';

$categoryName =
    $place['category_name'] ?? '';

$description =
    $place['description'] ?? '';

$openingHours =
    $place['opening_hours'] ?? '';

$distance =
    $place['distance'] ?? '';

$facilities =
    $place['facilities'] ?? '';

$travelTips =
    $place['travel_tips'] ?? '';

$entranceFee =
    $place['entrance_fee'] ?? '';

$mainImage =
    trim($place['image'] ?? '');

$mapsUrl =
    trim($place['maps_url'] ?? '');

$mapsEmbed =
    trim($place['maps_embed'] ?? '');


// ======================================================
// ADDITIONAL PHOTOS
// ======================================================

$additionalPhotos = [];

try {

    $photoStmt = $pdo->prepare("
        SELECT *
        FROM place_images
        WHERE place_id = ?
        ORDER BY id ASC
    ");

    $photoStmt->execute([$id]);

    $additionalPhotos =
        $photoStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $additionalPhotos = [];

}


// ======================================================
// BUILD PHOTO LIST
// ======================================================

$photos = [];


// Main image

if ($mainImage !== '') {

    $photos[] = [
        'src' => $mainImage,
        'alt' => $name
    ];

}


// Additional images

foreach ($additionalPhotos as $photo) {

    $photoPath = '';


    if (!empty($photo['image'])) {

        $photoPath = $photo['image'];

    } elseif (!empty($photo['image_path'])) {

        $photoPath = $photo['image_path'];

    } elseif (!empty($photo['photo'])) {

        $photoPath = $photo['photo'];

    } elseif (!empty($photo['filename'])) {

        $photoPath = $photo['filename'];

    }


    if ($photoPath !== '') {

        $photos[] = [
            'src' => $photoPath,
            'alt' => $name
        ];

    }

}


// ======================================================
// CATEGORY VISUAL
// ======================================================

function getPlaceVisualClass($category)
{
    $category = strtolower($category);

    if (strpos($category, 'historical') !== false) {
        return 'visual-historical';
    }

    if (strpos($category, 'religious') !== false) {
        return 'visual-religious';
    }

    if (strpos($category, 'restaurant') !== false) {
        return 'visual-dining';
    }

    if (strpos($category, 'public') !== false) {
        return 'visual-public';
    }

    return 'visual-nature';
}


$visualClass =
    getPlaceVisualClass($categoryName);


// ======================================================
// HEADER
// ======================================================

include __DIR__ . '/includes/header.php';

?>

<section class="section place-details-page">

    <div class="container">


        <!-- ==================================================
             BREADCRUMB
             ================================================== -->

        <div class="place-breadcrumb">

            <a href="places.php">
                Places
            </a>

            <span>›</span>

            <span>
                <?= e($name) ?>
            </span>

        </div>


        <!-- ==================================================
             MAIN PLACE CARD
             ================================================== -->

        <div class="place-main-card">


            <!-- IMAGE -->

            <div
                class="place-main-image <?= e($visualClass) ?>"
                <?php if ($mainImage !== ''): ?>

                    style="
                        background-image:
                        url('<?= e($mainImage) ?>');
                    "

                <?php endif; ?>
            >

                <?php if ($mainImage !== ''): ?>

                    <button
                        type="button"
                        class="main-image-expand"
                        onclick="openPhotoViewer(
                            <?= htmlspecialchars(
                                json_encode($mainImage),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        )"
                        aria-label="View main photo"
                    >
                        ⛶
                    </button>

                <?php endif; ?>

            </div>


            <!-- INFORMATION -->

            <div class="place-main-content">


                <div class="place-category">

                    <span class="category-dot"></span>

                    <?= e($categoryName) ?>

                </div>


                <h1>
                    <?= e($name) ?>
                </h1>


                <?php if ($description !== ''): ?>

                    <p class="place-description">

                        <?= nl2br(e($description)) ?>

                    </p>

                <?php endif; ?>


                <!-- QUICK INFORMATION -->

                <div class="place-info-grid">


                    <?php if ($openingHours !== ''): ?>

                        <div class="place-info-box">

                            <span class="info-label">
                                Opening Hours
                            </span>

                            <strong>
                                <?= e($openingHours) ?>
                            </strong>

                        </div>

                    <?php endif; ?>


                    <?php if ($distance !== ''): ?>

                        <div class="place-info-box">

                            <span class="info-label">
                                Distance
                            </span>

                            <strong>
                                <?= e($distance) ?> km
                            </strong>

                        </div>

                    <?php endif; ?>


                </div>


                <!-- ACTIONS -->

                <div class="place-actions">


                    <?php if ($mapsUrl !== ''): ?>

                        <a
                            href="<?= e($mapsUrl) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn"
                        >
                            View on Google Maps
                        </a>



                    <?php endif; ?>


                    <a
                        href="planner.php?add=<?= (int)$id ?>"
                        class="btn"
                    >
                        + Add to Plan
                    </a>


                </div>

            </div>

        </div>


        <!-- ==================================================
             ADDITIONAL INFORMATION
             ================================================== -->

        <?php if (
            $travelTips !== '' ||
            $entranceFee !== ''
        ): ?>

            <div class="place-extra-grid">


                <?php if ($travelTips !== ''): ?>

                    <div class="place-extra-card">

                        <h3>
                            Travel Tips
                        </h3>

                        <p>
                            <?= nl2br(e($travelTips)) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <?php if ($entranceFee !== ''): ?>

                    <div class="place-extra-card">

                        <h3>
                            Entrance Fee
                        </h3>

                        <p>
                            <?= e($entranceFee) ?>
                        </p>

                    </div>

                <?php endif; ?>


            </div>

        <?php endif; ?>


        <!-- ==================================================
             PHOTOS
             ================================================== -->

        <?php if (!empty($photos)): ?>

            <section class="place-section">


                <div class="place-section-heading">

                    <div>

                        <span class="section-kicker">
                            GALLERY
                        </span>

                        <h2>
                            Photos
                        </h2>

                    </div>

                    <span class="photo-count">

                        <?= count($photos) ?>

                        <?= count($photos) === 1
                            ? 'Photo'
                            : 'Photos'
                        ?>

                    </span>

                </div>


                <div class="photo-gallery">


                    <?php foreach (
                        $photos as $index => $photo
                    ): ?>

                        <button
                            type="button"
                            class="photo-card"
                            onclick="openPhotoViewer(
                                <?= htmlspecialchars(
                                    json_encode($photo['src']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>,
                                <?= (int)$index ?>
                            )"
                        >

                            <img
                                src="<?= e($photo['src']) ?>"
                                alt="<?= e($photo['alt']) ?>"
                                loading="lazy"
                            >

                        </button>

                    <?php endforeach; ?>


                </div>

            </section>

        <?php endif; ?>


        <!-- ==================================================
             LOCATION
             ================================================== -->

        <section class="place-section location-section">


            <div class="place-section-heading">

                <div>

                    <span class="section-kicker">
                        LOCATION
                    </span>

                    <h2>
                        Location
                    </h2>

                </div>

            </div>


            <?php if ($mapsEmbed !== ''): ?>

                <div class="map-card">

                    <iframe
                        src="<?= e($mapsEmbed) ?>"
                        title="Map showing <?= e($name) ?>"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>

                </div>


            <?php elseif ($mapsUrl !== ''): ?>

                <div class="map-card">

                    <iframe
                        src="https://www.google.com/maps?q=<?= urlencode($mapsUrl) ?>&output=embed"
                        title="Map showing <?= e($name) ?>"
                        loading="lazy"
                        allowfullscreen
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>

                </div>


            <?php else: ?>

                <div class="map-empty">

                    Location map is not available.

                </div>

            <?php endif; ?>


        </section>


    </div>

</section>


<!-- ======================================================
     PHOTO LIGHTBOX
     ====================================================== -->

<div
    id="photoViewer"
    class="photo-viewer"
    aria-hidden="true"
>


    <button
        type="button"
        class="photo-viewer-close"
        onclick="closePhotoViewer()"
        aria-label="Close"
    >
        ×
    </button>


    <button
        type="button"
        class="photo-viewer-arrow photo-prev"
        onclick="previousPhoto(event)"
        aria-label="Previous photo"
    >
        ‹
    </button>


    <div
        class="photo-viewer-content"
        onclick="event.stopPropagation()"
    >

        <img
            id="photoViewerImage"
            src=""
            alt=""
        >

    </div>


    <button
        type="button"
        class="photo-viewer-arrow photo-next"
        onclick="nextPhoto(event)"
        aria-label="Next photo"
    >
        ›
    </button>


</div>


<script>


// ======================================================
// PHOTO DATA
// ======================================================

const placePhotos = <?= json_encode(
    array_column($photos, 'src'),
    JSON_UNESCAPED_SLASHES
) ?>;

let currentPhotoIndex = 0;


// ======================================================
// OPEN PHOTO
// ======================================================

function openPhotoViewer(
    imageUrl,
    index = 0
) {

    const viewer =
        document.getElementById('photoViewer');

    const image =
        document.getElementById('photoViewerImage');


    currentPhotoIndex =
        Number(index) || 0;


    image.src = imageUrl;


    viewer.classList.add('active');

    viewer.setAttribute(
        'aria-hidden',
        'false'
    );


    document.body.classList.add(
        'photo-viewer-open'
    );

}


// ======================================================
// CLOSE PHOTO
// ======================================================

function closePhotoViewer()
{

    const viewer =
        document.getElementById('photoViewer');

    const image =
        document.getElementById('photoViewerImage');


    viewer.classList.remove('active');

    viewer.setAttribute(
        'aria-hidden',
        'true'
    );


    image.src = '';

    document.body.classList.remove(
        'photo-viewer-open'
    );

}


// ======================================================
// SHOW PHOTO
// ======================================================

function showPhoto(index)
{

    if (!placePhotos.length) {
        return;
    }


    if (index < 0) {

        index =
            placePhotos.length - 1;

    }


    if (
        index >=
        placePhotos.length
    ) {

        index = 0;

    }


    currentPhotoIndex = index;


    const image =
        document.getElementById(
            'photoViewerImage'
        );


    image.src =
        placePhotos[currentPhotoIndex];

}


// ======================================================
// PREVIOUS
// ======================================================

function previousPhoto(event)
{

    event.stopPropagation();

    showPhoto(
        currentPhotoIndex - 1
    );

}


// ======================================================
// NEXT
// ======================================================

function nextPhoto(event)
{

    event.stopPropagation();

    showPhoto(
        currentPhotoIndex + 1
    );

}


// ======================================================
// KEYBOARD CONTROLS
// ======================================================

document.addEventListener(
    'keydown',
    function(event)
    {

        const viewer =
            document.getElementById(
                'photoViewer'
            );


        if (
            !viewer.classList.contains(
                'active'
            )
        ) {

            return;

        }


        if (event.key === 'Escape') {

            closePhotoViewer();

        }


        if (event.key === 'ArrowLeft') {

            showPhoto(
                currentPhotoIndex - 1
            );

        }


        if (event.key === 'ArrowRight') {

            showPhoto(
                currentPhotoIndex + 1
            );

        }

    }
);


// ======================================================
// CLICK BACKDROP TO CLOSE
// ======================================================

document
    .getElementById('photoViewer')
    .addEventListener(
        'click',
        function(event)
        {

            if (
                event.target === this
            ) {

                closePhotoViewer();

            }

        }
    );

</script>


<style>

/* ======================================================
   PLACE DETAILS
   ====================================================== */

.place-details-page {
    padding-top: 30px;
}


/* ======================================================
   BREADCRUMB
   ====================================================== */

.place-breadcrumb {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 18px;

    font-size: 14px;

    color: var(--muted, #6b7280);

}

.place-breadcrumb a {

    color: var(--text, #111827);

    text-decoration: none;

    font-weight: 600;

}

.place-breadcrumb a:hover {
    text-decoration: underline;
}


/* ======================================================
   MAIN CARD
   ====================================================== */

.place-main-card {

    display: grid;

    grid-template-columns:
        minmax(300px, 1fr)
        minmax(400px, 1.1fr);

    overflow: hidden;

    background: #fff;

    border: 1px solid #e5e7eb;

    border-radius: 22px;

    box-shadow:
        0 10px 35px
        rgba(0, 0, 0, .06);

}


.place-main-image {

    min-height: 430px;

    position: relative;

    background-position: center;

    background-size: cover;

    background-repeat: no-repeat;

}


.main-image-expand {

    position: absolute;

    right: 18px;

    bottom: 18px;

    width: 42px;

    height: 42px;

    border: 0;

    border-radius: 50%;

    background:
        rgba(0, 0, 0, .58);

    color: #fff;

    cursor: pointer;

    font-size: 19px;

}


.main-image-expand:hover {
    background: rgba(0, 0, 0, .75);
}


/* ======================================================
   CONTENT
   ====================================================== */

.place-main-content {

    padding: 38px;

}


.place-category {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 12px;

    font-size: 13px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .05em;

    color: #15803d;

}


.category-dot {

    width: 8px;

    height: 8px;

    border-radius: 50%;

    background: #16a34a;

}


.place-main-content h1 {

    margin: 0 0 18px;

    font-size: clamp(
        30px,
        4vw,
        46px
    );

    line-height: 1.08;

    color: #111827;

}


.place-description {

    margin: 0;

    line-height: 1.75;

    color: #4b5563;

}


/* ======================================================
   INFO
   ====================================================== */

.place-info-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 12px;

    margin-top: 25px;

}


.place-info-box {

    padding: 15px;

    border-radius: 13px;

    background: #f8fafc;

    border: 1px solid #eef2f7;

}


.info-label {

    display: block;

    margin-bottom: 5px;

    color: #6b7280;

    font-size: 12px;

    font-weight: 600;

}


.place-info-box strong {

    color: #111827;

    font-size: 14px;

}


/* ======================================================
   BUTTONS
   ====================================================== */

.place-actions {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-top: 26px;

}


/* ======================================================
   EXTRA INFORMATION
   ====================================================== */

.place-extra-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px;

    margin-top: 24px;

}


.place-extra-card {

    padding: 24px;

    border-radius: 16px;

    background: #fff;

    border: 1px solid #e5e7eb;

}


.place-extra-card h3 {

    margin: 0 0 10px;

    font-size: 18px;

}


.place-extra-card p {

    margin: 0;

    color: #4b5563;

    line-height: 1.7;

}


/* ======================================================
   SECTIONS
   ====================================================== */

.place-section {

    margin-top: 42px;

}


.place-section-heading {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 17px;

}


.section-kicker {

    display: block;

    margin-bottom: 4px;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: .12em;

    color: #16a34a;

}


.place-section-heading h2 {

    margin: 0;

    font-size: 28px;

    color: #111827;

}


.photo-count {

    color: #6b7280;

    font-size: 13px;

}


/* ======================================================
   PHOTO GALLERY
   ====================================================== */

.photo-gallery {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 14px;

}


.photo-card {

    position: relative;

    display: block;

    padding: 0;

    width: 100%;

    height: 230px;

    overflow: hidden;

    border: 0;

    border-radius: 16px;

    background: #f3f4f6;

    cursor: zoom-in;

}


.photo-card img {

    width: 100%;

    height: 100%;

    display: block;

    object-fit: cover;

    transition:
        transform .3s ease,
        filter .3s ease;

}


.photo-card:hover img {

    transform: scale(1.06);

    filter: brightness(.92);

}


/* ======================================================
   MAP
   ====================================================== */

.map-card {

    overflow: hidden;

    border-radius: 18px;

    border: 1px solid #e5e7eb;

    background: #f3f4f6;

    box-shadow:
        0 8px 25px
        rgba(0, 0, 0, .05);

}


.map-card iframe {

    display: block;

    width: 100%;

    height: 430px;

    border: 0;

}


.map-empty {

    padding: 45px;

    text-align: center;

    border-radius: 16px;

    border: 1px solid #e5e7eb;

    background: #f8fafc;

    color: #6b7280;

}


/* ======================================================
   LIGHTBOX
   ====================================================== */

.photo-viewer {

    position: fixed;

    inset: 0;

    z-index: 99999;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px;

    background:
        rgba(0, 0, 0, .92);

    opacity: 0;

    visibility: hidden;

    pointer-events: none;

    transition:
        opacity .2s ease,
        visibility .2s ease;

}


.photo-viewer.active {

    opacity: 1;

    visibility: visible;

    pointer-events: auto;

}


.photo-viewer-content {

    display: flex;

    align-items: center;

    justify-content: center;

    max-width: 90vw;

    max-height: 90vh;

}


.photo-viewer-content img {

    display: block;

    max-width: 88vw;

    max-height: 88vh;

    width: auto;

    height: auto;

    object-fit: contain;

    border-radius: 8px;

    box-shadow:
        0 20px 70px
        rgba(0, 0, 0, .55);

}


.photo-viewer-close {

    position: absolute;

    top: 20px;

    right: 25px;

    z-index: 3;

    width: 46px;

    height: 46px;

    padding: 0;

    border: 0;

    border-radius: 50%;

    background:
        rgba(255, 255, 255, .14);

    color: #fff;

    font-size: 32px;

    line-height: 42px;

    cursor: pointer;

}


.photo-viewer-close:hover {

    background:
        rgba(255, 255, 255, .25);

}


.photo-viewer-arrow {

    position: absolute;

    top: 50%;

    z-index: 3;

    width: 48px;

    height: 48px;

    border: 0;

    border-radius: 50%;

    background:
        rgba(255, 255, 255, .14);

    color: #fff;

    font-size: 38px;

    line-height: 38px;

    cursor: pointer;

    transform: translateY(-50%);

}


.photo-viewer-arrow:hover {

    background:
        rgba(255, 255, 255, .25);

}


.photo-prev {
    left: 24px;
}


.photo-next {
    right: 24px;
}


/* ======================================================
   BODY WHEN LIGHTBOX IS OPEN
   ====================================================== */

body.photo-viewer-open {

    overflow: hidden;

}


/* ======================================================
   NOT FOUND
   ====================================================== */

.place-not-found {

    padding: 60px 25px;

    text-align: center;

    border-radius: 18px;

    border: 1px solid #e5e7eb;

    background: #fff;

}


.place-not-found h2 {

    margin-top: 0;

}


/* ======================================================
   RESPONSIVE
   ====================================================== */

@media (max-width: 900px) {

    .place-main-card {

        grid-template-columns: 1fr;

    }


    .place-main-image {

        min-height: 350px;

    }


    .photo-gallery {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }

}


@media (max-width: 600px) {

    .place-main-content {

        padding: 25px;

    }


    .place-info-grid {

        grid-template-columns: 1fr;

    }


    .place-extra-grid {

        grid-template-columns: 1fr;

    }


    .photo-gallery {

        grid-template-columns: 1fr;

    }


    .photo-card {

        height: 250px;

    }


    .map-card iframe {

        height: 350px;

    }


    .photo-viewer {

        padding: 15px;

    }


    .photo-viewer-content img {

        max-width: 94vw;

        max-height: 82vh;

    }


    .photo-viewer-arrow {

        width: 40px;

        height: 40px;

        font-size: 30px;

    }


    .photo-prev {

        left: 10px;

    }


    .photo-next {

        right: 10px;

    }

}

</style>


<?php

include __DIR__ . '/includes/footer.php';

?>
