<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: Admin edit page for YT Videos (update first, then reload record)

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$id = (int)se($_GET, "id", -1, false);

// UPDATE first
if ($id > 0 && isset($_POST["video_id"])) {

    $allowed = [
        "video_id", "channel_id", "title", "channel_name",
        "length_text", "published_text", "views_text", "thumbnail_url", "is_api"
    ];

    foreach ($_POST as $k => $v) {
        if (!in_array($k, $allowed)) {
            unset($_POST[$k]);
        }
    }

    $_POST["is_api"] = isset($_POST["is_api"]) ? 1 : 0;
    $video = $_POST;

    $db = getDB();
    $query = "UPDATE IT202_M2_YT_Videos SET ";
    $params = [];

    foreach ($video as $k => $v) {
        if ($params) {
            $query .= ",";
        }
        $query .= "`$k`=:$k";
        $params[":$k"] = $v;
    }

    $query .= " WHERE id = :id";
    $params[":id"] = $id;

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Updated video", "success");
    } catch (PDOException $e) {
        error_log("Error updating video: " . var_export($e, true));
        flash("An error occurred updating the video", "danger");
    }
}

// SELECT record
$video = [];
if ($id > 0) {
    $db = getDB();
    $query = "SELECT video_id, channel_id, title, channel_name, length_text, published_text, views_text, thumbnail_url, is_api
              FROM IT202_M2_YT_Videos
              WHERE id = :id";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([":id" => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $video = $r;
        } else {
            flash("Video not found", "warning");
            die(header("Location: " . get_url("admin/list_yt_videos.php")));
        }
    } catch (PDOException $e) {
        error_log("Error fetching video: " . var_export($e, true));
        flash("Error fetching video", "danger");
        die(header("Location: " . get_url("admin/list_yt_videos.php")));
    }
} else {
    flash("Invalid id passed", "danger");
    die(header("Location: " . get_url("admin/list_yt_videos.php")));
}
?>

<div class="container-fluid">
    <h3>Edit YouTube Video</h3>

    <form method="POST">
        <div class="mb-3">
            <label for="video_id">Video ID</label>
            <input type="text" name="video_id" id="video_id" required value="<?php se($video, "video_id"); ?>">
        </div>

        <div class="mb-3">
            <label for="channel_id">Channel ID</label>
            <input type="text" name="channel_id" id="channel_id" required value="<?php se($video, "channel_id"); ?>">
        </div>

        <div class="mb-3">
            <label for="title">Video Title</label>
            <input type="text" name="title" id="title" required value="<?php se($video, "title"); ?>">
        </div>

        <div class="mb-3">
            <label for="channel_name">Channel Name</label>
            <input type="text" name="channel_name" id="channel_name" required value="<?php se($video, "channel_name"); ?>">
        </div>

        <div class="mb-3">
            <label for="length_text">Length</label>
            <input type="text" name="length_text" id="length_text" value="<?php se($video, "length_text"); ?>">
        </div>

        <div class="mb-3">
            <label for="published_text">Published</label>
            <input type="text" name="published_text" id="published_text" value="<?php se($video, "published_text"); ?>">
        </div>

        <div class="mb-3">
            <label for="views_text">Views</label>
            <input type="text" name="views_text" id="views_text" value="<?php se($video, "views_text"); ?>">
        </div>

        <div class="mb-3">
            <label for="thumbnail_url">Thumbnail URL</label>
            <input type="url" name="thumbnail_url" id="thumbnail_url" value="<?php se($video, "thumbnail_url"); ?>">
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