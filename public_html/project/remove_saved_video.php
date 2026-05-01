<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Remove ONE saved video association (soft delete), then redirect back

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);

$db = getDB();
$user_id = get_user_id();
$video_db_id = (int)se($_GET, "id", -1, false);

$return = se($_GET, "return", get_url("my_videos.php"), false);
if (!is_string($return) || strpos($return, "/project") !== 0) $return = get_url("my_videos.php");

if ($video_db_id < 1) {
    flash("Invalid video id", "warning");
    header("Location: " . $return);
    exit;
}

try {
    $stmt = $db->prepare("UPDATE IT202_M3_UserYTVideos
                          SET is_active = 0
                          WHERE user_id = :uid AND yt_video_id = :vid");
    $stmt->execute([":uid"=>$user_id, ":vid"=>$video_db_id]);
    flash("Removed video from your watchlist", "success");
} catch (PDOException $e) {
    error_log("remove_saved_video error: " . var_export($e, true));
    flash("Error removing saved video", "danger");
}

header("Location: " . $return);
exit;