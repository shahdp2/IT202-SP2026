<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Redirect helper with JS + meta fallback (prevents header already sent issues).

function redirect($path) {
    // If they pass a full URL or absolute path, keep it. Otherwise convert to app URL.
    $url = (str_starts_with($path, "http") || str_starts_with($path, "/"))
        ? $path
        : get_url($path);

    if (!headers_sent()) {
        header("Location: $url");
        exit;
    }

    $safe_url = htmlspecialchars($url, ENT_QUOTES, "UTF-8");
    echo "<script>window.location.href='{$safe_url}';</script>";
    echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url={$safe_url}\"/></noscript>";
    exit;
}