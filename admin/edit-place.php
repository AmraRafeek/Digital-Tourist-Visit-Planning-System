<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| GET PLACE ID
|--------------------------------------------------------------------------
*/

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: places.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET PLACE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT *
     FROM places
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([$id]);

$place = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$place) {
    header('Location: places.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = 'Edit Place';
$basePath = '../';

/*
|--------------------------------------------------------------------------
| GET CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = $pdo->query(
    "SELECT *
     FROM categories
     ORDER BY name"
)->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = '';

$openingTime = '';
$closingTime = '';

$openingHours = $place['opening_hours'] ?? '';

if ($openingHours !== '') {
    $parts = preg_split(
        '/\s*-\s*/',
        $openingHours,
        2
    );

    if (count($parts) === 2) {
        $openingTime = trim($parts[0]);
        $closingTime = trim($parts[1]);
    } else {
        $openingTime = trim($openingHours);
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE PLACE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /*
        |--------------------------------------------------------------------------
        | BASIC VALUES
        |--------------------------------------------------------------------------
        */

        $name = trim(
            $_POST['name'] ?? ''
        );

        $categoryId = (int) (
            $_POST['category_id'] ?? 0
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $distance = trim(
            $_POST['distance'] ?? ''
        );

        $openingTime = trim(
            $_POST['opening_time'] ?? ''
        );

        $closingTime = trim(
            $_POST['closing_time'] ?? ''
        );

        $entranceFee = trim(
            $_POST['entrance_fee'] ?? ''
        );

        $travelTips = trim(
            $_POST['travel_tips'] ?? ''
        );

        $mapsUrl = trim(
            $_POST['maps_url'] ?? ''
        );

        $mapsEmbed = trim(
            $_POST['maps_embed'] ?? ''
        );

        $status = isset($_POST['status'])
            ? (int) $_POST['status']
            : 1;

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($name === '') {
            throw new Exception(
                'Please enter the place name.'
            );
        }

        if ($categoryId <= 0) {
            throw new Exception(
                'Please select a category.'
            );
        }

        if ($description === '') {
            throw new Exception(
                'Please enter a description.'
            );
        }

        if ($mapsUrl === '') {
            throw new Exception(
                'Please enter the Google Maps location.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DISTANCE
        |--------------------------------------------------------------------------
        */

        $distanceValue = null;

        if ($distance !== '') {

            if (
                !is_numeric($distance) ||
                (float) $distance < 0
            ) {
                throw new Exception(
                    'Distance must be a valid number.'
                );
            }

            $distanceValue = (float) $distance;
        }

        /*
        |--------------------------------------------------------------------------
        | OPENING HOURS
        |--------------------------------------------------------------------------
        */

        $openingHours = '';

        if (
            $openingTime !== '' &&
            $closingTime !== ''
        ) {

            $openingHours =
                $openingTime .
                ' - ' .
                $closingTime;

        } elseif ($openingTime !== '') {

            $openingHours = $openingTime;

        } elseif ($closingTime !== '') {

            $openingHours = $closingTime;
        }

        /*
        |--------------------------------------------------------------------------
        | ENTRANCE FEE
        |--------------------------------------------------------------------------
        */

        $entranceFeeValue = null;

        if ($entranceFee !== '') {

            if (
                !is_numeric($entranceFee) ||
                (float) $entranceFee < 0
            ) {
                throw new Exception(
                    'Entrance fee must be a valid number.'
                );
            }

            $entranceFeeValue =
                (float) $entranceFee;
        }

        /*
        |--------------------------------------------------------------------------
        | KEEP CURRENT MAIN IMAGE
        |--------------------------------------------------------------------------
        */

        $imagePath = $place['image'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | DATABASE TRANSACTION
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | UPDATE PLACE INFORMATION
        |--------------------------------------------------------------------------
        */

        $update = $pdo->prepare(
            "UPDATE places
             SET
                name = ?,
                category_id = ?,
                description = ?,
                distance = ?,
                opening_hours = ?,
                entrance_fee = ?,
                travel_tips = ?,
                maps_url = ?,
                maps_embed = ?,
                status = ?
             WHERE id = ?"
        );

        $update->execute([
            $name,
            $categoryId,
            $description,
            $distanceValue,
            $openingHours,
            $entranceFeeValue,
            $travelTips,
            $mapsUrl,
            $mapsEmbed,
            $status,
            $id
        ]);

        /*
        |--------------------------------------------------------------------------
        | MAIN IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            isset($_FILES['image_file']) &&
            $_FILES['image_file']['error']
                !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES['image_file']['error']
                !== UPLOAD_ERR_OK
            ) {
                throw new Exception(
                    'There was a problem uploading the main image.'
                );
            }

            if (
                $_FILES['image_file']['size']
                > 5 * 1024 * 1024
            ) {
                throw new Exception(
                    'Main image must be smaller than 5MB.'
                );
            }

            $imageInfo = getimagesize(
                $_FILES['image_file']['tmp_name']
            );

            if ($imageInfo === false) {
                throw new Exception(
                    'Please select a valid image.'
                );
            }

            $allowedTypes = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG  => 'png',
                IMAGETYPE_WEBP => 'webp'
            ];

            $imageType = $imageInfo[2];

            if (!isset($allowedTypes[$imageType])) {
                throw new Exception(
                    'Only JPG, PNG and WEBP images are allowed.'
                );
            }

            $extension =
                $allowedTypes[$imageType];

            $uploadDir =
                __DIR__ .
                '/../uploads/places/';

            if (!is_dir($uploadDir)) {

                if (!mkdir(
                    $uploadDir,
                    0755,
                    true
                )) {
                    throw new Exception(
                        'Unable to create image folder.'
                    );
                }
            }

            $fileName =
                'place_' .
                $id .
                '_' .
                time() .
                '_' .
                bin2hex(random_bytes(5)) .
                '.' .
                $extension;

            $uploadPath =
                $uploadDir .
                $fileName;

            if (
                !move_uploaded_file(
                    $_FILES['image_file']['tmp_name'],
                    $uploadPath
                )
            ) {
                throw new Exception(
                    'Unable to save the main image.'
                );
            }

            $imagePath =
                'uploads/places/' .
                $fileName;

            $imageUpdate = $pdo->prepare(
                "UPDATE places
                 SET image = ?
                 WHERE id = ?"
            );

            $imageUpdate->execute([
                $imagePath,
                $id
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | EXISTING PHOTO VISIBILITY
        |--------------------------------------------------------------------------
        |
        | Checked   = show in Place Details
        | Unchecked = hide from Place Details
        |
        */

        $visibleImages =
            $_POST['visible_images'] ?? [];

        if (!is_array($visibleImages)) {
            $visibleImages = [];
        }

        $visibleImages = array_map(
            'intval',
            $visibleImages
        );

        $photoIdsStmt = $pdo->prepare(
            "SELECT id
             FROM place_images
             WHERE place_id = ?"
        );

        $photoIdsStmt->execute([
            $id
        ]);

        $existingPhotoIds =
            $photoIdsStmt->fetchAll(
                PDO::FETCH_COLUMN
            );

        /*
        |--------------------------------------------------------------------------
        | UPDATE PHOTO VISIBILITY
        |--------------------------------------------------------------------------
        */

        $visibilityStmt = $pdo->prepare(
            "UPDATE place_images
             SET show_in_gallery = ?
             WHERE id = ?
               AND place_id = ?"
        );

        foreach (
            $existingPhotoIds
            as $photoId
        ) {

            $show =
                in_array(
                    (int) $photoId,
                    $visibleImages,
                    true
                )
                ? 1
                : 0;

            $visibilityStmt->execute([
                $show,
                (int) $photoId,
                $id
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE SELECTED PHOTOS
        |--------------------------------------------------------------------------
        */

        $deleteImages =
            $_POST['delete_images'] ?? [];

        if (!is_array($deleteImages)) {
            $deleteImages = [];
        }

        $deleteImages = array_map(
            'intval',
            $deleteImages
        );

        if (!empty($deleteImages)) {

            $findPhoto = $pdo->prepare(
                "SELECT id, image
                 FROM place_images
                 WHERE place_id = ?
                   AND id = ?
                 LIMIT 1"
            );

            $deletePhoto = $pdo->prepare(
                "DELETE FROM place_images
                 WHERE id = ?
                   AND place_id = ?"
            );

            foreach (
                $deleteImages
                as $photoId
            ) {

                $findPhoto->execute([
                    $id,
                    $photoId
                ]);

                $photo =
                    $findPhoto->fetch(
                        PDO::FETCH_ASSOC
                    );

                if (!$photo) {
                    continue;
                }

                $deletePhoto->execute([
                    $photoId,
                    $id
                ]);

                /*
                |--------------------------------------------------------------------------
                | DELETE IMAGE FILE
                |--------------------------------------------------------------------------
                */

                if (!empty($photo['image'])) {

                    $filePath =
                        __DIR__ .
                        '/../' .
                        ltrim(
                            $photo['image'],
                            '/'
                        );

                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ADD NEW ADDITIONAL PHOTOS
        |--------------------------------------------------------------------------
        */

        if (
            isset($_FILES['additional_images']) &&
            isset($_FILES['additional_images']['name']) &&
            is_array(
                $_FILES['additional_images']['name']
            )
        ) {

            $uploadDir =
                __DIR__ .
                '/../uploads/places/';

            if (!is_dir($uploadDir)) {
                mkdir(
                    $uploadDir,
                    0755,
                    true
                );
            }

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = new finfo(
                FILEINFO_MIME_TYPE
            );

            $insertPhoto = $pdo->prepare(
                "INSERT INTO place_images
                (
                    place_id,
                    image,
                    show_in_gallery
                )
                VALUES
                (
                    ?,
                    ?,
                    1
                )"
            );

            foreach (
                $_FILES['additional_images']['name']
                as $key => $originalName
            ) {

                $uploadError =
                    $_FILES['additional_images']['error'][$key];

                if (
                    $uploadError !==
                    UPLOAD_ERR_OK
                ) {
                    continue;
                }

                $tmpName =
                    $_FILES['additional_images']['tmp_name'][$key];

                $fileSize =
                    $_FILES['additional_images']['size'][$key];

                if (
                    $fileSize >
                    5 * 1024 * 1024
                ) {
                    continue;
                }

                $mime =
                    $finfo->file(
                        $tmpName
                    );

                if (
                    !isset(
                        $allowedTypes[$mime]
                    )
                ) {
                    continue;
                }

                $extension =
                    $allowedTypes[$mime];

                $fileName =
                    'place_' .
                    $id .
                    '_' .
                    time() .
                    '_' .
                    bin2hex(
                        random_bytes(5)
                    ) .
                    '.' .
                    $extension;

                $destination =
                    $uploadDir .
                    $fileName;

                if (
                    move_uploaded_file(
                        $tmpName,
                        $destination
                    )
                ) {

                    $additionalImagePath =
                        'uploads/places/' .
                        $fileName;

                    $insertPhoto->execute([
                        $id,
                        $additionalImagePath
                    ]);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */

        $pdo->commit();

        header(
            'Location: edit-place.php?id=' .
            $id .
            '&updated=1'
        );

        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error =
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| REFRESH PLACE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT *
     FROM places
     WHERE id = ?
     LIMIT 1"
);

$stmt->execute([
    $id
]);

$place =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| REFRESH ADDITIONAL PHOTOS
|--------------------------------------------------------------------------
*/

$photoStmt = $pdo->prepare(
    "SELECT *
     FROM place_images
     WHERE place_id = ?
     ORDER BY id ASC"
);

$photoStmt->execute([
    $id
]);

$additionalPhotos =
    $photoStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

include __DIR__ . '/../includes/header.php';

?>

<section class="admin-shell">

    <div class="container admin-layout">

        <!-- ==========================================================
             ADMIN SIDEBAR
        =========================================================== -->

        <aside class="admin-sidebar">

            <div class="admin-brand">

                Digital Tourist

                <br>

                <small
                    style="
                        opacity:.65;
                        font-weight:400;
                    "
                >
                    Admin Panel
                </small>

            </div>

            <a href="dashboard.php">
                ⌂ Dashboard
            </a>

            <a href="add-place.php">
                ＋ Add Place
            </a>

            <a
                class="active"
                href="places.php"
            >
                📍 Manage Places
            </a>

            <a href="../places.php">
                ↗ View Website
            </a>

            <a href="logout.php">
                ⇥ Logout
            </a>

        </aside>

        <!-- ==========================================================
             MAIN CONTENT
        =========================================================== -->

        <div class="admin-main">

            <div class="admin-top">

                <div>

                    <h1>
                        Edit Place
                    </h1>

                    <p
                        style="
                            font-size:11px;
                            color:var(--muted);
                        "
                    >
                        Update destination information
                        and manage photos.
                    </p>

                </div>

            </div>

            <div class="admin-form">

                <!-- SUCCESS MESSAGE -->

                <?php if (isset($_GET['updated'])): ?>

                    <div
                        class="notice"
                        style="
                            margin-bottom:20px;
                            background:#eaf8ef;
                            color:#18743a;
                            border:1px solid #bce5c9;
                        "
                    >
                        Place updated successfully.
                    </div>

                <?php endif; ?>

                <!-- ERROR MESSAGE -->

                <?php if ($error): ?>

                    <div class="notice error">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>

                <!-- ==================================================
                     PLACE FORM
                =================================================== -->

                <form
                    method="post"
                    action="edit-place.php?id=<?= (int) $id ?>"
                    enctype="multipart/form-data"
                >

                    <div class="form-grid">

                        <!-- NAME -->

                        <div class="form-group">

                            <label>
                                Name *
                            </label>

                            <input
                                class="form-control"
                                type="text"
                                name="name"
                                required
                                value="<?= htmlspecialchars(
                                    $place['name'] ?? ''
                                ) ?>"
                                placeholder="Enter place name"
                            >

                        </div>

                        <!-- CATEGORY -->

                        <div class="form-group">

                            <label>
                                Category *
                            </label>

                            <select
                                class="form-control"
                                name="category_id"
                                required
                            >

                                <option value="">
                                    Select Category
                                </option>

                                <?php foreach ($categories as $c): ?>

                                    <option
                                        value="<?= (int) $c['id'] ?>"
                                        <?= (
                                            (int) (
                                                $place['category_id'] ?? 0
                                            )
                                            ===
                                            (int) $c['id']
                                        )
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        <?= htmlspecialchars(
                                            $c['name']
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <!-- DESCRIPTION -->

                        <div class="form-group full">

                            <label>
                                Description *
                            </label>

                            <textarea
                                class="form-control"
                                name="description"
                                rows="5"
                                required
                                placeholder="Describe this place..."
                            ><?= htmlspecialchars(
                                $place['description'] ?? ''
                            ) ?></textarea>

                        </div>

                        <!-- DISTANCE -->

                        <div class="form-group">

                            <label>
                                Distance (km)
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                name="distance"
                                min="0"
                                step="0.1"
                                value="<?= htmlspecialchars(
                                    $place['distance'] ?? ''
                                ) ?>"
                                placeholder="Example: 5.5"
                            >

                            <small
                                style="
                                    display:block;
                                    margin-top:8px;
                                    color:var(--muted);
                                "
                            >
                                Distance from
                                Maradankadawala in kilometres.
                            </small>

                        </div>

                        <!-- OPENING TIME -->

                        <div class="form-group">

                            <label>
                                Opening Time
                            </label>

                            <input
                                class="form-control"
                                type="time"
                                name="opening_time"
                                value="<?= htmlspecialchars(
                                    $openingTime
                                ) ?>"
                            >

                        </div>

                        <!-- CLOSING TIME -->

                        <div class="form-group">

                            <label>
                                Closing Time
                            </label>

                            <input
                                class="form-control"
                                type="time"
                                name="closing_time"
                                value="<?= htmlspecialchars(
                                    $closingTime
                                ) ?>"
                            >

                        </div>

                        <!-- ENTRANCE FEE -->

                        <div class="form-group">

                            <label>
                                Entrance Fee
                            </label>

                            <input
                                class="form-control"
                                type="number"
                                name="entrance_fee"
                                min="0"
                                step="1"
                                value="<?= htmlspecialchars(
                                    $place['entrance_fee'] ?? ''
                                ) ?>"
                                placeholder="Example: 500"
                            >

                            <small
                                style="
                                    display:block;
                                    margin-top:8px;
                                    color:var(--muted);
                                "
                            >
                                Optional. Leave empty if free
                                or not specified.
                            </small>

                        </div>

                        <!-- TRAVEL TIPS -->

                        <div class="form-group full">

                            <label>
                                Travel Tips
                            </label>

                            <textarea
                                class="form-control"
                                name="travel_tips"
                                rows="3"
                                placeholder="Useful information for visitors... (Optional)"
                            ><?= htmlspecialchars(
                                $place['travel_tips'] ?? ''
                            ) ?></textarea>

                        </div>

                        <!-- GOOGLE MAPS -->

                        <div class="form-group full">

                            <label>
                                Google Maps Location *
                            </label>

                            <input
                                class="form-control"
                                type="url"
                                name="maps_url"
                                required
                                value="<?= htmlspecialchars(
                                    $place['maps_url'] ?? ''
                                ) ?>"
                                placeholder="Paste Google Maps link here"
                            >

                            <small
                                style="
                                    display:block;
                                    margin-top:8px;
                                    color:var(--muted);
                                "
                            >
                                Google Maps → find the place →
                                Share → Copy link.
                            </small>

                        </div>

                        <!-- GOOGLE MAP EMBED -->

                        <div class="form-group full">

                            <label>
                                Google Maps Embed URL
                            </label>

                            <input
                                class="form-control"
                                type="url"
                                name="maps_embed"
                                value="<?= htmlspecialchars(
                                    $place['maps_embed'] ?? ''
                                ) ?>"
                                placeholder="Paste Google Maps embed URL here"
                            >

                            <small
                                style="
                                    display:block;
                                    margin-top:8px;
                                    color:var(--muted);
                                "
                            >
                                Optional.
                            </small>

                        </div>

                        <!-- MAIN IMAGE -->

                        <div class="form-group full">

                            <label>
                                Main Image
                            </label>

                            <input
                                class="form-control"
                                type="file"
                                name="image_file"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <small
                                style="
                                    display:block;
                                    margin-top:8px;
                                    color:var(--muted);
                                "
                            >
                                JPG, PNG or WEBP —
                                maximum 5MB.
                                Leave empty to keep
                                the current image.
                            </small>

                            <?php if (!empty($place['image'])): ?>

                                <div
                                    style="
                                        margin-top:15px;
                                    "
                                >

                                    <strong>
                                        Current Main Image
                                    </strong>

                                    <br><br>

                                    <img
                                        src="<?= htmlspecialchars(
                                            '../' .
                                            ltrim(
                                                $place['image'],
                                                '/'
                                            )
                                        ) ?>"
                                        alt="Current place image"
                                        style="
                                            width:200px;
                                            height:130px;
                                            object-fit:cover;
                                            border-radius:10px;
                                            border:1px solid #ddd;
                                        "
                                    >

                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- ==================================================
                             EXISTING ADDITIONAL PHOTOS
                        =================================================== -->

                        <div class="form-group full">

                            <label>
                                Place Photos
                            </label>

                            <p
                                style="
                                    margin-top:5px;
                                    color:var(--muted);
                                    font-size:13px;
                                "
                            >
                                Select which photos should appear
                                in Place Details. You can also
                                delete photos permanently.
                            </p>

                            <?php if (empty($additionalPhotos)): ?>

                                <div
                                    style="
                                        padding:15px;
                                        background:#f7f7f7;
                                        border-radius:8px;
                                        color:var(--muted);
                                    "
                                >
                                    No additional photos yet.
                                </div>

                            <?php else: ?>

                                <div
                                    style="
                                        display:grid;
                                        grid-template-columns:
                                            repeat(
                                                auto-fill,
                                                minmax(180px, 1fr)
                                            );
                                        gap:16px;
                                        margin-top:15px;
                                    "
                                >

                                    <?php foreach (
                                        $additionalPhotos
                                        as $photo
                                    ): ?>

                                        <?php

                                        $photoUrl =
                                            '../' .
                                            ltrim(
                                                $photo['image'],
                                                '/'
                                            );

                                        $isVisible =
                                            isset(
                                                $photo[
                                                    'show_in_gallery'
                                                ]
                                            )
                                                ? (int) $photo[
                                                    'show_in_gallery'
                                                ]
                                                : 1;

                                        ?>

                                        <div
                                            style="
                                                border:1px solid #ddd;
                                                border-radius:12px;
                                                padding:10px;
                                                background:#fff;
                                            "
                                        >

                                            <img
                                                src="<?= htmlspecialchars(
                                                    $photoUrl
                                                ) ?>"
                                                alt="Place photo"
                                                style="
                                                    width:100%;
                                                    height:140px;
                                                    object-fit:cover;
                                                    border-radius:8px;
                                                    display:block;
                                                "
                                            >

                                            <div
                                                style="
                                                    margin-top:10px;
                                                "
                                            >

                                                <label
                                                    style="
                                                        display:flex;
                                                        align-items:center;
                                                        gap:8px;
                                                        cursor:pointer;
                                                    "
                                                >

                                                    <input
                                                        type="checkbox"
                                                        name="visible_images[]"
                                                        value="<?= (int) $photo['id'] ?>"
                                                        <?= $isVisible
                                                            ? 'checked'
                                                            : '' ?>
                                                    >

                                                    <span>
                                                        Show in Place Details
                                                    </span>

                                                </label>

                                                <label
                                                    style="
                                                        display:flex;
                                                        align-items:center;
                                                        gap:8px;
                                                        margin-top:8px;
                                                        cursor:pointer;
                                                        color:#b42318;
                                                    "
                                                >

                                                    <input
                                                        type="checkbox"
                                                        name="delete_images[]"
                                                        value="<?= (int) $photo['id'] ?>"
                                                    >

                                                    <span>
                                                        Delete this photo
                                                    </span>

                                                </label>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- ==================================================
                             ADD NEW PHOTOS
                        =================================================== -->

                        <div class="form-group full">

                            <label>
                                Add New Photos
                            </label>

                            <input
                                class="form-control"
                                type="file"
                                name="additional_images[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                            >

                            <small
                                style="
                                    display:block;
                                    margin-top:8px;
                                    color:var(--muted);
                                "
                            >
                                Select one or multiple photos.
                                New photos will automatically
                                be shown in Place Details.
                                Maximum 5MB per image.
                            </small>

                        </div>

                        <!-- STATUS -->

                        <div class="form-group">

                            <label>
                                Status *
                            </label>

                            <select
                                class="form-control"
                                name="status"
                                required
                            >

                                <option
                                    value="1"
                                    <?= (
                                        $place['status'] ?? 1
                                    )
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Active
                                </option>

                                <option
                                    value="0"
                                    <?= (
                                        isset($place['status']) &&
                                        !$place['status']
                                    )
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- BUTTONS -->

                    <div
                        class="actions"
                        style="margin-top:25px;"
                    >

                        <button
                            class="btn"
                            type="submit"
                        >
                            Update Place
                        </button>

                        <a
                            class="btn secondary"
                            href="places.php"
                        >
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

</section>

<?php

include __DIR__ . '/../includes/footer.php';

?>