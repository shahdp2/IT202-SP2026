<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: Admin edit page for YT Channels (loads by id, updates via whitelist, then re-selects).

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$db = getDB();
$id = (int)se($_GET, "id", -1, false);

// ----- UPDATE first (POST) -----
if ($id > -1 && isset($_POST["title"])) {
    // Whitelist editable fields (id/created/modified should NOT be editable)
    $allowed = ["title", "vanity_url", "verified", "subscribers_text", "avatar_url", "is_api"];

    foreach ($_POST as $k => $v) {
        if (!in_array($k, $allowed)) {
            unset($_POST[$k]);
        }
    }

    // normalize checkbox-like values (verified, is_api)
    $_POST["verified"] = isset($_POST["verified"]) ? 1 : 0;
    $_POST["is_api"] = isset($_POST["is_api"]) ? 1 : 0;

    $query = "UPDATE IT202_M2_YT_Channels SET ";
    $params = [];

    foreach ($_POST as $k => $v) {
        if (!empty($params)) {
            $query .= ", ";
        }
        $query .= "`$k` = :$k";
        $params[":$k"] = $v;
    }

    $query .= " WHERE id = :id";
    $params[":id"] = $id;

    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        flash("Updated channel successfully", "success");
    } catch (PDOException $e) {
        error_log("Error updating channel: " . var_export($e, true));
        flash("Error updating channel", "danger");
    }
}

// ----- LOAD record (SELECT) -----
$channel = [];
if ($id > -1) {
    $query = "SELECT channel_id, title, vanity_url, verified, subscribers_text, avatar_url, is_api
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
        flash("Error fetching channel", "danger");
        die(header("Location: " . get_url("admin/list_yt_channels.php")));
    }
} else {
    flash("Invalid id passed", "danger");
    die(header("Location: " . get_url("admin/list_yt_channels.php")));
}
?>

<div class="container-fluid">
    <h3>Edit YouTube Channel</h3>

    <p><b>Channel ID (read-only):</b> <?php se($channel, "channel_id"); ?></p>

    <form method="POST">
        <div class="mb-3">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" required maxlength="120"
                   value="<?php se($channel, "title"); ?>">
        </div>

        <div class="mb-3">
            <label for="vanity_url">Vanity URL</label>
            <input type="url" name="vanity_url" id="vanity_url" maxlength="255"
                   value="<?php se($channel, "vanity_url"); ?>">
        </div>

        <div class="mb-3">
            <label for="subscribers_text">Subscribers Text</label>
            <input type="text" name="subscribers_text" id="subscribers_text" maxlength="40"
                   value="<?php se($channel, "subscribers_text"); ?>">
        </div>

        <div class="mb-3">
            <label for="avatar_url">Avatar URL</label>
            <input type="url" name="avatar_url" id="avatar_url" maxlength="255"
                   value="<?php se($channel, "avatar_url"); ?>">
        </div>

        <div class="mb-3">
            <label>
                <input type="checkbox" name="verified" <?php echo (se($channel, "verified", 0, false) ? "checked" : ""); ?>>
                Verified
            </label>
        </div>

        <div class="mb-3">
            <label>
                <input type="checkbox" name="is_api" <?php echo (se($channel, "is_api", 0, false) ? "checked" : ""); ?>>
                API Record
            </label>
            <small>(Uncheck if you want to mark it as manual)</small>
        </div>

        <input type="submit" value="Update" class="btn btn-primary">
    </form>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>