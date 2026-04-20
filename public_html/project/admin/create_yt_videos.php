<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: Admin fetch/create videos (API fetch inserts multiple; manual create inserts one). Handles duplicates safely.

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location:" . get_url("landing.php")));
}

$db = getDB();

if (isset($_POST["action"])) {
    $action = se($_POST, "action", "", false);
    $videos = [];

    if ($action === "fetch") {
        $channelId = trim(se($_POST, "channel_id", "", false));
        $query = trim(se($_POST, "query", "", false));
        $next = trim(se($_POST, "next", "", false));

        if (empty($channelId) || empty($query)) {
            flash("Channel ID and Query are required", "warning");
        } else {
            $api = yt_channel_search($channelId, $query, $next);
            $videos = yt_transform_videos($api, $channelId);

            if (count($videos) === 0) {
                flash("No videos found from API", "warning");
            } else {
                flash("Fetched " . count($videos) . " videos from API", "success");
            }
        }
    }

    if ($action === "create") {
        // Whitelist ONLY your table columns (no id/created/modified)
        $allowed = ["video_id","channel_id","title","channel_name","length_text","published_text","views_text","thumbnail_url"];

        foreach ($_POST as $k => $v) {
            if (!in_array($k, $allowed) && $k !== "action") {
                unset($_POST[$k]);
            }
        }

        // mark manual record
        $_POST["is_api"] = 0;

        // wrap single record so insert loop works same as fetch
        $videos = [$_POST];

        if (empty(se($_POST, "video_id", "", false)) || empty(se($_POST, "channel_id", "", false))) {
            $videos = [];
            flash("Manual create requires Video ID and Channel ID", "warning");
        }
    }

    // ---- INSERT (works for both fetch/create) ----
    if (count($videos) > 0) {
        // Build INSERT from first record (must match table columns)
        $query = "INSERT INTO IT202_M2_YT_Videos ";
        $columns = [];
        $params = [];

        foreach ($videos[0] as $k => $v) {
            // safety: only allow known columns
            if (!in_array($k, ["video_id","channel_id","title","channel_name","length_text","published_text","views_text","thumbnail_url","is_api"])) {
                continue;
            }
            $columns[] = "`$k`";
            $params[":$k"] = null;
        }

        $query .= "(" . join(",", $columns) . ") VALUES (" . join(",", array_keys($params)) . ")";

        // Handle duplicates (video_id UNIQUE)
        // If duplicate, update fields + modified timestamp automatically updates
        $query .= " ON DUPLICATE KEY UPDATE
            `title` = VALUES(`title`),
            `channel_name` = VALUES(`channel_name`),
            `length_text` = VALUES(`length_text`),
            `published_text` = VALUES(`published_text`),
            `views_text` = VALUES(`views_text`),
            `thumbnail_url` = VALUES(`thumbnail_url`),
            `is_api` = VALUES(`is_api`)";

        $stmt = $db->prepare($query);

        $inserted = 0;
        $updated = 0;

        foreach ($videos as $v) {
            foreach ($params as $pk => $_) {
                $col = substr($pk, 1);
                $params[$pk] = se($v, $col, null, false);
            }

            try {
                $stmt->execute($params);

                // rowCount() with ON DUPLICATE KEY can be 1 (insert) or 2 (update), depending on MySQL settings
                $rc = $stmt->rowCount();
                if ($rc === 1) $inserted++;
                else if ($rc >= 2) $updated++;

            } catch (PDOException $e) {
                error_log("YT insert error: " . var_export($e, true));
                flash("A DB error occurred inserting videos", "danger");
                break;
            }
        }

        flash("Done. Inserted: $inserted, Updated (duplicates): $updated", "success");
    }
}
?>

<div class="container-fluid">
    <h3>Create or Fetch YouTube Videos</h3>

    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('fetch'); return false;">Fetch (API)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('create'); return false;">Create (Manual)</a>
        </li>
    </ul>

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
            <input type="submit" value="Fetch Videos" class="btn btn-primary">
        </form>
    </div>

    <div id="create" style="display:none;" class="tab-target">
        <form method="POST">
            <div class="mb-3">
                <label for="video_id">Video ID</label>
                <input type="text" name="video_id" id="video_id" required placeholder="c80ELNJ0LJE">
            </div>

            <div class="mb-3">
                <label for="channel_id2">Channel ID</label>
                <input type="text" name="channel_id" id="channel_id2" required placeholder="MANUAL_CH_001 or real UC...">
            </div>

            <div class="mb-3">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" required maxlength="150">
            </div>

            <div class="mb-3">
                <label for="channel_name">Channel Name</label>
                <input type="text" name="channel_name" id="channel_name" required maxlength="120">
            </div>

            <div class="mb-3">
                <label for="length_text">Length</label>
                <input type="text" name="length_text" id="length_text" maxlength="20" placeholder="4:37">
            </div>

            <div class="mb-3">
                <label for="published_text">Published</label>
                <input type="text" name="published_text" id="published_text" maxlength="30" placeholder="1 year ago">
            </div>

            <div class="mb-3">
                <label for="views_text">Views</label>
                <input type="text" name="views_text" id="views_text" maxlength="40" placeholder="6,965 views">
            </div>

            <div class="mb-3">
                <label for="thumbnail_url">Thumbnail URL</label>
                <input type="url" name="thumbnail_url" id="thumbnail_url" maxlength="255">
            </div>

            <input type="hidden" name="action" value="create">
            <input type="submit" value="Create Video" class="btn btn-primary">
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    let targets = document.getElementsByClassName("tab-target");
    for (let t of targets) {
        t.style.display = (t.id === tab) ? "block" : "none";
    }
}
</script>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>