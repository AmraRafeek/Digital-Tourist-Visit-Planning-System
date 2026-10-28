<?php

$editing = isset($place['id']) && !empty($place['id']);

$action = $editing
    ? 'edit-place.php?id=' . (int)$place['id']
    : 'add-place.php';

/*
|--------------------------------------------------------------------------
| Opening Hours
|--------------------------------------------------------------------------
| Stored as: 08:00 - 18:00
*/

$openingHours = $place['opening_hours'] ?? '';

$openingTime = '';
$closingTime = '';

if ($openingHours !== '') {

    $parts = preg_split(
        '/\s*-\s*/',
        $openingHours,
        2
    );

    if (count($parts) === 2) {

        $openingTime = trim($parts[0]);
        $closingTime = trim($parts[1]);

    }
}

?>

<form
    method="post"
    action="<?= htmlspecialchars($action) ?>"
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
                value="<?= htmlspecialchars($place['name'] ?? '') ?>"
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
                        value="<?= (int)$c['id'] ?>"
                        <?= (
                            (int)($place['category_id'] ?? 0)
                            ===
                            (int)$c['id']
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($c['name']) ?>

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
            ><?= htmlspecialchars($place['description'] ?? '') ?></textarea>

        </div>


        <!-- DISTANCE -->

        <div class="form-group">

            <label>
                Distance from Maradankadawala (km) *
            </label>

            <input
                class="form-control"
                type="number"
                name="distance"
                min="0"
                step="0.1"
                required
                value="<?= htmlspecialchars($place['distance'] ?? '') ?>"
                placeholder="Example: 5.5"
            >

            <small
                style="
                    display:block;
                    margin-top:8px;
                    color:var(--muted);
                "
            >
                Enter the distance from Maradankadawala in kilometres.
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
                value="<?= htmlspecialchars($openingTime) ?>"
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
                value="<?= htmlspecialchars($closingTime) ?>"
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
                value="<?= htmlspecialchars($place['entrance_fee'] ?? '') ?>"
                placeholder="Example: 500"
            >

            <small
                style="
                    display:block;
                    margin-top:8px;
                    color:var(--muted);
                "
            >
                Optional. Enter numbers only. Leave empty if free or not specified.
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
            ><?= htmlspecialchars($place['travel_tips'] ?? '') ?></textarea>

        </div>


        <!-- GOOGLE MAPS LOCATION -->

        <div class="form-group full">

            <label>
                Google Maps Location *
            </label>

            <input
                class="form-control"
                type="url"
                name="maps_url"
                required
                value="<?= htmlspecialchars($place['maps_url'] ?? '') ?>"
                placeholder="Paste the Google Maps link here"
            >

            <small
                style="
                    display:block;
                    margin-top:8px;
                    color:var(--muted);
                "
            >
                Open Google Maps → find the exact place → Share → Copy link → paste it here.
            </small>

        </div>


        <!-- GOOGLE MAP EMBED -->

        <div class="form-group full">

            <label>
                Google Maps Embed URL *
            </label>

            <input
                class="form-control"
                type="url"
                name="maps_embed"
                required
                value="<?= htmlspecialchars($place['maps_embed'] ?? '') ?>"
                placeholder="Paste the Google Maps embed URL here"
            >

            <small
                style="
                    display:block;
                    margin-top:8px;
                    color:var(--muted);
                "
            >
                Google Maps → Share → Embed a map → copy the URL from the iframe.
            </small>

        </div>


        <!-- MAIN IMAGE -->

        <div class="form-group full">

            <label>
                Main Image <?= $editing ? '' : '*' ?>
            </label>

            <input
                class="form-control"
                type="file"
                name="image_file"
                accept="image/jpeg,image/png,image/webp"
                <?= !$editing ? 'required' : '' ?>
            >

            <small
                style="
                    display:block;
                    margin-top:8px;
                    color:var(--muted);
                "
            >
                JPG, PNG or WEBP — maximum 5MB.
                <?= $editing ? 'Leave empty to keep the current image.' : '' ?>
            </small>


            <?php if (!empty($place['image'])): ?>

                <div style="margin-top:12px;">

                    <img
                        src="<?= htmlspecialchars(
                            '../' . ltrim($place['image'], '/')
                        ) ?>"
                        alt="Current place image"
                        style="
                            width:180px;
                            height:120px;
                            object-fit:cover;
                            border-radius:10px;
                            border:1px solid #ddd;
                        "
                    >

                </div>

            <?php endif; ?>

        </div>


        <!-- ADDITIONAL PHOTOS -->

        <div class="form-group full">

            <label>
                Additional Photos
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
                Optional. You can select multiple photos.
                JPG, PNG or WEBP — maximum 5MB per image.
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
                    <?= ($place['status'] ?? 1)
                        ? 'selected'
                        : '' ?>
                >
                    Active
                </option>

                <option
                    value="0"
                    <?= isset($place['status'])
                        && !$place['status']
                        ? 'selected'
                        : '' ?>
                >
                    Inactive
                </option>

            </select>

        </div>


    </div>


    <!-- BUTTONS -->

    <div class="actions">

        <button
            class="btn"
            type="submit"
        >

            <?= $editing
                ? 'Update Place'
                : 'Save Place' ?>

        </button>


        <a
            class="btn secondary"
            href="places.php"
        >
            Cancel
        </a>

    </div>

</form>