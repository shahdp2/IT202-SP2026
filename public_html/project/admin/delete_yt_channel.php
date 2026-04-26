<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

session_start();
require_once(__DIR__ . "/../../../lib/functions.php");

if (!has_role("Admin")) {
    flash("You don't have permission to do that", "warning");
    header("Location: " . get_url("landing.php"));
    exit;
}

$id = (int)se($_GET, "id", -1, false);
if ($id < 1) {
    flash("Invalid channel id", "warning");
    header("Location: " . get_url("admin/list_yt_channels.php"));
    exit;
}

$db = getDB();
$channel_id = "";

try {
    $stmt = $db->prepare("SELECT channel_id FROM IT202_M2_YT_Channels WHERE id = :id");
    $stmt->execute([":id" => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $channel_id = $row["channel_id"];
} catch (PDOException $e) {}

try {
    $stmt = $db->prepare("DELETE FROM IT202_M2_YT_Channels WHERE id = :id");
    $stmt->execute([":id" => $id]);

    if ($stmt->rowCount() > 0) {
        flash("Deleted channel " . ($channel_id ?: ("id " . $id)), "success");
    } else {
        flash("Channel not found (already deleted?)", "warning");
    }
} catch (PDOException $e) {
    error_log("Delete yt channel error: " . var_export($e, true));
    flash("Error deleting channel", "danger");
}

header("Location: " . get_url("admin/list_yt_channels.php"));
exit;