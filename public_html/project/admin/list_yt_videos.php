<?php
// UCID: dns33
// Date: 04/25/2026
// Summary: List YouTube Videos with filter/sort/limit and dynamic table output.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once(__DIR__ . "/../../../lib/functions.php"); // no HTML output yet

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

store_current_route();

// now safe to output HTML
require(__DIR__ . "/../../../partials/nav.php");

$db = getDB();

// ---- Limit: valid range 1-100, default 10 ----
$limit = (int)se($_GET, "limit", 10, false);
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

// ---- Filter/Search ----
$search = trim(se($_GET, "search", "", false));

// ---- Sort (whitelist) ----
$allowedSort = ["created", "title", "channel_name", "video_id", "channel_id", "views_text", "published_text", "is_api"];
$sort = se($_GET, "sort", "created", false);
if (!in_array($sort, $allowedSort, true)) {
    $sort = "created";
}
$dir = strtolower(se($_GET, "dir", "desc", false));
$dir = ($dir === "asc") ? "asc" : "desc";

// ---- Query ----
$query = "SELECT
            id, video_id, channel_id, title, channel_name, length_text, published_text, views_text, is_api, created
          FROM IT202_M2_YT_Videos";
$params = [];

if ($search !== "") {
    $query .= " WHERE title LIKE :s
                OR channel_name LIKE :s
                OR video_id LIKE :s
                OR channel_id LIKE :s";
    $params[":s"] = "%$search%";
}

$query .= " ORDER BY `$sort` $dir LIMIT :lim";
$stmt = $db->prepare($query);

// bind params
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(":lim", $limit, PDO::PARAM_INT);

$results = [];
try {
    $stmt->execute();
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($r) {
        $results = $r;
    }
} catch (PDOException $e) {
    error_log("Error fetching videos " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>

<div class="container-fluid">
  <h3>List YouTube Videos</h3>

  <div class="card">
    <div class="card-body">
      <form method="GET" class="filter-form">
        <div class="mb-3">
          <label>Search</label>
          <input type="search" name="search" value="<?php se($_GET, "search"); ?>" placeholder="title, channel, video id, channel id" />
        </div>

        <div class="mb-3">
          <label>Sort</label>
          <select name="sort">
            <?php foreach ($allowedSort as $c): ?>
              <option value="<?php echo $c; ?>" <?php echo ($c === $sort) ? "selected" : ""; ?>>
                <?php echo $c; ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="dir">
            <option value="desc" <?php echo ($dir === "desc") ? "selected" : ""; ?>>desc</option>
            <option value="asc" <?php echo ($dir === "asc") ? "selected" : ""; ?>>asc</option>
          </select>
        </div>

        <div class="mb-3">
          <label>Limit (1-100)</label>
          <input type="number" name="limit" min="1" max="100" value="<?php echo htmlspecialchars((string)$limit); ?>" />
        </div>

        <input type="submit" value="Apply" class="btn btn-primary" />
      </form>
    </div>
  </div>

  <?php if (count($results) == 0) : ?>
    <p>No results to show</p>
  <?php else : ?>
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <?php foreach ($results as $index => $record) : ?>
              <?php if ($index == 0) : ?>
                <thead>
                  <tr>
                    <?php foreach ($record as $column => $value) : ?>
                      <th><?php se($column); ?></th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
              <?php endif; ?>

              <tr>
                <?php foreach ($record as $column => $value) : ?>
                  <td><?php se($value, null, "N/A"); ?></td>
                <?php endforeach; ?>

                <td class="actions">
                  <a href="<?php echo get_url("admin/view_yt_video.php", true); ?>?id=<?php se($record, "id"); ?>">View</a>
                  <a href="<?php echo get_url("admin/edit_yt_video.php", true); ?>?id=<?php se($record, "id"); ?>">Edit</a>
                  <a href="<?php echo get_url("admin/delete_yt_video.php", true); ?>?id=<?php se($record, "id"); ?>&return=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>">Delete</a>
                </td>
              </tr>

            <?php endforeach; ?>
                </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>