<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Store & retrieve last route for reliable "Back" redirects (keeps filters/sort/limit).

function store_current_route() {
    // Full path including /project/... and query string
    $current_path = $_SERVER["REQUEST_URI"];

    // Normalize query params (avoid weird double ??)
    if (count($_GET) > 0) {
        $base = preg_replace('/\?.*/', '', $current_path);
        $current_path = $base . "?" . http_build_query($_GET);
    } else {
        $current_path = preg_replace('/\?.*/', '', $current_path);
    }

    $_SESSION["last"] = $current_path;
    error_log("Stored route: " . $current_path);
}

function get_last_route($default = "landing.php") {
    // returns a path like /project/admin/list_yt_videos.php?... or default page
    return isset($_SESSION["last"]) ? $_SESSION["last"] : get_url($default);
}