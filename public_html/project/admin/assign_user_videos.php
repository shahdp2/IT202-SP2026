<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Admin assign user<->video associations (toggle) similar to assign_roles

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

session_start();
require_once(__DIR__ . "/../../../lib/functions.php"); // IMPORTANT: no nav.php yet (no HTML output)

if (!has_role("Admin")) {
  flash("You don't have permission to view this page", "warning");
  redirect("landing.php");
}

$db = getDB();

$video_q = trim(se($_GET, "video", "", false));
$user_q  = trim(se($_GET, "user", "", false));

$did_search = ($video_q !== "" || $user_q !== "");

$videos = [];
$users  = [];

/* ---------- POST: apply toggles (must be BEFORE nav.php) ---------- */
if (isset($_POST["apply"])) {
  $video_ids = se($_POST, "video_ids", [], false);
  $user_ids  = se($_POST, "user_ids", [], false);

  $video_q = trim(se($_POST, "video_q", "", false));
  $user_q  = trim(se($_POST, "user_q", "", false));

  if (!is_array($video_ids) || !is_array($user_ids) || count($video_ids) === 0 || count($user_ids) === 0) {
    flash("Select at least 1 user and 1 video", "warning");
  } else {
    $toggled = 0;

    // prepare ONCE (performance + cleaner)
    $check = $db->prepare("SELECT id, is_active FROM IT202_M3_UserYTVideos WHERE user_id=:u AND yt_video_id=:v LIMIT 1");
    $upd   = $db->prepare("UPDATE IT202_M3_UserYTVideos SET is_active=:a WHERE id=:id");
    $ins   = $db->prepare("INSERT INTO IT202_M3_UserYTVideos (user_id, yt_video_id, is_active) VALUES (:u,:v,1)");

    foreach ($user_ids as $uid) {
      $uid = (int)$uid;
      foreach ($video_ids as $vid) {
        $vid = (int)$vid;

        $check->execute([":u" => $uid, ":v" => $vid]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if ($row) {
          $new = (int)!((int)$row["is_active"]);
          $upd->execute([":a" => $new, ":id" => $row["id"]]);
        } else {
          $ins->execute([":u" => $uid, ":v" => $vid]);
        }
        $toggled++;
      }
    }

    flash("Applied toggles: $toggled association(s)", "success");
  }

  // redirect safely (no header issues)
  redirect("admin/assign_user_videos.php?video=" . urlencode($video_q) . "&user=" . urlencode($user_q));
}

/* ---------- GET: searches (after POST handling) ---------- */

// search videos (max 25)
if ($video_q !== "") {
  $stmt = $db->prepare("
    SELECT id, title, channel_name, video_id
    FROM IT202_M2_YT_Videos
    WHERE title LIKE :q OR channel_name LIKE :q OR video_id LIKE :q
    ORDER BY created DESC
    LIMIT 25
  ");
  $stmt->execute([":q" => "%$video_q%"]);
  $videos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// search users (max 25)
if ($user_q !== "") {
  $stmt = $db->prepare("
    SELECT id, username
    FROM Users
    WHERE username LIKE :q
    ORDER BY username ASC
    LIMIT 25
  ");
  $stmt->execute([":q" => "%$user_q%"]);
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/* ---------- NOW it is safe to output HTML ---------- */
require(__DIR__ . "/../../../partials/nav.php");
?>
<div class="container-fluid">
  <h3>Assign Users ↔ Videos</h3>

  <form method="GET" class="mb-3">
    <label>Video search</label>
    <input type="search" name="video" value="<?php echo htmlspecialchars($video_q); ?>" placeholder="video title/channel/id">

    <label>User search</label>
    <input type="search" name="user" value="<?php echo htmlspecialchars($user_q); ?>" placeholder="username/email">

    <input type="submit" value="Search" class="btn btn-primary">
  </form>

  <form method="POST">
    <input type="hidden" name="video_q" value="<?php echo htmlspecialchars($video_q); ?>">
    <input type="hidden" name="user_q" value="<?php echo htmlspecialchars($user_q); ?>">

    <div class="row">
      <div class="col-md-6">
        <h5>Videos (max 25)</h5>
        <?php if (count($videos)===0): ?>
          <p>No results available</p>
        <?php else: ?>
          <?php foreach($videos as $v): ?>
            <div>
              <label>
                <input type="checkbox" name="video_ids[]" value="<?php echo (int)$v["id"]; ?>">
                <?php echo htmlspecialchars($v["title"]); ?>
                (<?php echo htmlspecialchars($v["channel_name"]); ?>)
              </label>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="col-md-6">
        <h5>Users (max 25)</h5>
        <?php if ($did_search && count($users)===0): ?>
          <p>No results available</p>
        <?php else: ?>
          <?php foreach($users as $u): ?>
            <div>
              <label>
                <input type="checkbox" name="user_ids[]" value="<?php echo (int)$u["id"]; ?>">
                <?php echo htmlspecialchars($u["username"]); ?>
              </label>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-3">
      <button class="btn btn-success" type="submit" name="apply" value="1">Apply Associations (Toggle)</button>
    </div>
  </form>
</div>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>