<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: Admin Create/Fetch YouTube Videos (multiple entities) following create_company.php template.

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location:" . get_url("landing.php")));
}

if (isset($_POST["action"])) {
    $action = se($_POST, "action", "", false);
    $videos = [];

    if ($action === "fetch") {
        $channelId = trim(se($_POST, "channel_id", "", false));
        $query     = trim(se($_POST, "query", "", false));
        $next      = trim(se($_POST, "next", "", false));

        if ($channelId && $query) {
            $bundle = yt_channel_search($channelId, $query, $next);

            // IMPORTANT: yt_channel_search returns ["videos"=> already transformed rows]
            $videos = se($bundle, "videos", [], false);

            error_log("YT videos from API: " . var_export($videos, true));

            if (!$videos) {
                flash("No videos returned from API", "warning");
            }
        } else {
            flash("You must provide Channel ID and Query", "warning");
        }

    } elseif ($action === "create") {

        foreach ($_POST as $k => $v) {
            if (!in_array($k, ["video_id","channel_id","title","channel_name","length_text","published_text","views_text","thumbnail_url"])) {
                unset($_POST[$k]);
            }
        }

        $_POST["is_api"] = 0;
        $videos = [$_POST]; // wrap single record like company template

        $video_id     = trim(se($_POST, "video_id", "", false));
        $channel_id   = trim(se($_POST, "channel_id", "", false));
        $title        = trim(se($_POST, "title", "", false));
        $channel_name = trim(se($_POST, "channel_name", "", false)); // include this if your form has it
        
        if ($video_id === "" || $channel_id === "" || $title === "" || $channel_name === "") {
            $videos = [];
            flash("Manual create requires Video ID, Channel ID, Title, and Channel Name", "warning");
        }

        error_log("Manual video: " . var_export($videos, true));
    }

    // INSERT (like company template)
    if (count($videos) > 0) {
        $db = getDB();

        $querySql = "INSERT INTO `IT202_M2_YT_Videos` ";
        $columns = [];
        $params = [];

        // build from first record
        foreach ($videos[0] as $k => $v) {
            $columns[] = "`$k`";
            $params[":$k"] = null;
        }

        $querySql .= "(" . join(",", $columns) . ")";
        $querySql .= " VALUES (" . join(",", array_keys($params)) . ")";

        foreach ($videos as $vid) {
            foreach ($vid as $k => $v) {
                $params[":$k"] = $v;
            }

            error_log("Query: " . $querySql);
            error_log("Params: " . var_export($params, true));

            try {
                $stmt = $db->prepare($querySql);
                $stmt->execute($params);
                flash("Inserted record " . $db->lastInsertId(), "success");
            } catch (PDOException $e) {
                error_log("YT Video insert error: " . var_export($e, true));
                flash("Insert failed (likely duplicate video_id).", "danger");
            }
        }
    } else {
        flash("No video fetched or provided", "warning");
    }
}
?>

<div class="container-fluid">
    <h3>Create or Fetch YouTube Videos</h3>

    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('fetch'); return false;">Fetch</a>
        </li>
        <li class="nav-item">
            <a class="nav-link bg-success" href="#" onclick="switchTab('create'); return false;">Create</a>
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
            <input type="submit" value="Fetch & Save" class="btn btn-primary">
        </form>
    </div>

    <div id="create" style="display:none;" class="tab-target">
        <form method="POST" onsubmit="return validateVideoForm();">
            <div class="mb-3">
                <label for="video_id">Video ID</label>
                <input type="text" name="video_id" id="video_id" required placeholder="c80ELNJ0LJE">
            </div>

            <div class="mb-3">
                <label for="channel_id2">Channel ID</label>
                <input type="text" name="channel_id" id="channel_id2" required placeholder="MANUAL_CH_001">
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
                <input type="text" name="length_text" id="length_text" maxlength="20">
            </div>

            <div class="mb-3">
                <label for="published_text">Published</label>
                <input type="text" name="published_text" id="published_text" maxlength="30">
            </div>

            <div class="mb-3">
                <label for="views_text">Views</label>
                <input type="text" name="views_text" id="views_text" maxlength="40">
            </div>

            <div class="mb-3">
                <label for="thumbnail_url">Thumbnail URL</label>
                <input type="url" name="thumbnail_url" id="thumbnail_url" maxlength="255">
            </div>

            <input type="hidden" name="action" value="create">
            <input type="submit" value="Create" class="btn btn-primary">
        </form>
    </div>
</div>

<script>
// UCID: dns33
function switchTab(tab) {
    let targets = document.getElementsByClassName("tab-target");
    for (let ele of targets) {
        ele.style.display = (ele.id === tab) ? "block" : "none";
    }
}

function validateVideoForm() {

  const videoId = document.getElementById("video_id")?.value?.trim() || "";

  if (videoId.length < 6) {

    alert("Video ID must be at least 6 characters.");

    return false;

  }

  return true;
}
</script>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>