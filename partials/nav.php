<?php
// UCID: dns33
// Date: 04/25/2026
// Summary: Nav with session setup + Admin dropdown menus (valid UL/LI structure)

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

// checking to see if domain has a port number attached (localhost)
$domain = $_SERVER["HTTP_HOST"];
if (strpos($domain, ":")) {
    $domain = explode(":", $domain)[0];
}

// used for public hosting like render
if ($domain != "localhost") {
    session_set_cookie_params([
        "lifetime" => 60 * 60,
        "path" => "/project",
        "domain" => $domain,
        "secure" => true,
        "httponly" => true,
        "samesite" => "lax"
    ]);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once(__DIR__ . "/../lib/functions.php");

$current = basename(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)); // ex: landing.php
$hide_main_links = ($current === "landing.php");

?>

<link rel="stylesheet" href="<?php get_url('styles.css', true); ?>">
<script src="<?php get_url('helpers.js', true); ?>"></script>
<script src="https://matttoegel.github.io/IT202-Utils/submission-utils.js"></script>

<nav>
    <ul>
        <?php if (is_logged_in()) : ?>
        
            <?php if (!$hide_main_links): ?>
                <li><a href="<?php get_url('landing.php', true); ?>">Landing</a></li>
                <li><a href="<?php get_url('profile.php', true); ?>">Profile</a></li>
                <li><a href="<?php get_url('my_videos.php', true); ?>">My Videos</a></li>
                <li><a href="<?php get_url('videos.php', true); ?>">Videos</a></li>
            <?php endif; ?>
            
        <?php else : ?>
            <li><a href="<?php get_url('login.php', true); ?>">Login</a></li>
            <li><a href="<?php get_url('register.php', true); ?>">Register</a></li>
        <?php endif; ?>

        <?php if (has_role("Admin")) : ?>
            <!-- Roles dropdown -->
            <li class="dropdown">
                <button class="dropbtn" type="button">Roles</button>
                <div class="dropdown-content">
                    <a href="<?php get_url('admin/create_role.php', true); ?>">Create Role</a>
                    <a href="<?php get_url('admin/list_roles.php', true); ?>">List Roles</a>
                    <a href="<?php get_url('admin/assign_roles.php', true); ?>">Assign Roles</a>
                </div>
            </li>

            <!-- YouTube dropdown (Milestone 2) -->
            <li class="dropdown">
                <button class="dropbtn" type="button">YouTube</button>
                <div class="dropdown-content">
                    <a href="<?php get_url('admin/create_yt_channel.php', true); ?>">Create/Fetch Channel</a>
                    <a href="<?php get_url('admin/list_yt_channels.php', true); ?>">List Channels</a>
                    <a href="<?php get_url('admin/create_yt_videos.php', true); ?>">Create/Fetch Video</a>
                    <a href="<?php get_url('admin/list_yt_videos.php', true); ?>">List Videos</a>
                </div>
            </li>
        <?php endif; ?>
        <?php if (is_logged_in()) : ?>
          <?php if (!$hide_main_links): ?>
            <!-- main links -->
          <?php endif; ?>
        
          <!-- always show -->
          <li class="logout">
            <a href="<?php get_url('logout.php', true); ?>">Logout</a>
          </li>
        <?php endif; ?>
    </ul>
</nav>