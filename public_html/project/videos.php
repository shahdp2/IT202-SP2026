<?php
// UCID: dns33
// Date: 05/02/2026
// Summary: User-facing Browse Videos page (read-only list + Save/Unsave watchlist toggle)

require(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);
store_current_route();

$db = getDB();
$user_id = (int)get_user_id();

// ---- Limit: 1-100, default 10 ----
$limit = (int)se($_GET, "limit", 10, false);
if ($limit < 1 || $limit > 100) $limit = 10;

// ---- Filter/Search ----
$search = trim(se($_GET, "search", "", false));

// ---- Sort (whitelist) ----
// Use a map so ORDER BY is safe + correct table alias
$sortMap = [
  "created" => "v.created",
  "title" => "v.title",
  "channel_name" => "v.channel_name",
  "published_text" => "v.published_text",
  "views_text" => "v.views_text",
];
$allowedSort = array_keys($sortMap);

$sort = se($_GET, "sort", "created", false);
if (!isset($sortMap[$sort])) $sort = "created";
$orderBy = $sortMap[$sort];

$dir = strtolower(se($_GET, "dir", "desc", false));
$dir = ($dir === "asc") ? "asc" : "desc";

// ---- Count total (for Results header) ----
$countSql = "SELECT COUNT(*) AS c FROM IT202_M2_YT_Videos v WHERE 1=1";
$countParams = [];

if ($search !== "") {
  $countSql .= " AND (v.title LIKE :s OR v.channel_name LIKE :s OR v.video_id LIKE :s OR v.channel_id LIKE :s)";
  $countParams[":s"] = "%$search%";
}

$total_count = 0;
try {
  $r = selectAll($countSql, $countParams);
  $total_count = (int)se($r[0] ?? [], "c", 0, false);
} catch (Exception $e) {
  error_log("videos.php count error: " . var_export($e, true));
}

// ---- Main query (rows) ----
// LEFT JOIN to see if current user has saved it
$sql = "
SELECT
  v.id,
  v.video_id,
  v.title,
  v.channel_name,
  v.published_text,
  v.views_text,
  v.created,
  CASE WHEN uv.id IS NULL THEN 0 ELSE 1 END AS is_saved
FROM IT202_M2_YT_Videos v
LEFT JOIN IT202_M3_UserYTVideos uv
  ON uv.yt_video_id = v.id AND uv.user_id = :uid
WHERE 1=1
";
$params = [":uid" => $user_id];

if ($search !== "") {
  $sql .= " AND (v.title LIKE :s OR v.channel_name LIKE :s OR v.video_id LIKE :s OR v.channel_id LIKE :s) ";
  $params[":s"] = "%$search%";
}

$sql .= " ORDER BY $orderBy $dir LIMIT :lim";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(":lim", $limit, PDO::PARAM_INT);

$rows = [];
try {
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
  error_log("videos.php fetch error: " . var_export($e, true));
  flash("Error loading videos", "danger");
}

$shown_count = count($rows);
$return = $_SERVER["REQUEST_URI"];

// results header data
$result_stats = [
  "current" => $shown_count,
  "total" => $total_count
];
?>
<div class="container-fluid">
  <h3>Browse Videos</h3>

  <?php
  // Optional: if you have partials/results_header.php
  // It expects $result_stats
  require(__DIR__ . "/../../partials/results_header.php");
  ?>

  <form method="GET" class="mb-3">
    <label>Search</label>
    <input type="search" name="search" value="<?php se($_GET, 'search'); ?>" placeholder="title, channel, video id, channel id">

    <label>Sort</label>
    <select name="sort">
      <?php foreach ($allowedSort as $c): ?>
        <option value="<?php echo $c; ?>" <?php echo ($c === $sort) ? "selected" : ""; ?>>
          <?php echo $c; ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="dir">
      <option value="desc" <?php echo ($dir==="desc")?"selected":""; ?>>desc</option>
      <option value="asc" <?php echo ($dir==="asc")?"selected":""; ?>>asc</option>
    </select>

    <label>Limit (1-100)</label>
    <input type="number" name="limit" min="1" max="100" value="<?php echo htmlspecialchars((string)$limit); ?>">

    <input type="submit" value="Apply" class="btn btn-primary">
    <a class="btn btn-secondary" href="<?php echo get_url("videos.php", true); ?>">Reset</a>
  </form>

  <?php if ($shown_count === 0): ?>
    <p>No results available</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
        <tr>
          <th>title</th>
          <th>channel</th>
          <th>published</th>
          <th>views</th>
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
            <td>
              <a href="<?php echo get_url("view_video.php", true); ?>?id=<?php se($r,"id"); ?>">View</a>
              |
              <a href="<?php echo get_url("toggle_save_video.php", true); ?>?id=<?php se($r,"id"); ?>&return=<?php echo urlencode($return); ?>">
                <?php echo ((int)se($r, "is_saved", 0, false) === 1) ? "Unsave" : "Save"; ?>
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