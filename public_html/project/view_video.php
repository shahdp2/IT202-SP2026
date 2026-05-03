<?php
require(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);

$id = (int)se($_GET, "id", -1, false);
if ($id < 1) {
  flash("Invalid video id", "warning");
  redirect(get_last_route());
}

$db = getDB();
$stmt = $db->prepare("SELECT id, video_id, title, channel_name, published_text, views_text, thumbnail_url, created, is_api
                      FROM IT202_M2_YT_Videos
                      WHERE id = :id");
$stmt->execute([":id" => $id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
  flash("Video not found", "warning");
  redirect(get_last_route());
}
?>
<div class="container-fluid">
  <h3><?php se($video, "title"); ?></h3>

  <p><b>Channel:</b> <?php se($video, "channel_name"); ?></p>
  <p><b>Published:</b> <?php se($video, "published_text", "N/A"); ?></p>
  <p><b>Views:</b> <?php se($video, "views_text", "N/A"); ?></p>

  <?php if (!empty(se($video, "thumbnail_url", "", false))): ?>
    <img src="<?php se($video, "thumbnail_url"); ?>" style="max-width:320px;" alt="thumbnail">
  <?php endif; ?>

  <div class="mt-3">
    <a class="btn btn-secondary" href="<?php get_last_route(true, 'videos.php'); ?>">Back</a>
    <a class="btn btn-primary" target="_blank"
       href="https://www.youtube.com/watch?v=<?php se($video, "video_id"); ?>">
      Open on YouTube
    </a>
  </div>
</div>

<?php require(__DIR__ . "/../../partials/flash.php"); ?>