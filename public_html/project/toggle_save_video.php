<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Toggle user<->video association (save/unsave) then redirect back

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

session_start();
require_once(__DIR__ . "/../../lib/functions.php"); // IMPORTANT: no nav.php

is_logged_in(true);

$user_id = (int)get_user_id();
$video_db_id = (int)se($_GET, "id", -1, false);

// Prefer explicit return, otherwise last route, otherwise fallback
$return = se($_GET, "return", "", false);
if (!is_string($return) || strpos($return, "/project") !== 0) {
    // fallback to stored route (you already call store_current_route() on list pages)
    $return = isset($_SESSION["last"]) ? $_SESSION["last"] : get_url("admin/list_yt_videos.php");
}

if ($video_db_id < 1) {
    flash("Invalid video id", "warning");
    redirect($return);
}

// --- DEBUG LOGGING (safe, goes to error_log, not user) ---
error_log("toggle_save_video: user_id=$user_id video_db_id=$video_db_id");

try {
    $db = getDB();

    // Check existing relationship
    $stmt = $db->prepare("
        SELECT id, is_active
        FROM IT202_M3_UserYTVideos
        WHERE user_id = :uid AND yt_video_id = :vid
        LIMIT 1
    ");
    $stmt->execute([":uid" => $user_id, ":vid" => $video_db_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && (int)$row["is_active"] === 1) {
        // soft-remove relationship
        $stmt = $db->prepare("
            UPDATE IT202_M3_UserYTVideos
            SET is_active = 0
            WHERE user_id = :uid AND yt_video_id = :vid
        ");
        $stmt->execute([":uid" => $user_id, ":vid" => $video_db_id]);
        flash("Removed video from your watchlist", "success");
    } elseif ($row) {
        // re-activate
        $stmt = $db->prepare("
            UPDATE IT202_M3_UserYTVideos
            SET is_active = 1
            WHERE user_id = :uid AND yt_video_id = :vid
        ");
        $stmt->execute([":uid" => $user_id, ":vid" => $video_db_id]);
        flash("Saved video to your watchlist", "success");
    } else {
        // insert new relationship
        $stmt = $db->prepare("
            INSERT INTO IT202_M3_UserYTVideos (user_id, yt_video_id, is_active)
            VALUES (:uid, :vid, 1)
        ");
        $stmt->execute([":uid" => $user_id, ":vid" => $video_db_id]);
        flash("Saved video to your watchlist", "success");
    }
} catch (PDOException $e) {
    // Log real DB error, show user-friendly message
    error_log("toggle_save_video PDO error: " . var_export($e->errorInfo, true));
    flash("Error saving video (please try again)", "danger");
}

redirect($return);