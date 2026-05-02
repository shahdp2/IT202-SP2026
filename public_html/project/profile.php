<?php
ob_start();
require_once(__DIR__ . "/../../partials/nav.php");

// (Optional) make it fully public by NOT forcing login
// If you want “login-public”, keep is_logged_in(true) instead.
 // is_logged_in(true);

// Pick which profile to view
$view_id = (int)se($_GET, "id", get_user_id(), false);
if ($view_id <= 0) {
    flash("Invalid user id", "warning");
    die(header("Location: " . get_url("landing.php")));
}

$is_me = (is_logged_in() && $view_id === get_user_id());
$is_edit = $is_me && isset($_GET["edit"]);
$user_id = get_user_id();
$email = get_user_email();
$username = get_username();
// handle email/username update
if ($is_edit && isset($_POST["email"], $_POST["username"])) {
    $new_email = se($_POST, "email", null, false);
    $new_username = se($_POST, "username", null, false);
    $hasError = false;
    // validate format
    if (empty($new_email)) {
        //echo "Email must not be empty<br>";
        flash("Email must not be empty.", "danger");
        $hasError = true;
    }
    // Sanitize and validate email
    $new_email = sanitize_email($new_email);
    if (!is_valid_email($new_email)) {
        //echo "Invalid email address<br>";
        flash("Invalid email address.", "danger");
        $hasError = true;
    }
    if (!is_valid_username($new_username)) {
        flash("Username must be lowercase, alphanumerical, and can only contain _ or -", "danger");
        $hasError = true;
    }
    // check for changes
    if (($username != $new_username || $email != $new_email) && !$hasError) {
        $saved = false;
        $params = [":email" => $new_email, ":username" => $new_username, ":id" => get_user_id()];
        $db = getDB();
        $stmt = $db->prepare("UPDATE Users set email = :email, username = :username where id = :id");
        try {
            $stmt->execute($params);
            $updated_rows = $stmt->rowCount();  
            if ($updated_rows === 0) {
                flash("No changes made", "warning");
            } else if ($updated_rows == 1) {
                flash("Profile saved", "success");
                $saved = true;
            } else {
                // this shouldn't happen, but we log it just in case
                error_log("Unexpected number of rows updated: " . $updated_rows);
            }
        } catch (PDOException $e) {
            // handle existing email/username error
            users_check_duplicate($e);
        } catch (Exception $e) {
            flash("An unexpected error occurred, please try again", "danger");
            error_log("Unexpected Error updating user details: " . var_export($e, true));
        }
        if ($saved) {
            //select fresh data from table
            $stmt = $db->prepare("SELECT email, username from Users where id = :id LIMIT 1");
            try {
                $stmt->execute([":id" => get_user_id()]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    //$_SESSION["user"] = $user; // don't overwrite the entire session data, just update the specific fields
                    $_SESSION["user"]["email"] = $user["email"];
                    $_SESSION["user"]["username"] = $user["username"];
                    // since this comes after the setting of $username and $email at the top, we'll apply the edits to them too
                    $username = $user["username"];
                    $email = $user["email"];
                } else {
                    // This shouldn't happen, but we add logs/notification just in case
                    flash("User doesn't exist", "danger");
                    error_log("User doesn't exist");
                }
            } catch (PDOException $e) {
                flash("An unexpected error occurred, please try again", "danger");
                error_log("DB Error fetching user details: " . var_export($e, true));
            } catch (Exception $e) {
                flash("An unexpected error occurred, please try again", "danger");
                error_log("Unexpected Error fetching user details: " . var_export($e, true));
            }
        }
    }
}
// handle password update
if ($is_edit && isset($_POST["currentPassword"], $_POST["newPassword"], $_POST["confirmPassword"])) {

    //check/update password
    $current_password = se($_POST, "currentPassword", null, false);
    $new_password = se($_POST, "newPassword", null, false);
    $confirm_password = se($_POST, "confirmPassword", null, false);
    // require all 3 to be set before attempting to process
    $can_update = !empty($current_password) && !empty($new_password) && !empty($confirm_password);
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (!$can_update) {
            flash("To change your password, fill Current Password, New Password, and Confirm Password.", "warning");
        }
    }
    if ($can_update) {
        // check that new matches confirm (i.e., no typos)
        if (!is_valid_confirm($new_password,$confirm_password)) {
            flash("New passwords don't match", "warning");
        } else {
            //validate current password against password rules
            $hasError = false;
            if (!is_valid_password($new_password)) {
                //echo "Password too short<br>";
                flash("Password must be at least 8 characters long.", "danger");
                $hasError = true;
            }
            if (!$hasError) {
                // fetch current hash
                try {
                    $db = getDB();
                    $stmt = $db->prepare("SELECT password from Users where id = :id");
                    // using get_user_id() in this block to ensure we don't mistakenly allow changing someone else's password
                    $stmt->execute([":id" => get_user_id()]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (isset($result["password"])) {
                        // verify current vs hash
                        if (!password_verify($current_password, $result["password"])) {
                            flash("Current password is invalid", "warning");
                        } else {
                            // change password
                            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                            $query = "UPDATE Users set password = :password where id = :id";
                            $stmt = $db->prepare($query);
                            $stmt->execute([
                                ":id" => get_user_id(),
                                ":password" => $new_hash
                            ]);
                            $updated_rows = $stmt->rowCount();
                            if ($updated_rows === 0) {
                                flash("No changes made to password", "warning");
                            } else if ($updated_rows == 1) {
                                flash("Password updated successfully", "success");
                            } else {
                                // this shouldn't happen, but we log it just in case
                                error_log("Unexpected number of rows updated for password change: " . $updated_rows);
                            }
                        }
                    } else {
                        error_log("No password field in result");
                    }
                } catch (Exception $e) {
                    flash("Error processing password change", "danger");
                    error_log("Error processing password change: " . var_export($e, true));
                }
            }
        }
    }
}
$db = getDB();
$stmt = $db->prepare("SELECT id, username, email, created FROM Users WHERE id = :id");
$stmt->execute([":id" => $view_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    flash("User not found", "warning");
    die(header("Location: " . get_url("landing.php")));
}
?>


<h3>Profile</h3>

<?php if ($is_me): ?>
    <?php if ($is_edit): ?>
        <a class="btn btn-secondary" href="<?php echo get_url("profile.php", true); ?>?id=<?php echo urlencode((string)$view_id); ?>">View Profile</a>
    <?php else: ?>
        <a class="btn btn-secondary" href="<?php echo get_url("profile.php", true); ?>?id=<?php echo urlencode((string)$view_id); ?>&edit=1">Edit Profile</a>
    <?php endif; ?>
<?php endif; ?>

<?php if ($is_edit): ?>
    <!-- KEEP YOUR EXISTING FORM HERE (your current edit form code) -->
    <form method="POST" onsubmit="return validate(this);">
        ...
    </form>

    <script>
        // KEEP your existing validate(form) JS here
    </script>

<?php else: ?>
    <!-- PUBLIC VIEW -->
    <div class="card" style="max-width: 400px;">
        <div class="card-body">
            <h5 class="card-title"><?php se($profile_user, "username"); ?></h5>
            <p><b>Joined:</b> <?php echo date("F j, Y", strtotime(se($profile_user, "created", "", false))); ?></p>

            <?php if ($is_me): ?>
                <p><b>Email:</b> <?php se($profile_user, "email"); ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
//   UCID: dns33
//   Date: 04/11/2026
  // Summary: JS validation for email/username format and optional password change rules
  function validate(form) {
    const email = form.email?.value.trim() || "";
    const username = form.username?.value.trim() || "";

    const pw = form.newPassword?.value.trim() || "";
    const con = form.confirmPassword?.value.trim() || "";
    const cp = form.currentPassword?.value.trim() || "";

    const flash = document.getElementById("flash");
    if (flash) flash.innerHTML = "";

    function showMsg(message) {
      if (!flash) return;
      const outerDiv = document.createElement("div");
      outerDiv.className = "row justify-content-center";

      const innerDiv = document.createElement("div");
      innerDiv.className = "alert alert-warning";
      innerDiv.innerText = message;

      outerDiv.appendChild(innerDiv);
      flash.appendChild(outerDiv);
    }

    // Email check (simple but decent)
    if (!email || !email.includes("@") || email.lastIndexOf(".") < email.indexOf("@") + 2) {
      showMsg("Email must be a valid email address.");
      return false;
    }

    // Username check
    const userRegex = /^[a-z0-9_-]+$/;
    if (!username || !userRegex.test(username)) {
      showMsg("Username must be lowercase and can only contain letters, numbers, _ or -.");
      return false;
    }

    // If not changing password, allow submit
    if (!pw && !con && !cp) return true;

    // If trying to change password, require all 3
    if (!cp || !pw || !con) {
      showMsg("To change your password, fill Current Password, New Password, and Confirm Password.");
      return false;
    }

    // Password length
    if (pw.length < 8) {
      showMsg("New password must be at least 8 characters.");
      return false;
    }

    // Confirm match
    if (pw !== con) {
      showMsg("New Password and Confirm password must match.");
      return false;
    }

    return true;
  }
</script>

<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>