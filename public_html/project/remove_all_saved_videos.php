<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Remove ALL saved video associations for logged-in user, then redirect back

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);

$db = getDB();
$user_id = get_user_id();

$return = se($_GET, "return", get_url("my_videos.php"), false);
if (!is_string($return) || strpos($return, "/project") !== 0) $return = get_url("my_videos.php");

try {
    $stmt = $db->prepare("UPDATE IT202_M3_UserYTVideos SET is_active=0 WHERE user_id=:uid AND is_active=1");
    $stmt->execute([":uid" => $user_id]);
    flash("Removed all saved videos", "success");
} catch (PDOException $e) {
    error_log("remove_all_saved_videos error: " . var_export($e, true));
    flash("Error removing all saved videos", "danger");
}

header("Location: " . $return);
exit;