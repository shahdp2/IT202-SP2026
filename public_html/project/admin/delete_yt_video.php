<?php
// UCID: dns33
// Date: 04/25/2026
// Summary: Admin hard-delete YT video then redirect back to list.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

session_start();
require_once(__DIR__ . "/../../../lib/functions.php"); // IMPORTANT: not nav.php

if (!has_role("Admin")) {
    flash("You don't have permission to do that", "warning");
    header("Location: " . get_url("landing.php"));
    exit;
}

$id = (int)se($_GET, "id", -1, false);

$return = se($_GET, "return", "", false);
if (empty($return)) {
    $return = get_url("admin/list_yt_videos.php");
}

if ($id < 1) {
    flash("Invalid video id", "warning");
    header("Location: " . get_url("admin/list_yt_videos.php"));
    exit;
}

$db = getDB();

// (Optional) grab video_id for nicer message
$video_id = "";
try {
    $stmt = $db->prepare("SELECT video_id FROM IT202_M2_YT_Videos WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $video_id = $row["video_id"];
} catch (PDOException $e) {
    // ignore, still attempt delete
}

try {
    $stmt = $db->prepare("DELETE FROM IT202_M2_YT_Videos WHERE id = :id");
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        flash("Deleted video " . ($video_id ?: ("id " . $id)), "success");
    } else {
        flash("Video not found (already deleted?)", "warning");
    }
} catch (PDOException $e) {
    error_log("Delete yt video error: " . var_export($e, true));
    flash("Error deleting video", "danger");
}

redirect(get_last_route("admin/list_yt_videos.php"));


