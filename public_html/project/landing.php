<?php
ob_start();
require(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);

$db = getDB();
$user_id = get_user_id();
$username = get_username();

// Optional stats
$saved_count = 0;
$total_videos = 0;

try {
    $r = selectAll(
        "SELECT COUNT(*) AS c FROM IT202_M3_UserYTVideos WHERE user_id = :uid",
        [":uid" => $user_id]
    );
    $saved_count = (int)se($r[0] ?? [], "c", 0, false);
} catch (Exception $e) {
    error_log("Landing saved_count error: " . var_export($e, true));
}

try {
    $r = selectAll("SELECT COUNT(*) AS c FROM IT202_M2_YT_Videos", []);
    $total_videos = (int)se($r[0] ?? [], "c", 0, false);
} catch (Exception $e) {
    error_log("Landing total_videos error: " . var_export($e, true));
}
?>

<div class="landing-wrap">
  <div class="landing-hero">

    <h1 class="hero-title">Welcome back, <span><?php echo htmlspecialchars($username); ?></span></h1>
    <p class="hero-subtitle">Browse videos, save to your watchlist, and manage your profile.</p>
  </div>

  <div class="landing-grid">
    <!-- Actions -->
    <div class="card soft-card">
      <div class="card-header-clean">
        <div class="card-title-row">
          <span class="card-icon">▶</span>
          <h3 class="card-title">Quick Actions</h3>
        </div>
        <p class="card-subtitle">Jump to the most common pages.</p>
      </div>

      <div class="action-cards">
        <a class="action-tile" href="<?php echo get_url("videos.php", true); ?>">
          <div class="tile-icon">🔎</div>
          <div class="tile-text">
            <div class="tile-title">Browse Videos</div>
            <div class="tile-sub">Search, sort, save/unsave</div>
          </div>
        </a>

        <a class="action-tile" href="<?php echo get_url("my_videos.php", true); ?>">
          <div class="tile-icon">⭐</div>
          <div class="tile-text">
            <div class="tile-title">My Watchlist</div>
            <div class="tile-sub">Your saved videos</div>
          </div>
        </a>

        <a class="action-tile" href="<?php echo get_url("profile.php", true); ?>">
          <div class="tile-icon">👤</div>
          <div class="tile-text">
            <div class="tile-title">Profile</div>
            <div class="tile-sub">View / edit your account</div>
          </div>
        </a>
      </div>
    </div>

    <!-- Stats -->
    <div class="card soft-card">
      <div class="card-header-clean">
        <div class="card-title-row">
          <span class="card-icon">📊</span>
          <h3 class="card-title">Stats</h3>
        </div>
        <p class="card-subtitle">Quick snapshot of your activity.</p>
      </div>

      <div class="stat-grid">
        <div class="stat-tile">
          <div class="stat-label">Saved videos</div>
          <div class="stat-value"><?php echo htmlspecialchars((string)$saved_count); ?></div>
        </div>

        <div class="stat-tile">
          <div class="stat-label">Total videos in DB</div>
          <div class="stat-value"><?php echo htmlspecialchars((string)$total_videos); ?></div>
        </div>
      </div>

      <div class="stat-footer">
        <a class="btn btn-primary btn-lg w-100" href="<?php echo get_url("videos.php", true); ?>">
          Start browsing
        </a>
      </div>
    </div>

    <?php if (has_role("Admin")): ?>
      <!-- Admin -->
      <div class="card soft-card admin-card">
        <div class="card-header-clean">
          <div class="card-title-row">
            <span class="card-icon">🛠</span>
            <h3 class="card-title">Admin</h3>
          </div>
          <p class="card-subtitle">Manage API data + CRUD pages.</p>
        </div>

        <div class="admin-links">
          <a class="mini-link" href="<?php echo get_url("admin/create_yt_videos.php", true); ?>">Create/Fetch Video</a>
          <a class="mini-link" href="<?php echo get_url("admin/list_yt_videos.php", true); ?>">List Videos</a>
          <a class="mini-link" href="<?php echo get_url("admin/create_yt_channel.php", true); ?>">Create/Fetch Channel</a>
          <a class="mini-link" href="<?php echo get_url("admin/list_yt_channels.php", true); ?>">List Channels</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require(__DIR__ . "/../../partials/flash.php"); ?>