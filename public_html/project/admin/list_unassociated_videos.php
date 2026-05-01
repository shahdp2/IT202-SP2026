<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Admin list videos with NO user associations (filter/sort/limit)

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
  flash("You don't have permission to view this page", "warning");
  die(header("Location: " . get_url("landing.php")));
}

$db = getDB();

$limit = (int)se($_GET,"limit",10,false);
if ($limit < 1 || $limit > 100) $limit = 10;

$search = trim(se($_GET,"search","",false));

$allowedSort = ["created","title","channel_name"];
$sort = se($_GET,"sort","created",false);
if (!in_array($sort,$allowedSort,true)) $sort = "created";

$dir = strtolower(se($_GET,"dir","desc",false));
$dir = ($dir==="asc") ? "asc" : "desc";

$sql = "
SELECT v.id, v.video_id, v.title, v.channel_name, v.published_text, v.views_text, v.is_api, v.created
FROM IT202_M2_YT_Videos v
LEFT JOIN IT202_M3_UserYTVideos uv
  ON uv.yt_video_id = v.id AND uv.is_active = 1
WHERE uv.id IS NULL
";
$params = [];

if ($search !== "") {
  $sql .= " AND (v.title LIKE :s OR v.channel_name LIKE :s OR v.video_id LIKE :s) ";
  $params[":s"] = "%$search%";
}

$sql .= " ORDER BY `$sort` $dir LIMIT :lim";

$stmt = $db->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(":lim",$limit,PDO::PARAM_INT);

$rows = [];
try {
  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch(PDOException $e) {
  error_log("unassociated list error: ".var_export($e,true));
  flash("Error loading unassociated videos", "danger");
}
?>
<div class="container-fluid">
  <h3>Unassociated Videos (no users)</h3>

  <form method="GET" class="mb-3">
    <label>Search</label>
    <input type="search" name="search" value="<?php se($_GET,'search'); ?>" placeholder="title/channel/video id">

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
  </form>

  <?php if (count($rows)===0): ?>
    <p>No results available</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>video_id</th><th>title</th><th>channel</th><th>created</th><th>actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?php se($r,"video_id"); ?></td>
              <td><?php se($r,"title"); ?></td>
              <td><?php se($r,"channel_name"); ?></td>
              <td><?php se($r,"created"); ?></td>
              <td><a href="<?php echo get_url("admin/view_yt_video.php", true); ?>?id=<?php se($r,"id"); ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php require(__DIR__ . "/../../../partials/flash.php"); ?>