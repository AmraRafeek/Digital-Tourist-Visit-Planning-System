<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$pageTitle = 'Add Place';
$basePath = '../';


// ==================================================
// GET CATEGORIES
// ==================================================

$categories = $pdo
    ->query(
        'SELECT * FROM categories ORDER BY name'
    )
    ->fetchAll();

$error = '';


// ==================================================
// FORM SUBMISSION
// ==================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        // ==================================================
        // BASIC FORM VALUES
        // ==================================================

        $name = trim(
            $_POST['name'] ?? ''
        );

        $categoryId = (int)(
            $_POST['category_id'] ?? 0
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $openingTime = trim(
            $_POST['opening_time'] ?? ''
        );

        $closingTime = trim(
            $_POST['closing_time'] ?? ''
        );

        $travelTips = trim(
            $_POST['travel_tips'] ?? ''
        );

        $entranceFee = trim(
            $_POST['entrance_fee'] ?? ''
        );

        $distance = trim(
            $_POST['distance'] ?? ''
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


        // ==================================================
        // VALIDATION
        // ==================================================

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


        if ($mapsEmbed === '') {

            throw new Exception(
                'Please enter the Google Maps embed URL.'
            );
        }


        // ==================================================
        // DISTANCE VALIDATION
        // ==================================================

        if (
            $distance !== '' &&
            !is_numeric($distance)
        ) {

            throw new Exception(
                'Distance must contain numbers only.'
            );
        }


        // ==================================================
        // ENTRANCE FEE VALIDATION
        // ==================================================

        if (
            $entranceFee !== '' &&
            !is_numeric($entranceFee)
        ) {

            throw new Exception(
                'Entrance Fee must contain numbers only.'
            );
        }


        // ==================================================
        // OPENING HOURS
        // ==================================================

        $openingHours = '';

        if (
            $openingTime !== '' &&
            $closingTime !== ''
        ) {

            $openingHours =
                $openingTime .
                ' - ' .
                $closingTime;
        }


        // ==================================================
        // MAIN IMAGE
        // ==================================================

        $imagePath = '';

        if (
            !isset($_FILES['image_file']) ||
            $_FILES['image_file']['error']
                === UPLOAD_ERR_NO_FILE
        ) {

            throw new Exception(
                'Please select a main image.'
            );
        }


        if (
            $_FILES['image_file']['error']
            !== UPLOAD_ERR_OK
        ) {

            throw new Exception(
                'There was a problem uploading the main image.'
            );
        }


        // Maximum 5 MB

        if (
            $_FILES['image_file']['size']
            > 5 * 1024 * 1024
        ) {

            throw new Exception(
                'Main image must be smaller than 5 MB.'
            );
        }


        // Check actual image

        $imageInfo = getimagesize(
            $_FILES['image_file']['tmp_name']
        );


        if ($imageInfo === false) {

            throw new Exception(
                'Please select a valid main image.'
            );
        }


        // Allowed image types

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];


        $mimeType =
            $imageInfo['mime'];


        if (
            !isset(
                $allowedTypes[$mimeType]
            )
        ) {

            throw new Exception(
                'Only JPG, PNG and WEBP images are allowed.'
            );
        }


        $extension =
            $allowedTypes[$mimeType];


        // ==================================================
        // UPLOAD DIRECTORY
        // ==================================================

        $uploadDir =
            __DIR__ . '/../Images/places/';


        if (!is_dir($uploadDir)) {

            if (
                !mkdir(
                    $uploadDir,
                    0777,
                    true
                )
            ) {

                throw new Exception(
                    'Unable to create the image upload folder.'
                );
            }
        }


        // ==================================================
        // MAIN IMAGE FILE NAME
        // ==================================================

        $fileName =
            'place_' .
            date('Ymd_His') .
            '_' .
            uniqid() .
            '.' .
            $extension;


        $destination =
            $uploadDir . $fileName;


        // ==================================================
        // MOVE MAIN IMAGE
        // ==================================================

        if (
            !move_uploaded_file(
                $_FILES['image_file']['tmp_name'],
                $destination
            )
        ) {

            throw new Exception(
                'Unable to save the main image.'
            );
        }


        $imagePath =
            'Images/places/' . $fileName;


        // ==================================================
        // SAVE PLACE
        // ==================================================

        $stmt = $pdo->prepare("
            INSERT INTO places
            (
                name,
                category_id,
                description,
                distance,
                opening_hours,
                travel_tips,
                entrance_fee,
                image,
                maps_url,
                maps_embed,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        $stmt->execute([

            $name,

            $categoryId,

            $description,

            $distance !== ''
                ? $distance
                : null,

            $openingHours,

            $travelTips,

            $entranceFee !== ''
                ? $entranceFee
                : null,

            $imagePath,

            $mapsUrl,

            $mapsEmbed,

            $status

        ]);


        // ==================================================
        // GET NEW PLACE ID
        // ==================================================

        $placeId =
            (int) $pdo->lastInsertId();


        // ==================================================
        // ADDITIONAL PHOTOS
        // ==================================================

        if (
            isset($_FILES['additional_images']) &&
            isset($_FILES['additional_images']['name']) &&
            is_array(
                $_FILES['additional_images']['name']
            )
        ) {

            $photoStmt = $pdo->prepare("
                INSERT INTO place_images
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
                )
            ");


            foreach (
                $_FILES['additional_images']['name']
                as $key => $originalName
            ) {

                // No file selected

                if (
                    $_FILES['additional_images']['error'][$key]
                    === UPLOAD_ERR_NO_FILE
                ) {

                    continue;
                }


                // Upload error

                if (
                    $_FILES['additional_images']['error'][$key]
                    !== UPLOAD_ERR_OK
                ) {

                    continue;
                }


                // Maximum 5 MB

                if (
                    $_FILES['additional_images']['size'][$key]
                    > 5 * 1024 * 1024
                ) {

                    continue;
                }


                $tmpFile =
                    $_FILES['additional_images']['tmp_name'][$key];


                // Check image

                $photoInfo =
                    getimagesize($tmpFile);


                if ($photoInfo === false) {

                    continue;
                }


                $photoMime =
                    $photoInfo['mime'];


                if (
                    !isset(
                        $allowedTypes[$photoMime]
                    )
                ) {

                    continue;
                }


                $photoExtension =
                    $allowedTypes[$photoMime];


                // Unique filename

                $photoFileName =
                    'place_' .
                    $placeId .
                    '_' .
                    date('Ymd_His') .
                    '_' .
                    uniqid() .
                    '.' .
                    $photoExtension;


                $photoDestination =
                    $uploadDir .
                    $photoFileName;


                // Save additional image

                if (
                    move_uploaded_file(
                        $tmpFile,
                        $photoDestination
                    )
                ) {

                    $additionalImagePath =
                        'Images/places/' .
                        $photoFileName;


                    $photoStmt->execute([
                        $placeId,
                        $additionalImagePath
                    ]);
                }
            }
        }


        // ==================================================
        // SUCCESS
        // ==================================================

        header(
            'Location: places.php'
        );

        exit;


    } catch (Exception $e) {

        $error =
            $e->getMessage();
    }
}


// ==================================================
// HEADER
// ==================================================

include __DIR__ . '/../includes/header.php';

?>


<section class="admin-shell">

    <div class="container admin-layout">


        <!-- ==========================================
             ADMIN SIDEBAR
        =========================================== -->

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


            <a
                class="active"
                href="add-place.php"
            >
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


        <!-- ==========================================
             MAIN CONTENT
        =========================================== -->

        <div class="admin-main">

            <div class="admin-top">

                <div>

                    <h1>
                        Add Place
                    </h1>

                    <p
                        style="
                        font-size:11px;
                        color:var(--muted);
                        "
                    >
                        Add a destination to the public website.
                    </p>

                </div>

            </div>


            <div class="admin-form">


                <!-- ERROR -->

                <?php if ($error): ?>

                    <div class="notice error">

                        <?= htmlspecialchars($error) ?>

                    </div>

                <?php endif; ?>


                <?php

                $place = [];

                include __DIR__ . '/place-form.php';

                ?>

            </div>

        </div>

    </div>

</section>


<?php

include __DIR__ . '/../includes/footer.php';

?>