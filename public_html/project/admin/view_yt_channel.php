<?php
// UCID: dns33
// Date: 04/24/2026
// Summary: Admin view page for a single YouTube Channel (details page)

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// Get id from query params
$id = (int)se($_GET, "id", -1, false);
if ($id < 1) {
    flash("Invalid channel id", "danger");
    die(header("Location: " . get_url("admin/list_yt_channels.php")));
}

// Load record
$db = getDB();
$channel = [];

$query = "SELECT id, channel_id, title, vanity_url, verified, subscribers_text, avatar_url, is_api, created, modified
          FROM IT202_M2_YT_Channels
          WHERE id = :id";

try {
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($r) {
        $channel = $r;
    } else {
        flash("Channel not found", "warning");
        die(header("Location: " . get_url("admin/list_yt_channels.php")));
    }
} catch (PDOException $e) {
    error_log("Error fetching channel: " . var_export($e, true));
    flash("Error loading channel", "danger");
    die(header("Location: " . get_url("admin/list_yt_channels.php")));
}
?>

<div class="container-fluid">
    <h3 class="mb-3">YouTube Channel Details</h3>

    <div class="card p-3">
        <table class="table">
            <tr><th>ID</th><td><?php se($channel, "id"); ?></td></tr>
            <tr><th>Channel ID</th><td><?php se($channel, "channel_id"); ?></td></tr>
            <tr><th>Title</th><td><?php se($channel, "title"); ?></td></tr>
            <tr><th>Vanity URL</th>
                <td>
                    <?php if (!empty($channel["vanity_url"])): ?>
                        <a href="<?php se($channel, "vanity_url"); ?>" target="_blank">Open</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <tr><th>Verified</th><td><?php echo !empty($channel["verified"]) ? "Yes" : "No"; ?></td></tr>
            <tr><th>Subscribers</th><td><?php se($channel, "subscribers_text", "N/A"); ?></td></tr>
            <tr><th>Avatar</th>
                <td>
                    <?php if (!empty($channel["avatar_url"])): ?>
                        <img src="<?php se($channel, "avatar_url"); ?>" alt="avatar" style="max-height:80px;">
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <tr><th>is_api</th><td><?php echo !empty($channel["is_api"]) ? "1" : "0"; ?></td></tr>
            <tr><th>Created</th><td><?php se($channel, "created"); ?></td></tr>
            <tr><th>Modified</th><td><?php se($channel, "modified"); ?></td></tr>
        </table>

        <div class="mt-2">
            <a class="btn btn-primary" href="<?php echo get_url("admin/edit_yt_channel.php"); ?>?id=<?php se($channel, "id"); ?>">Edit</a>
            <a class="btn btn-secondary" href="<?php echo get_url("admin/list_yt_channels.php"); ?>">Back to List</a>
            <a class="btn btn-danger" href="<?php echo get_url("admin/delete_yt_channel.php"); ?>
                ?id=<?php se($channel, "id"); ?>" onclick="return confirm('Delete this channel?');">
                Delete</a>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>