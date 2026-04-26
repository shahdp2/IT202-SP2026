<?php
// UCID: dns33
// Date: 04/19/2026
// Summary: Admin edit page for YT Channels using update() helper.

require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$id = (int)se($_GET, "id", -1, false);

// UPDATE first
if ($id > 0 && isset($_POST["title"])) {
    // Only allow these to be edited
    $allowed = ["title", "vanity_url", "verified", "subscribers_text", "avatar_url", "is_api"];

    foreach ($_POST as $k => $v) {
        if (!in_array($k, $allowed, true)) {
            unset($_POST[$k]);
        }
    }

    // normalize checkbox values
    $_POST["verified"] = isset($_POST["verified"]) ? 1 : 0;
    $_POST["is_api"] = isset($_POST["is_api"]) ? 1 : 0;

    // required for update() WHERE clause
    $_POST["id"] = $id;

    try {
        $r = update("IT202_M2_YT_Channels", $_POST);
        if ($r["rowCount"]) {
            flash("Updated " . $r["rowCount"] . " record(s)", "success");
        } else {
            flash("No changes made (or same values submitted)", "warning");
        }
    } catch (Exception $e) {
        error_log("YT channel update error: " . var_export($e, true));
        flash("Error updating channel", "danger");
    }
}

// LOAD record
$channel = [];
if ($id > 0) {
    $db = getDB();
    $query = "SELECT channel_id, title, vanity_url, verified, subscribers_text, avatar_url, is_api
              FROM IT202_M2_YT_Channels WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([":id" => $id]);
    $channel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$channel) {
        flash("Channel not found", "warning");
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

    <form method="POST" onsubmit="return validateChannelEdit(this);">
        <div class="mb-3">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" required maxlength="120" value="<?php se($channel, "title"); ?>">
        </div>

        <div class="mb-3">
            <label for="vanity_url">Vanity URL</label>
            <input type="url" name="vanity_url" id="vanity_url" maxlength="255" value="<?php se($channel, "vanity_url"); ?>">
        </div>

        <div class="mb-3">
            <label for="subscribers_text">Subscribers Text</label>
            <input type="text" name="subscribers_text" id="subscribers_text" maxlength="40" value="<?php se($channel, "subscribers_text"); ?>">
        </div>

        <div class="mb-3">
            <label for="avatar_url">Avatar URL</label>
            <input type="url" name="avatar_url" id="avatar_url" maxlength="255" value="<?php se($channel, "avatar_url"); ?>">
        </div>

        <div class="mb-3">
            <label>
                <input type="checkbox" name="verified" <?php echo (se($channel, "verified", 0, false) ? "checked" : ""); ?>>
                Verified
            </label>
        </div>

        <div class="mb-3">
            <label>
                <input type="checkbox" name="is_api" <?php echo (se($channel, "is_api", 1, false) ? "checked" : ""); ?>>
                API Record
            </label>
        </div>

        <input type="submit" value="Update" class="btn btn-primary">
    </form>
</div>
<script>
// UCID: dns33
// JS Validation: edit_yt_channel
function validateChannelEdit(form) {
  // Clear any old flash if present
  const flash = document.getElementById("flash");
  if (flash) flash.innerHTML = "";

  function showMsg(msg) {
    if (!flash) {
      alert(msg);
      return;
    }
    const outer = document.createElement("div");
    outer.className = "row justify-content-center";
    const inner = document.createElement("div");
    inner.className = "alert alert-warning";
    inner.innerText = msg;
    outer.appendChild(inner);
    flash.appendChild(outer);
  }

  const title = (form.title?.value || "").trim();
  const vanity = (form.vanity_url?.value || "").trim();
  const avatar = (form.avatar_url?.value || "").trim();

  // Required (stronger than HTML required because it rejects spaces)
  if (!title) {
    showMsg("Title cannot be blank.");
    return false;
  }

  // If URL fields are provided, ensure they look valid and use https
  // (HTML type=url helps, but JS makes it stricter + user-friendly)
  const urlRegex = /^https:\/\/.+/i;

  if (vanity && !urlRegex.test(vanity)) {
    showMsg("Vanity URL must start with https://");
    return false;
  }

  if (avatar && !urlRegex.test(avatar)) {
    showMsg("Avatar URL must start with https://");
    return false;
  }

  return true;
}
</script>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>