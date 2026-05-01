<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Toggle user<->video association (save/unsave) then redirect back

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);

$db = getDB();
$user_id = get_user_id();
$video_db_id = (int)se($_GET, "id", -1, false);

$return = se($_GET, "return", get_url("videos.php"), false);
// basic safe redirect (stay inside /project)
if (!is_string($return) || strpos($return, "/project") !== 0) {
  $return = get_url("videos.php");
}

if ($video_db_id < 1) {
  flash("Invalid video id", "warning");
  header("Location: " . $return);
  exit;
}

try {
  // does active association exist?
  $stmt = $db->prepare("SELECT id, is_active FROM IT202_M3_UserYTVideos WHERE user_id=:uid AND yt_video_id=:vid LIMIT 1");
  $stmt->execute([":uid"=>$user_id, ":vid"=>$video_db_id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($row && (int)$row["is_active"] === 1) {
    // unsave (soft delete)
    $stmt = $db->prepare("UPDATE IT202_M3_UserYTVideos SET is_active=0 WHERE user_id=:uid AND yt_video_id=:vid");
    $stmt->execute([":uid"=>$user_id, ":vid"=>$video_db_id]);
    flash("Removed video from your watchlist", "success");
  } else if ($row) {
    // re-activate
    $stmt = $db->prepare("UPDATE IT202_M3_UserYTVideos SET is_active=1 WHERE user_id=:uid AND yt_video_id=:vid");
    $stmt->execute([":uid"=>$user_id, ":vid"=>$video_db_id]);
    flash("Saved video to your watchlist", "success");
  } else {
    // insert new
    $stmt = $db->prepare("INSERT INTO IT202_M3_UserYTVideos (user_id, yt_video_id, is_active) VALUES (:uid, :vid, 1)");
    $stmt->execute([":uid"=>$user_id, ":vid"=>$video_db_id]);
    flash("Saved video to your watchlist", "success");
  }
} catch (PDOException $e) {
  error_log("toggle_save_video error: " . var_export($e, true));
  flash("Error saving video", "danger");
}

header("Location: " . $return);
exit;