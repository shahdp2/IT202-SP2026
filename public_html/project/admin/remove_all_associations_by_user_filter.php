<?php
// UCID: dns33
// Date: 05/xx/2026
// Summary: Admin action to remove all user<->video associations for matching username filter

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set("display_errors", "0");

session_start();
require_once(__DIR__ . "/../../../lib/functions.php"); // ✅ no nav.php

if (!has_role("Admin")) {
  flash("You don't have permission to do that", "warning");
  redirect("landing.php");
}

$db = getDB();

// inputs
$username = trim(se($_GET, "username", "", false));
$return = se($_GET, "return", "", false);

// safe return (stay inside /project)
if (!is_string($return) || strpos($return, "/project") !== 0) {
  $return = get_url("admin/list_user_video_associations.php");
}

if ($username === "") {
  flash("Username filter is required", "warning");
  redirect($return);
}

try {
  // Soft delete relationships for ALL matching users
  $sql = "
    UPDATE IT202_M3_UserYTVideos uv
    JOIN Users u ON u.id = uv.user_id
    SET uv.is_active = 0
    WHERE uv.is_active = 1 AND u.username LIKE :uname
  ";
  $stmt = $db->prepare($sql);
  $stmt->execute([":uname" => "%$username%"]);
  $count = $stmt->rowCount();

  flash("Removed $count association(s) for users matching '$username'", "success");
} catch (PDOException $e) {
  error_log("remove_all_associations_by_user_filter error: " . var_export($e, true));
  flash("Error removing associations", "danger");
}

redirect($return);