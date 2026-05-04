<?php
// UCID: dns33
// Date: 05/03/2026
// Summary: Admin list of videos NOT associated with any user (unassociated) with filter/sort/limit + stats.

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
  flash("You don't have permission to view this page", "warning");
  die(header("Location: " . get_url("landing.php")));
}

$db = getDB();

// ---- Limit: 1-100 default 10 ----
$limit = (int)se($_GET, "limit", 10, false);
if ($limit < 1 || $limit > 100) $limit = 10;

// ---- Filter/Search (title/channel/video_id/channel_id) ----
$search = trim(se($_GET, "search", "", false));

// ---- Sort + dir ----
$sortMap = [
  "title"         => "v.title",
  "channel_name"  => "v.channel_name",
  "created"       => "v.created",
  "published_text"=> "v.published_text",
  "views_text"    => "v.views_text",
];
$allowedSort = array_keys($sortMap);

$sort = se($_GET, "sort", "created", false);
if (!isset($sortMap[$sort])) $sort = "created";
$orderBy = $sortMap[$sort];

$dir = strtolower(se($_GET, "dir", "desc", false));
$dir = ($dir === "asc") ? "asc" : "desc";

// ---- Stats ----
$total = 0;
$shown = 0;

// COUNT query (same filters, NO limit)
$countSql = "
SELECT COUNT(*) AS c
FROM IT202_M2_YT_Videos v
LEFT JOIN IT202_M3_UserYTVideos uv
  ON uv.yt_video_id = v.id AND uv.is_active = 1
WHERE uv.id IS NULL
";
$countParams = [];

if ($search !== "") {
  $countSql .= " AND (v.title LIKE :s OR v.channel_name LIKE :s OR v.video_id LIKE :s OR v.channel_id LIKE :s) ";
  $countParams[":s"] = "%$search%";
}

try {
  $r = selectAll($countSql, $countParams);
  $total = (int)se($r[0] ?? [], "c", 0, false);
} catch (Exception $e) {
  error_log("unassoc count error: " . var_export($e, true));
}

// MAIN query (rows)
$sql = "
SELECT
  v.id AS video_db_id,
  v.video_id,
  v.title,
  v.channel_name,
  v.published_text,
  v.views_text,
  v.created
FROM IT202_M2_YT_Videos v
LEFT JOIN IT202_M3_UserYTVideos uv
  ON uv.yt_video_id = v.id AND uv.is_active = 1
WHERE uv.id IS NULL
";
$params = [];

if ($search !== "") {
  $sql .= " AND (v.title LIKE :s OR v.channel_name LIKE :s OR v.video_id LIKE :s OR v.channel_id LIKE :s) ";
  $params[":s"] = "%$search%";
}

$sql .= " ORDER BY $orderBy $dir LIMIT :lim";
$params[":lim"] = (int)$limit;

$rows = [];
try {
  $rows = selectAll($sql, $params);
  $shown = count($rows);
} catch (Exception $e) {
  error_log("unassoc list error: " . var_export($e, true));
  flash("Error loading unassociated videos", "danger");
}

$return = $_SERVER["REQUEST_URI"];
?>

<div class="container-fluid">
  <h3>Unassociated Videos</h3>

  <p>
    <b>Total unassociated:</b> <?php echo htmlspecialchars((string)$total); ?>
    |
    <b>Shown:</b> <?php echo htmlspecialchars((string)$shown); ?>
  </p>

  <form method="GET" class="mb-3">
    <label>Search</label>
    <input type="search" name="search" value="<?php se($_GET, "search"); ?>" placeholder="title, channel, video id, channel id">

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
    <a class="btn btn-secondary" href="<?php echo strtok($return,'?'); ?>">Reset</a>
  </form>

  <?php if ($shown === 0): ?>
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
            <th>created</th>
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
              <td><?php se($r, "created"); ?></td>
              <td>
                <a href="<?php echo get_url("admin/view_yt_video.php", true); ?>?id=<?php se($r, "video_db_id"); ?>">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>