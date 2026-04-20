<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: Admin Create YouTube Channel (Fetch one entity OR Manual create) using the stock template pattern.

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}
?>

<?php
// TODO handle channel fetch/create
if (isset($_POST["action"])) {

    $action = se($_POST, "action", "", false);

    // single entity like "quote" in stock template

    $channel = [];

    // ---- FETCH ONE ENTITY (API) ----

    if ($action === "fetch") {

        $channel_id = trim(se($_POST, "channel_id", "", false));

        $query = trim(se($_POST, "query", "", false));     // ✅ FIX: define query

        $next  = trim(se($_POST, "next", "", false));      // ✅ optional, if your form has it

        if ($channel_id && $query) {

            // ✅ returns bundle: ["raw"=>..., "channel"=>..., "videos"=>...]

            $result = yt_channel_search($channel_id, $query, $next);

            error_log("YT Channel bundle: " . var_export($result, true));

            // ✅ FIX: extract the single channel row

            $channel = se($result, "channel", [], false);

            if ($channel) {

                $channel["is_api"] = 1; // (already 1 in transform, but ok)

            } else {

                flash("No channel data returned from API.", "warning");

            }

        } else {

            flash("You must provide a Channel ID and Query.", "warning");

        }


    // ---- CREATE ONE ENTITY (MANUAL) ----
    } else if ($action === "create") {

        // remove keys that aren't part of your data (whitelist)
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ["channel_id", "title", "vanity_url", "verified", "subscribers_text", "avatar_url"])) {
                unset($_POST[$k]);
            }
        }

        $channel = $_POST;
        $channel["is_api"] = 0;

        // normalize verified checkbox/text
        $channel["verified"] = !empty($channel["verified"]) ? 1 : 0;

        error_log("Manual Channel cleaned POST: " . var_export($channel, true));

        // simple required checks
        if (empty(trim(se($channel, "channel_id", "", false))) || empty(trim(se($channel, "title", "", false)))) {
            $channel = [];
            flash("Channel ID and Title are required.", "warning");
        }
    }

    // ---- INSERT ONE ROW (same pattern as stock template) ----
    if (!empty($channel)) {
        $db = getDB();
        $query = "INSERT INTO `IT202_M2_YT_Channels` ";
        $columns = [];
        $params = [];

        foreach ($channel as $k => $v) {
            $columns[] = "`$k`";
            $params[":$k"] = $v;
        }

        $query .= "(" . join(",", $columns) . ")";
        $query .= " VALUES (" . join(",", array_keys($params)) . ")";

        error_log("Query: " . $query);
        error_log("Params: " . var_export($params, true));

        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            flash("Inserted record " . $db->lastInsertId(), "success");
        } catch (PDOException $e) {
            error_log("YT Channel insert error: " . var_export($e, true));
            flash("An error occurred inserting the channel (maybe duplicate channel_id).", "danger");
        }
    }
}
?>

<div class="container-fluid">
    <h3>Create or Fetch YouTube Channel</h3>

    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('create'); return false;">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('fetch'); return false;">Create</a>
        </li>
    </ul>

    <!-- FETCH TAB -->
    <div id="fetch" class="tab-target">
        <form method="POST">
            <div class="mb-3">
                <label for="channel_id">Channel ID</label>
                <input type="text" name="channel_id" id="channel_id" placeholder="UChPvQ8hfrSW1EAbtBWjis0g" required>
            </div>

            <input type="hidden" name="action" value="fetch">
            <input type="submit" value="Fetch & Save Channel" class="btn btn-primary">
        </form>
    </div>

    <!-- CREATE TAB -->
    <div id="create" style="display:none;" class="tab-target">
        <form method="POST">
            <div class="mb-3">
                <label for="channel_id2">Channel ID</label>
                <input type="text" name="channel_id" id="channel_id2" placeholder="MANUAL_CH_001" required>
            </div>

            <div class="mb-3">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" placeholder="My Manual Channel" required maxlength="120">
            </div>

            <div class="mb-3">
                <label for="vanity_url">Vanity URL</label>
                <input type="url" name="vanity_url" id="vanity_url" placeholder="https://youtube.com/@manualchannel">
            </div>

            <div class="mb-3">
                <label for="subscribers_text">Subscribers Text</label>
                <input type="text" name="subscribers_text" id="subscribers_text" placeholder="3.54K subscribers" maxlength="40">
            </div>

            <div class="mb-3">
                <label for="avatar_url">Avatar URL</label>
                <input type="url" name="avatar_url" id="avatar_url" placeholder="https://example.com/avatar.png" maxlength="255">
            </div>

            <div class="mb-3">
                <label>
                    <input type="checkbox" name="verified" value="1"> Verified
                </label>
            </div>

            <input type="hidden" name="action" value="create">
            <input type="submit" value="Create Channel" class="btn btn-primary">
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    let target = document.getElementById(tab);
    if (target) {
        let eles = document.getElementsByClassName("tab-target");
        for (let ele of eles) {
            ele.style.display = (ele.id === tab) ? "none" : "block";
        }
    }
}
</script>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>