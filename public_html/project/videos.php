<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: User browse videos + save/unsave (Option 1)

require(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);

$db = getDB();
$user_id = get_user_id();

// limit 1-100 default 10
$limit = (int)se($_GET, "limit", 10, false);
if ($limit < 1 || $limit > 100) $limit = 10;

// search
$search = trim(se($_GET, "search", "", false));

// sort whitelist
$allowedSort = ["created","title","channel_name","published_text","views_text","is_api"];
$sort = se($_GET, "sort", "created", false);
if (!in_array($sort, $allowedSort, true)) $sort = "created";

$dir = strtolower(se($_GET, "dir", "desc", false));
$dir = ($dir === "asc") ? "asc" : "desc";

// build query
$sql = "
SELECT 
  v.id, v.video_id, v.title, v.channel_name, v.published_text, v.views_text, v.is_api, v.created,
  CASE WHEN uv.id IS NULL THEN 0 ELSE 1 END AS is_saved
FROM IT202_M2_YT_Videos v
LEFT JOIN IT202_M3_UserYTVideos uv
  ON uv.yt_video_id = v.id AND uv.user_id = :uid AND uv.is_active = 1
WHERE 1=1
";

$params = [":uid" => $user_id];

if ($search !== "") {
  $sql .= " AND (v.title LIKE :s OR v.channel_name LIKE :s OR v.video_id LIKE :s) ";
  $params[":s"] = "%$search%";
}

$sql .= " ORDER BY `$sort` $dir LIMIT :lim";
$stmt = $db->prepare($sql);

foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(":lim", $limit, PDO::PARAM_INT);

$rows = [];
try {
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
  error_log("videos.php error: " . var_export($e, true));
  flash("Error loading videos", "danger");
}

// helper: current page return url
$return = $_SERVER["REQUEST_URI"];
?>
<div class="container-fluid">
  <h3>Browse Videos</h3>

  <form method="GET" class="mb-3">
    <label>Search</label>
    <input type="search" name="search" value="<?php se($_GET, 'search'); ?>" placeholder="title, channel, or video id">
    <label>Sort</label>
    <select name="sort">
      <?php foreach ($allowedSort as $c): ?>
        <option value="<?php echo $c; ?>" <?php echo ($c === $sort) ? "selected" : ""; ?>><?php echo $c; ?></option>
      <?php endforeach; ?>
    </select>
    <select name="dir">
      <option value="desc" <?php echo ($dir==="desc")?"selected":""; ?>>desc</option>
      <option value="asc" <?php echo ($dir==="asc")?"selected":""; ?>>asc</option>
    </select>

    <label>Limit (1-100)</label>
    <input type="number" name="limit" min="1" max="100" value="<?php echo htmlspecialchars((string)$limit); ?>">
    <input type="submit" value="Apply" class="btn btn-primary">
    <a class="btn btn-secondary" href="<?php echo strtok($return, '?'); ?>">Reset</a>
  </form>

  <?php if (count($rows) === 0): ?>
    <p>No results available</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>title</th>
            <th>channel_name</th>
            <th>published</th>
            <th>views</th>
            <th>saved?</th>
            <th>actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php se($r, "title"); ?></td>
              <td><?php se($r, "channel_name"); ?></td>
              <td><?php se($r, "published_text", "N/A"); ?></td>
              <td><?php se($r, "views_text", "N/A"); ?></td>
              <td><?php echo (se($r,"is_saved",0,false) ? "Yes" : "No"); ?></td>
              <td>
                <a href="<?php echo get_url("admin/view_yt_video.php", true); ?>?id=<?php se($r,"id"); ?>">View</a>
                |
                <a href="<?php echo get_url("toggle_save_video.php", true); ?>?id=<?php se($r,"id"); ?>&return=<?php echo urlencode($return); ?>">
                  <?php echo (se($r,"is_saved",0,false) ? "Unsave" : "Save"); ?>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require(__DIR__ . "/../../partials/flash.php"); ?>