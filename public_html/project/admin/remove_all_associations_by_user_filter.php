<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Remove all user-video associations for users matching username filter

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
  flash("You don't have permission to do that", "warning");
  header("Location: " . get_url("landing.php"));
  exit;
}

$db = getDB();

$username = trim(se($_GET, "username", "", false));
$return = se($_GET, "return", get_url("admin/list_user_video_associations.php"), false);
if (!is_string($return) || strpos($return, "/project") !== 0) $return = get_url("admin/list_user_video_associations.php");

if ($username === "") {
  flash("Username filter required", "warning");
  header("Location: " . $return);
  exit;
}

try {
  $stmt = $db->prepare("
    UPDATE IT202_M3_UserYTVideos uv
    JOIN Users u ON u.id = uv.user_id
    SET uv.is_active = 0
    WHERE uv.is_active = 1 AND u.username LIKE :u
  ");
  $stmt->execute([":u" => "%$username%"]);
  flash("Removed associations for matching users", "success");
} catch(PDOException $e) {
  error_log("remove_all_associations_by_user_filter error: ".var_export($e,true));
  flash("Error removing associations", "danger");
}

header("Location: " . $return);
exit;