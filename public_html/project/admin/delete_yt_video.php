<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// Get id
$id = (int)se($_GET, "id", -1, false);
if ($id < 1) {
    flash("Invalid video id", "danger");
    die(header("Location: " . get_url("admin/list_yt_videos.php")));
}

$db = getDB();

try {
    // Optional: fetch a small piece for nicer message
    $stmt = $db->prepare("SELECT video_id FROM IT202_M2_YT_Videos WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        flash("Video not found", "warning");
        die(header("Location: " . get_url("admin/list_yt_videos.php")));
    }

    // Hard delete
    $stmt = $db->prepare("DELETE FROM IT202_M2_YT_Videos WHERE id = :id");
    $stmt->execute([":id" => $id]);

    flash("Deleted video " . se($row, "video_id", "", false), "success");
} catch (PDOException $e) {
    error_log("Delete video error: " . var_export($e, true));
    flash("Error deleting video", "danger");
}

die(header("Location: " . get_url("admin/list_yt_videos.php")));