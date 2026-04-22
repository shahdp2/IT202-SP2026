<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: Admin Create/Fetch YouTube Channel (single entity) following create_stock.php template.

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

if (isset($_POST["action"])) {
    $action = se($_POST, "action", "", false);

    // single row like $quote in create_stock.php
    $channel = [];

    if ($action === "fetch") {
        $channel_id = trim(se($_POST, "channel_id", "", false));
        $query      = trim(se($_POST, "query", "", false));
        $next       = trim(se($_POST, "next", "", false));

        if ($channel_id && $query) {
            // returns bundle: ["raw"=>..., "channel"=>..., "videos"=>...]
            $bundle = yt_channel_search($channel_id, $query, $next);

            error_log("YT bundle: " . var_export($bundle, true));

            $channel = se($bundle, "channel", [], false);
            if ($channel) {
                $channel["is_api"] = 1; // already true in transform, safe
            } else {
                flash("No channel data returned from API.", "warning");
            }
        } else {
            flash("You must provide a Channel ID and Query.", "warning");
        }

    } elseif ($action === "create") {
        // whitelist only DB columns
        foreach ($_POST as $k => $v) {
            if (!in_array($k, ["channel_id","title","vanity_url","verified","subscribers_text","avatar_url"])) {
                unset($_POST[$k]);
            }
        }

        $channel = $_POST;
        $channel["is_api"] = 0;
        $channel["verified"] = !empty($channel["verified"]) ? 1 : 0;

        // required checks
        if (empty(trim(se($channel, "channel_id", "", false))) || empty(trim(se($channel, "title", "", false)))) {
            $channel = [];
            flash("Channel ID and Title are required.", "warning");
        }

        error_log("Manual channel: " . var_export($channel, true));
    }

    // INSERT one record (same pattern as stock)
    if (!empty($channel)) {
        $db = getDB();

        $querySql = "INSERT INTO `IT202_M2_YT_Channels` ";
        $columns = [];
        $params = [];

        foreach ($channel as $k => $v) {
            $columns[] = "`$k`";
            $params[":$k"] = $v;
        }

        $querySql .= "(" . join(",", $columns) . ")";
        $querySql .= " VALUES (" . join(",", array_keys($params)) . ")";

        error_log("Query: " . $querySql);
        error_log("Params: " . var_export($params, true));

        try {
            $stmt = $db->prepare($querySql);
            $stmt->execute($params);
            flash("Inserted record " . $db->lastInsertId(), "success");
        } catch (PDOException $e) {
            error_log("YT Channel insert error: " . var_export($e, true));
            flash("Insert failed (likely duplicate channel_id).", "danger");
        }
    }
}
?>

<div class="container-fluid">
    <h3>Create or Fetch YouTube Channel</h3>

    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('fetch'); return false;">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('create'); return false;">Create</a>
        </li>
    </ul>

    <!-- FETCH TAB -->
    <div id="fetch" class="tab-target">
        <form method="POST">
            <div class="mb-3">
                <label for="channel_id">Channel ID</label>
                <input type="text" name="channel_id" id="channel_id" required placeholder="UChPvQ8hfrSW1EAbtBWjis0g">
            </div>

            <div class="mb-3">
                <label for="query">Query</label>
                <input type="text" name="query" id="query" required placeholder="news">
            </div>

            <div class="mb-3">
                <label for="next">Next (optional)</label>
                <input type="text" name="next" id="next" placeholder="pagination token">
            </div>

            <input type="hidden" name="action" value="fetch">
            <input type="submit" value="Fetch & Save" class="btn btn-primary">
        </form>
    </div>

    <!-- CREATE TAB -->
    <div id="create" style="display:none;" class="tab-target">
        <form method="POST">
            <div class="mb-3">
                <label for="channel_id2">Channel ID</label>
                <input type="text" name="channel_id" id="channel_id2" required placeholder="MANUAL_CH_001">
            </div>

            <div class="mb-3">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" required maxlength="120" placeholder="My Manual Channel">
            </div>

            <div class="mb-3">
                <label for="vanity_url">Vanity URL</label>
                <input type="url" name="vanity_url" id="vanity_url" placeholder="https://youtube.com/@manualchannel">
            </div>

            <div class="mb-3">
                <label for="subscribers_text">Subscribers Text</label>
                <input type="text" name="subscribers_text" id="subscribers_text" maxlength="40" placeholder="3.54K subscribers">
            </div>

            <div class="mb-3">
                <label for="avatar_url">Avatar URL</label>
                <input type="url" name="avatar_url" id="avatar_url" maxlength="255" placeholder="https://example.com/avatar.png">
            </div>

            <div class="mb-3">
                <label>
                    <input type="checkbox" name="verified" value="1"> Verified
                </label>
            </div>

            <input type="hidden" name="action" value="create">
            <input type="submit" value="Create" class="btn btn-primary">
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    let targets = document.getElementsByClassName("tab-target");
    for (let ele of targets) {
        ele.style.display = (ele.id === tab) ? "block" : "none";
    }
}
</script>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>