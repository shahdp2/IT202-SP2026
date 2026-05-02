<?php
// UCID: dns33
// Date: 04/25/2026
// Summary: Admin list page for YouTube Videos (shows API + manual records)

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}
store_current_route();
$query = "SELECT 
            id, video_id, channel_id, title, channel_name, length_text, published_text, views_text, is_api, created
          FROM IT202_M2_YT_Videos
          ORDER BY created DESC
          LIMIT 25";

$db = getDB();
$stmt = $db->prepare($query);
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
                  <a href="<?php echo get_url("admin/view_yt_video.php"); ?>?id=<?php se($record, "id"); ?>">View</a>
                  <a href="<?php echo get_url("admin/edit_yt_video.php"); ?>?id=<?php se($record, "id"); ?>">Edit</a>
                  <a href="<?php echo get_url("admin/delete_yt_video.php", true); ?>?id=<?php se($record, "id"); ?>
                  &return=<?php echo urlencode($_SERVER["REQUEST_URI"]); ?>">Delete</a>
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