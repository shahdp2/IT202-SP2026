<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Admin list of videos associated with users (username filter + count + limit)

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
  flash("You don't have permission to view this page", "warning");
  die(header("Location: " . get_url("landing.php")));
}

$db = getDB();

// limit 1-100 default 10
$limit = (int)se($_GET, "limit", 10, false);
if ($limit < 1 || $limit > 100) $limit = 10;

// username partial filter
$username = trim(se($_GET, "username", "", false));

// sort + dir
$allowedSort = ["video_title", "user_count", "last_added"];
$sort = se($_GET, "sort", "last_added", false);
if (!in_array($sort, $allowedSort, true)) $sort = "last_added";

$dir = strtolower(se($_GET, "dir", "desc", false));
$dir = ($dir === "asc") ? "asc" : "desc";

// stats
$total = 0;
$shown = 0;

// total distinct videos associated
try {
  $countSql = "
    SELECT COUNT(DISTINCT uv.yt_video_id) AS c
    FROM IT202_M3_UserYTVideos uv
    WHERE uv.is_active = 1
  ";
  $stmt = $db->prepare($countSql);
  $stmt->execute();
  $total = (int)se($stmt->fetch(PDO::FETCH_ASSOC), "c", 0, false);
} catch (PDOException $e) {
  error_log("assoc count error: " . var_export($e, true));
}

$sql = "
SELECT
  v.id AS video_db_id,
  v.title AS video_title,
  v.channel_name,
  v.published_text,
  v.views_text,
  COUNT(DISTINCT uv.user_id) AS user_count,
  MAX(uv.created) AS last_added,
  GROUP_CONCAT(DISTINCT u.username ORDER BY u.username SEPARATOR ', ') AS usernames
FROM IT202_M3_UserYTVideos uv
JOIN IT202_M2_YT_Videos v ON v.id = uv.yt_video_id
JOIN Users u ON u.id = uv.user_id
WHERE uv.is_active = 1
";

$params = [];

if ($username !== "") {
  $sql .= " AND u.username LIKE :uname ";
  $params[":uname"] = "%$username%";
}

$sql .= " GROUP BY v.id, v.title, v.channel_name, v.published_text, v.views_text ";

$orderCol = match ($sort) {
  "video_title" => "video_title",
  "user_count"  => "user_count",
  default       => "last_added",
};

$sql .= " ORDER BY $orderCol $dir LIMIT :lim";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(":lim", $limit, PDO::PARAM_INT);

$rows = [];
try {
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $shown = count($rows);
} catch (PDOException $e) {
  error_log("assoc list error: " . var_export($e, true));
  flash("Error loading associations", "danger");
}

$return = $_SERVER["REQUEST_URI"];
?>
<div class="container-fluid">
  <h3>Videos Associated With Users</h3>

  <p><b>Total associated videos:</b> <?php echo htmlspecialchars((string)$total); ?> | <b>Shown:</b> <?php echo htmlspecialchars((string)$shown); ?></p>

  <form method="GET" class="mb-3">
    <label>Username (partial)</label>
    <input type="search" name="username" value="<?php se($_GET,'username'); ?>" placeholder="e.g. deep">

    <label>Sort</label>
    <select name="sort">
      <?php foreach ($allowedSort as $c): ?>
        <option value="<?php echo $c; ?>" <?php echo ($c===$sort)?"selected":""; ?>><?php echo $c; ?></option>
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

    <?php if ($username !== ""): ?>
      <a class="btn btn-danger"
         href="<?php echo get_url("admin/remove_all_associations_by_user_filter.php", true); ?>?username=<?php echo urlencode($username); ?>&return=<?php echo urlencode($return); ?>"
         onclick="return confirm('Remove ALL associations for matching users?');">
        Remove all for matching users
      </a>
    <?php endif; ?>
  </form>

  <?php if (count($rows) === 0): ?>
    <p>No results available</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>video</th>
            <th>channel</th>
            <th>users</th>
            <th>user_count</th>
            <th>last_added</th>
            <th>actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?php se($r,"video_title"); ?></td>
              <td><?php se($r,"channel_name"); ?></td>
              <td>
                <?php
                  $names = se($r,"usernames","",false);
                  $parts = array_filter(array_map("trim", explode(",", (string)$names)));
                  foreach ($parts as $name) {
                    echo '<a href="'.get_url("profile.php", true).'?username='.urlencode($name).'">'.htmlspecialchars($name).'</a> ';
                  }
                ?>
              </td>
              <td><?php se($r,"user_count"); ?></td>
              <td><?php se($r,"last_added"); ?></td>
              <td>
                <a href="<?php echo get_url("admin/view_yt_video.php", true); ?>?id=<?php se($r,"video_db_id"); ?>">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require(__DIR__ . "/../../../partials/flash.php"); ?>