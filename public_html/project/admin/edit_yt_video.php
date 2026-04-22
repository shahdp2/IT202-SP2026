<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: Admin edit page for YT Videos using update() helper.

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$id = (int)se($_GET, "id", -1, false);

// UPDATE first
if ($id > 0 && isset($_POST["title"])) {
    // Do NOT allow editing unique identity fields like video_id/channel_id
    $allowed = ["title", "channel_name", "length_text", "published_text", "views_text", "thumbnail_url", "is_api"];

    foreach ($_POST as $k => $v) {
        if (!in_array($k, $allowed, true)) {
            unset($_POST[$k]);
        }
    }

    $_POST["is_api"] = isset($_POST["is_api"]) ? 1 : 0;
    $_POST["id"] = $id;

    try {
        $r = update("IT202_M2_YT_Videos", $_POST);
        if ($r["rowCount"]) {
            flash("Updated " . $r["rowCount"] . " record(s)", "success");
        } else {
            flash("No changes made (or same values submitted)", "warning");
        }
    } catch (Exception $e) {
        error_log("YT video update error: " . var_export($e, true));
        flash("Error updating video", "danger");
    }
}

// LOAD record
$video = [];
if ($id > 0) {
    $db = getDB();
    $query = "SELECT video_id, channel_id, title, channel_name, length_text, published_text, views_text, thumbnail_url, is_api
              FROM IT202_M2_YT_Videos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        flash("Video not found", "warning");
        die(header("Location: " . get_url("admin/list_yt_videos.php")));
    }
} else {
    flash("Invalid id passed", "danger");
    die(header("Location: " . get_url("admin/list_yt_videos.php")));
}
?>

<div class="container-fluid">
    <h3>Edit YouTube Video</h3>

    <p><b>Video ID (read-only):</b> <?php se($video, "video_id"); ?></p>
    <p><b>Channel ID (read-only):</b> <?php se($video, "channel_id"); ?></p>

    <form method="POST">
        <div class="mb-3">
            <label for="title">Video Title</label>
            <input type="text" name="title" id="title" required maxlength="150" value="<?php se($video, "title"); ?>">
        </div>

        <div class="mb-3">
            <label for="channel_name">Channel Name</label>
            <input type="text" name="channel_name" id="channel_name" required maxlength="120" value="<?php se($video, "channel_name"); ?>">
        </div>

        <div class="mb-3">
            <label for="length_text">Length</label>
            <input type="text" name="length_text" id="length_text" maxlength="20" value="<?php se($video, "length_text"); ?>">
        </div>

        <div class="mb-3">
            <label for="published_text">Published</label>
            <input type="text" name="published_text" id="published_text" maxlength="30" value="<?php se($video, "published_text"); ?>">
        </div>

        <div class="mb-3">
            <label for="views_text">Views</label>
            <input type="text" name="views_text" id="views_text" maxlength="40" value="<?php se($video, "views_text"); ?>">
        </div>

        <div class="mb-3">
            <label for="thumbnail_url">Thumbnail URL</label>
            <input type="url" name="thumbnail_url" id="thumbnail_url" maxlength="255" value="<?php se($video, "thumbnail_url"); ?>">
        </div>

        <div class="mb-3">
            <label>
                <input type="checkbox" name="is_api" <?php echo (se($video, "is_api", 1, false) ? "checked" : ""); ?>>
                API Record
            </label>
        </div>

        <input type="submit" value="Update" class="btn btn-primary">
    </form>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>