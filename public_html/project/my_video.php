<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Logged-in user's watchlist page (User <-> YT Videos association) with filter/sort/limit + remove links.

require(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);

$db = getDB();
$user_id = get_user_id();

// ---- Limit: 1-100, default 10 ----
$limit = (int)se($_GET, "limit", 10, false);
if ($limit < 1 || $limit > 100) $limit = 10;

// ---- Filter/Search ----
$search = trim(se($_GET, "search", "", false));

// ---- Sort (whitelist) ----
$allowedSort = ["created", "title", "channel_name", "published_text", "views_text"];
$sort = se($_GET, "sort", "created", false);
if (!in_array($sort, $allowedSort, true)) $sort = "created";

$dir = strtolower(se($_GET, "dir", "desc", false));
$dir = ($dir === "asc") ? "asc" : "desc";

// Stats
$total_count = 0;
$shown_count = 0;

// Count all associations (for heading stats)
try {
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM IT202_M3_UserYTVideos WHERE user_id = :uid AND is_active = 1");
    $stmt->execute([":uid" => $user_id]);
    $total_count = (int)se($stmt->fetch(PDO::FETCH_ASSOC), "c", 0, false);
} catch (PDOException $e) {
    error_log("my_videos count error: " . var_export($e, true));
}

// Query items (apply filters/limit)
$sql = "
SELECT 
  uv.id AS rel_id,
  v.id AS video_db_id,
  v.video_id, v.title, v.channel_name, v.published_text, v.views_text, v.thumbnail_url,
  uv.created
FROM IT202_M3_UserYTVideos uv
JOIN IT202_M2_YT_Videos v ON v.id = uv.yt_video_id
WHERE uv.user_id = :uid AND uv.is_active = 1
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
    $shown_count = count($rows);
} catch (PDOException $e) {
    error_log("my_videos fetch error: " . var_export($e, true));
    flash("Error loading your watchlist", "danger");
}

$return = $_SERVER["REQUEST_URI"];
?>
<div class="container-fluid">
    <h3>My Watchlist</h3>

    <p>
        <b>Total saved:</b> <?php echo htmlspecialchars((string)$total_count); ?>
        |
        <b>Shown:</b> <?php echo htmlspecialchars((string)$shown_count); ?>
    </p>

    <div class="mb-3">
        <a class="btn btn-danger"
           href="<?php echo get_url("remove_all_saved_videos.php", true); ?>?return=<?php echo urlencode($return); ?>"
           onclick="return confirm('Remove ALL saved videos from your watchlist?');">
            Remove All
        </a>
    </div>

    <form method="GET" class="mb-3">
        <label>Search</label>
        <input type="search" name="search" value="<?php se($_GET, 'search'); ?>" placeholder="title, channel, or video id">

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
                            <a href="<?php echo get_url("admin/view_yt_video.php", true); ?>?id=<?php se($r,"video_db_id"); ?>">View</a>
                            |
                            <a href="<?php echo get_url("remove_saved_video.php", true); ?>?id=<?php se($r,"video_db_id"); ?>&return=<?php echo urlencode($return); ?>">
                                Remove
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