<?php
// UCID: dns33
// Date: 04/24/2026
// Summary: Admin view page for a single YouTube Video (details page)

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$id = (int)se($_GET, "id", -1, false);
if ($id < 1) {
    flash("Invalid video id", "danger");
    die(header("Location: " . get_url("admin/list_yt_videos.php")));
}

$db = getDB();
$video = [];

$query = "SELECT id, video_id, channel_id, title, channel_name, length_text, published_text, views_text, thumbnail_url, is_api, created, modified
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
    flash("Error loading video", "danger");
    die(header("Location: " . get_url("admin/list_yt_videos.php")));
}
?>

<div class="container-fluid">
    <h3 class="mb-3">YouTube Video Details</h3>

    <div class="card p-3">
        <table class="table">
            <tr><th>ID</th><td><?php se($video, "id"); ?></td></tr>
            <tr><th>Video ID</th><td><?php se($video, "video_id"); ?></td></tr>
            <tr><th>Channel ID</th><td><?php se($video, "channel_id"); ?></td></tr>
            <tr><th>Title</th><td><?php se($video, "title"); ?></td></tr>
            <tr><th>Channel Name</th><td><?php se($video, "channel_name"); ?></td></tr>
            <tr><th>Length</th><td><?php se($video, "length_text", "N/A"); ?></td></tr>
            <tr><th>Published</th><td><?php se($video, "published_text", "N/A"); ?></td></tr>
            <tr><th>Views</th><td><?php se($video, "views_text", "N/A"); ?></td></tr>
            <tr><th>Thumbnail</th>
                <td>
                    <?php if (!empty($video["thumbnail_url"])): ?>
                        <img src="<?php se($video, "thumbnail_url"); ?>" alt="thumbnail" style="max-height:90px;">
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <tr><th>is_api</th><td><?php echo !empty($video["is_api"]) ? "1" : "0"; ?></td></tr>
            <tr><th>Created</th><td><?php se($video, "created"); ?></td></tr>
            <tr><th>Modified</th><td><?php se($video, "modified"); ?></td></tr>
        </table>

        <div class="mt-2">
            <a class="btn btn-primary" href="<?php echo get_url("admin/edit_yt_video.php"); ?>?id=<?php se($video, "id"); ?>">Edit</a>
            <a class="btn btn-secondary" href="<?php echo get_url("admin/list_yt_videos.php"); ?>">Back to List</a>
            <a class="btn btn-danger"
                href="<?php echo get_url("admin/delete_yt_video.php"); ?>?id=<?php se($video, "id"); ?>"
                onclick="return confirm('Delete this video?');">
                Delete
            </a>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>