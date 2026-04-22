<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: List YouTube Channels with filter/sort/limit and dynamic table output.

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$db = getDB();

// ---- Limit: valid range 1-100, default 10 ----
$limit = (int)se($_GET, "limit", 10, false);
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

// ---- Filter/Search ----
$search = trim(se($_GET, "search", "", false));

// ---- Sort (whitelist) ----
$allowedSort = ["created", "modified", "title", "channel_id", "verified", "is_api"];
$sort = se($_GET, "sort", "modified", false);
if (!in_array($sort, $allowedSort)) {
    $sort = "modified";
}
$dir = strtolower(se($_GET, "dir", "desc", false));
$dir = ($dir === "asc") ? "asc" : "desc";

$query = "SELECT id, channel_id, title, subscribers_text, verified, is_api, created, modified
          FROM IT202_M2_YT_Channels";
$params = [];

if ($search !== "") {
    $query .= " WHERE title LIKE :s OR channel_id LIKE :s";
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
    error_log("Error fetching channels " . var_export($e, true));
    flash("Unhandled error occurred", "danger");
}
?>
<div class="container-fluid">
    <h3>List YouTube Channels</h3>

    <form method="GET">
        <div class="mb-3">
            <label>Search</label>
            <input type="search" name="search" value="<?php se($_GET, "search"); ?>" placeholder="title or channel id" />
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
            <input type="number" name="limit" min="1" max="100" value="<?php echo htmlspecialchars($limit); ?>" />
        </div>

        <input type="submit" value="Apply" />
    </form>

    <?php if (count($results) == 0) : ?>
        <p>No results to show</p>
    <?php else : ?>
        <table class="table">
            <?php foreach ($results as $index => $record) : ?>
                <?php if ($index == 0) : ?>
                    <thead>
                        <?php foreach ($record as $column => $value) : ?>
                            <th><?php se($column); ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </thead>
                <?php endif; ?>
                <tr>
                    <?php foreach ($record as $column => $value) : ?>
                        <td><?php se($value, null, "N/A"); ?></td>
                    <?php endforeach; ?>
                    <td>
                        <a href="<?php echo get_url("admin/view_yt_channel.php", true); ?>?id=<?php se($record, "id"); ?>">View</a>
                        |
                        <a href="<?php echo get_url("admin/edit_yt_channel.php", true); ?>?id=<?php se($record, "id"); ?>">Edit</a>
                        |
                        <a href="<?php echo get_url("admin/delete_yt_channel.php", true); ?>?id=<?php se($record, "id"); ?>">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>