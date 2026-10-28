<?php

require_once __DIR__ . '/config/database.php';


// ==================================================
// SESSION
// ==================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Trip plan
if (!isset($_SESSION['trip_plan'])) {
    $_SESSION['trip_plan'] = [];
}


// Saved itineraries
if (!isset($_SESSION['saved_itineraries'])) {
    $_SESSION['saved_itineraries'] = [];
}


// ==================================================
// ADD PLACE
// ==================================================

if (isset($_GET['add'])) {

    $id = (int)$_GET['add'];

    if ($id > 0) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM places
            WHERE id = ?
              AND status = 1
        ");

        $stmt->execute([$id]);

        if ($stmt->fetchColumn()) {

            if (!in_array($id, $_SESSION['trip_plan'], true)) {
                $_SESSION['trip_plan'][] = $id;
            }
        }
    }

    header('Location: planner.php');
    exit;
}


// ==================================================
// REMOVE PLACE
// ==================================================

if (isset($_GET['remove'])) {

    $id = (int)$_GET['remove'];

    $_SESSION['trip_plan'] = array_values(
        array_filter(
            $_SESSION['trip_plan'],
            function ($placeId) use ($id) {
                return (int)$placeId !== $id;
            }
        )
    );

    header('Location: planner.php');
    exit;
}


// ==================================================
// DELETE SAVED ITINERARY
// ==================================================

if (isset($_GET['delete_itinerary'])) {

    $index = (int)$_GET['delete_itinerary'];

    if (isset($_SESSION['saved_itineraries'][$index])) {

        unset($_SESSION['saved_itineraries'][$index]);

        $_SESSION['saved_itineraries'] =
            array_values($_SESSION['saved_itineraries']);
    }

    header('Location: planner.php#saved-itineraries');
    exit;
}


// ==================================================
// SAVE ITINERARY
// ==================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_itinerary'])
) {

    $order = [];

    if (!empty($_POST['place_order'])) {

        $order = array_values(
            array_filter(
                array_map(
                    'intval',
                    explode(',', $_POST['place_order'])
                )
            )
        );
    }


    // If JS order is missing, use current session order
    if (empty($order)) {

        $order = array_values(
            array_map(
                'intval',
                $_SESSION['trip_plan']
            )
        );
    }


    $times = $_POST['visit_time'] ?? [];

    $savedPlaces = [];

    foreach ($order as $placeId) {

        $time = '';

        if (isset($times[$placeId])) {
            $time = trim($times[$placeId]);
        }

        $savedPlaces[] = [
            'place_id' => $placeId,
            'time'     => $time
        ];
    }


    if (!empty($savedPlaces)) {

        $_SESSION['saved_itineraries'][] = [
            'created_at' => date('Y-m-d H:i:s'),
            'places'     => $savedPlaces
        ];
    }


    header('Location: planner.php?saved=1#saved-itineraries');
    exit;
}


// ==================================================
// GET CURRENT PLACES
// ==================================================

$places = [];

if (!empty($_SESSION['trip_plan'])) {

    $placeIds = array_values(
        array_map(
            'intval',
            $_SESSION['trip_plan']
        )
    );


    $placeholders = implode(
        ',',
        array_fill(
            0,
            count($placeIds),
            '?'
        )
    );


    /*
     * p.* includes the distance column.
     */
    $stmt = $pdo->prepare("
        SELECT
            p.*,
            c.name AS category_name
        FROM places p
        JOIN categories c
            ON p.category_id = c.id
        WHERE p.id IN ($placeholders)
          AND p.status = 1
    ");

    $stmt->execute($placeIds);

    $result = $stmt->fetchAll();


    // Keep user's drag/drop order
    foreach ($placeIds as $placeId) {

        foreach ($result as $place) {

            if ((int)$place['id'] === (int)$placeId) {

                $places[] = $place;

                break;
            }
        }
    }
}


// ==================================================
// PAGE SETTINGS
// ==================================================

$pageTitle = 'One-Day Visit Planner';
$basePath = '';

include __DIR__ . '/includes/header.php';

?>

<style>

/* ==================================================
   PLANNER
   ================================================== */

.planner-page-title {
    margin-bottom: 28px;
}

.planner-page-title h1 {
    margin-bottom: 8px;
}

.planner-page-title p {
    color: var(--muted);
}


/* MAIN GRID */

.planner-layout {
    display: grid;
    grid-template-columns: 0.75fr 1.25fr;
    gap: 24px;
    align-items: start;
}


/* CARDS */

.planner-panel,
.itinerary-builder,
.saved-section {
    background: #fff;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,.06);
}

.planner-panel h2,
.itinerary-builder h2,
.saved-section h2 {
    margin-top: 0;
    margin-bottom: 6px;
}

.panel-description {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 20px;
}


/* ==================================================
   DRAG PLACES
   ================================================== */

#sortablePlaces {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.drag-place {
    display: flex;
    align-items: center;
    gap: 10px;

    background: #f7f9f9;

    border: 1px solid #e2e8e7;

    border-radius: 12px;

    padding: 12px 14px;

    cursor: grab;

    transition: .2s;
}

.drag-place:hover {
    border-color: #0c8f82;
    background: #f4fbfa;
}

.drag-place.dragging {
    opacity: .45;
}

.drag-handle {
    color: #7b8b89;
    font-size: 18px;
    cursor: grab;
}

.place-number {
    font-weight: 700;
    color: #0d2530;
    min-width: 24px;
}

.place-name {
    flex: 1;
    font-size: 14px;
    font-weight: 600;
}

.remove-place {
    color: #dc3545;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.remove-place:hover {
    text-decoration: underline;
}

.add-more {
    display: block;
    width: 100%;
    margin-top: 12px;
    text-align: center;
}


/* ==================================================
   BUILD ITINERARY
   ================================================== */

.itinerary-list {
    display: flex;
    flex-direction: column;
}

.itinerary-item {
    display: grid;

    grid-template-columns: 115px 1fr;

    gap: 22px;

    padding: 18px 0;

    border-bottom: 1px solid #e6eceb;
}

.itinerary-item:last-child {
    border-bottom: none;
}


/* TIME */

.time-column {
    display: flex;
    align-items: flex-start;
}

.visit-time {
    width: 110px;

    padding: 11px 10px;

    border: 1px solid #dce5e3;

    border-radius: 10px;

    background: #f8fbfa;

    color: #008f82;

    font-size: 16px;

    font-weight: 700;

    outline: none;
}

.visit-time:focus {
    border-color: #0c8f82;

    box-shadow:
        0 0 0 3px rgba(12,143,130,.10);
}


/* DESTINATION */

.destination-info {
    min-width: 0;
}

.destination-info h3 {
    margin: 0 0 5px;

    font-size: 17px;

    color: #0c2730;
}

.destination-category {
    margin: 0 0 5px;

    color: var(--muted);

    font-size: 13px;
}

.destination-distance {
    margin: 0;

    color: #078f82;

    font-size: 13px;

    font-weight: 600;
}


/* SAVE */

.save-area {
    margin-top: 20px;
}

.save-button {
    border: none;

    background: #078f82;

    color: white;

    padding: 13px 28px;

    border-radius: 10px;

    font-weight: 700;

    cursor: pointer;

    font-size: 14px;
}

.save-button:hover {
    background: #06786e;
}


/* ==================================================
   SUCCESS
   ================================================== */

.success-message {
    background: #dcfce7;

    color: #087443;

    border: 1px solid #b7efcc;

    padding: 13px 16px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-size: 14px;
}


/* ==================================================
   SAVED ITINERARIES
   ================================================== */

.saved-section {
    margin-top: 28px;
}

.saved-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;
}

.saved-count {
    background: #eef5f4;

    color: #087f75;

    padding: 7px 13px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 700;
}

.saved-itinerary {
    border: 1px solid #e0e7e6;

    border-radius: 14px;

    margin-bottom: 12px;

    overflow: hidden;

    background: #fff;
}

.saved-itinerary:last-child {
    margin-bottom: 0;
}

.saved-itinerary-header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 16px 18px;

    background: #f8faf9;
}

.saved-title {
    font-weight: 700;

    color: #092832;

    font-size: 16px;
}

.saved-date {
    display: block;

    color: var(--muted);

    font-size: 11px;

    margin-top: 4px;
}

.saved-actions {
    display: flex;

    gap: 8px;
}

.view-saved,
.delete-saved {
    padding: 8px 13px;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 600;

    text-decoration: none;
}

.view-saved {
    background: #078f82;

    color: white;
}

.delete-saved {
    background: #fff0f0;

    color: #d93636;
}

.saved-places {
    padding: 8px 18px 14px;
}

.saved-place-row {
    display: grid;

    grid-template-columns: 90px 1fr;

    gap: 16px;

    padding: 12px 0;

    border-bottom: 1px solid #edf1f0;
}

.saved-place-row:last-child {
    border-bottom: none;
}

.saved-time {
    color: #078f82;

    font-weight: 700;

    font-size: 14px;
}

.saved-time.not-set {
    color: #879593;
}

.saved-place-name {
    font-weight: 600;

    color: #102c34;

    font-size: 14px;
}

.saved-place-category {
    color: var(--muted);

    font-size: 12px;

    margin-top: 3px;
}

.saved-place-distance {
    color: #078f82;

    font-size: 12px;

    font-weight: 600;

    margin-top: 3px;
}


/* EMPTY */

.saved-empty {
    padding: 30px;

    text-align: center;

    color: var(--muted);

    background: #f8faf9;

    border-radius: 12px;
}


/* MOBILE */

@media (max-width: 850px) {

    .planner-layout {
        grid-template-columns: 1fr;
    }

    .itinerary-item {
        grid-template-columns: 95px 1fr;
        gap: 14px;
    }

    .visit-time {
        width: 95px;
        font-size: 14px;
    }

}

@media (max-width: 600px) {

    .saved-itinerary-header {
        align-items: flex-start;

        flex-direction: column;

        gap: 12px;
    }

    .saved-place-row {
        grid-template-columns: 75px 1fr;
    }

}

</style>


<section class="section">

<div class="container">


    <!-- PAGE TITLE -->

    <div class="planner-page-title">

        <h1>
            One-Day Visit Planner
        </h1>

        <p>
            Arrange your destinations and create your perfect one-day itinerary.
        </p>

    </div>


    <?php if (isset($_GET['saved'])): ?>

        <div class="success-message">
            ✓ Your itinerary has been saved successfully.
        </div>

    <?php endif; ?>


    <?php if (empty($places)): ?>

        <div class="empty">

            <h3>
                No destinations selected yet.
            </h3>

            <p>
                Browse the available places and add your favourite destinations to your plan.
            </p>

            <br>

            <a
                class="btn"
                href="places.php"
            >
                Browse Places
            </a>

        </div>


    <?php else: ?>


        <!-- MAIN PLANNER -->

        <div class="planner-layout">


            <!-- ARRANGE PLACES -->

            <div class="planner-panel">

                <h2>
                    Arrange Places
                </h2>

                <p class="panel-description">
                    Drag and drop the places to change their order.
                </p>


                <div id="sortablePlaces">

                    <?php foreach ($places as $i => $p): ?>

                        <div
                            class="drag-place"
                            draggable="true"
                            data-id="<?= (int)$p['id'] ?>"
                        >

                            <span class="drag-handle">
                                ⋮⋮
                            </span>

                            <span class="place-number">
                                <?= $i + 1 ?>.
                            </span>

                            <span class="place-name">
                                <?= htmlspecialchars(
                                    $p['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                            <a
                                class="remove-place"
                                href="planner.php?remove=<?= (int)$p['id'] ?>"
                                onclick="return confirm('Remove this place from your plan?');"
                            >
                                Remove
                            </a>

                        </div>

                    <?php endforeach; ?>

                </div>


                <a
                    class="btn secondary add-more"
                    href="places.php"
                >
                    ＋ Add More Places
                </a>

            </div>


            <!-- BUILD ITINERARY -->

            <div class="itinerary-builder">

                <h2>
                    Build Your Itinerary
                </h2>

                <p class="panel-description">
                    Choose a visit time for each destination.
                </p>


                <form
                    method="post"
                    id="itineraryForm"
                >

                    <!-- JavaScript puts the drag/drop order here -->

                    <input
                        type="hidden"
                        name="place_order"
                        id="placeOrder"
                        value=""
                    >


                    <div class="itinerary-list">

                        <?php foreach ($places as $i => $p): ?>

                            <div
                                class="itinerary-item"
                                data-place-id="<?= (int)$p['id'] ?>"
                            >


                                <!-- TIME -->

                                <div class="time-column">

                                    <input
                                        type="time"
                                        class="visit-time"
                                        name="visit_time[<?= (int)$p['id'] ?>]"
                                        value=""
                                    >

                                </div>


                                <!-- PLACE -->

                                <div class="destination-info">

                                    <h3>

                                        <?= htmlspecialchars(
                                            $p['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </h3>


                                    <p class="destination-category">

                                        <?= htmlspecialchars(
                                            $p['category_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </p>


                                    <?php if (
                                        isset($p['distance']) &&
                                        $p['distance'] !== '' &&
                                        $p['distance'] !== null
                                    ): ?>

                                        <p class="destination-distance">

                                            📍
                                            <?= htmlspecialchars(
                                                $p['distance'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                            km from Maradankadawala

                                        </p>

                                    <?php endif; ?>


                                </div>


                            </div>

                        <?php endforeach; ?>


                    </div>


                    <div class="save-area">

                        <button
                            type="submit"
                            name="save_itinerary"
                            class="save-button"
                        >
                            Save Itinerary
                        </button>

                    </div>


                </form>

            </div>


        </div>


    <?php endif; ?>


    <!-- SAVED ITINERARIES -->

    <div
        class="saved-section"
        id="saved-itineraries"
    >


        <div class="saved-header">

            <div>

                <h2>
                    Saved Itineraries
                </h2>

                <p
                    class="panel-description"
                    style="margin-bottom:0;"
                >
                    Your saved one-day travel schedules.
                </p>

            </div>


            <span class="saved-count">

                <?= count($_SESSION['saved_itineraries']) ?>

                <?= count($_SESSION['saved_itineraries']) === 1
                    ? 'Itinerary'
                    : 'Itineraries'
                ?>

            </span>

        </div>


        <?php if (empty($_SESSION['saved_itineraries'])): ?>


            <div class="saved-empty">

                No itineraries have been saved yet.

                <br>

                Create your itinerary above and click
                <strong>Save Itinerary</strong>.

            </div>


        <?php else: ?>


            <?php foreach (
                $_SESSION['saved_itineraries']
                as $itineraryIndex => $itinerary
            ): ?>


                <div class="saved-itinerary">


                    <!-- HEADER -->

                    <div class="saved-itinerary-header">

                        <div>

                            <div class="saved-title">

                                Itinerary
                                <?= str_pad(
                                    $itineraryIndex + 1,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>

                            </div>


                            <span class="saved-date">

                                Saved
                                <?= htmlspecialchars(
                                    $itinerary['created_at'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        </div>


                        <div class="saved-actions">

                            <a
                                href="#saved-itinerary-<?= $itineraryIndex ?>"
                                class="view-saved"
                            >
                                View
                            </a>


                            <a
                                href="planner.php?delete_itinerary=<?= $itineraryIndex ?>"
                                class="delete-saved"
                                onclick="return confirm('Delete this itinerary?');"
                            >
                                Delete
                            </a>

                        </div>

                    </div>


                    <!-- PLACES -->

                    <div
                        class="saved-places"
                        id="saved-itinerary-<?= $itineraryIndex ?>"
                    >


                        <?php foreach (
                            $itinerary['places']
                            as $savedPlace
                        ): ?>


                            <?php

                            $savedPlaceId =
                                (int)$savedPlace['place_id'];

                            /*
                             * Distance is loaded here as well.
                             */
                            $stmt = $pdo->prepare("
                                SELECT
                                    p.name,
                                    p.distance,
                                    c.name AS category_name
                                FROM places p
                                JOIN categories c
                                    ON p.category_id = c.id
                                WHERE p.id = ?
                            ");

                            $stmt->execute([
                                $savedPlaceId
                            ]);

                            $savedPlaceData =
                                $stmt->fetch();

                            if (!$savedPlaceData) {
                                continue;
                            }

                            ?>


                            <div class="saved-place-row">


                                <!-- TIME -->

                                <div
                                    class="
                                    saved-time
                                    <?= empty($savedPlace['time'])
                                        ? 'not-set'
                                        : ''
                                    ?>
                                    "
                                >

                                    <?php if (
                                        !empty($savedPlace['time'])
                                    ): ?>

                                        <?= date(
                                            'h:i A',
                                            strtotime(
                                                $savedPlace['time']
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        Time not set

                                    <?php endif; ?>

                                </div>


                                <!-- PLACE -->

                                <div>

                                    <div class="saved-place-name">

                                        <?= htmlspecialchars(
                                            $savedPlaceData['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>


                                    <div class="saved-place-category">

                                        <?= htmlspecialchars(
                                            $savedPlaceData['category_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>


                                    <?php if (
                                        isset($savedPlaceData['distance']) &&
                                        $savedPlaceData['distance'] !== '' &&
                                        $savedPlaceData['distance'] !== null
                                    ): ?>

                                        <div class="saved-place-distance">

                                            📍
                                            <?= htmlspecialchars(
                                                $savedPlaceData['distance'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                            km from Maradankadawala

                                        </div>

                                    <?php endif; ?>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</div>

</section>


<script>

/* ==================================================
   DRAG & DROP
   ================================================== */

const sortablePlaces =
    document.getElementById('sortablePlaces');

const placeOrder =
    document.getElementById('placeOrder');

const itineraryList =
    document.querySelector('.itinerary-list');


let draggedItem = null;


if (sortablePlaces) {

    const items =
        sortablePlaces.querySelectorAll('.drag-place');


    items.forEach(item => {

        item.addEventListener('dragstart', function () {

            draggedItem = this;

            this.classList.add('dragging');

        });


        item.addEventListener('dragend', function () {

            this.classList.remove('dragging');

            draggedItem = null;

            updateEverything();

        });


        item.addEventListener('dragover', function (event) {

            event.preventDefault();

            if (!draggedItem || draggedItem === this) {
                return;
            }


            const rect =
                this.getBoundingClientRect();

            const middle =
                rect.top + rect.height / 2;


            if (event.clientY < middle) {

                sortablePlaces.insertBefore(
                    draggedItem,
                    this
                );

            } else {

                sortablePlaces.insertBefore(
                    draggedItem,
                    this.nextSibling
                );

            }

        });

    });

}


/* ==================================================
   UPDATE ORDER
   ================================================== */

function updateEverything() {

    if (!sortablePlaces) {
        return;
    }


    const placeItems =
        sortablePlaces.querySelectorAll('.drag-place');


    const ids = [];


    placeItems.forEach((item, index) => {

        const id =
            item.dataset.id;

        ids.push(id);


        // Update numbers

        const number =
            item.querySelector('.place-number');

        if (number) {

            number.textContent =
                (index + 1) + '.';

        }

    });


    placeOrder.value =
        ids.join(',');


    // Reorder itinerary section

    if (itineraryList) {

        ids.forEach(id => {

            const itineraryItem =
                itineraryList.querySelector(
                    '.itinerary-item[data-place-id="' +
                    id +
                    '"]'
                );


            if (itineraryItem) {

                itineraryList.appendChild(
                    itineraryItem
                );

            }

        });

    }

}


/* ==================================================
   INITIAL ORDER
   ================================================== */

updateEverything();

</script>


<?php

include __DIR__ . '/includes/footer.php';

?>
```
