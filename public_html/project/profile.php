<?php
ob_start();
require_once(__DIR__ . "/../../partials/nav.php");
is_logged_in(true); // keep login required (Milestone 3 is fine with this)

// Session user
$session_id = (int)get_user_id();

// Which profile are we viewing?
$view_id = (int)se($_GET, "id", $session_id, false);
if ($view_id <= 0) {
    flash("Invalid user id", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// Are we viewing our own profile?
$is_me = ($view_id === $session_id);
$is_edit = ($is_me && isset($_GET["edit"]));

// Load the viewed user's info from DB
$db = getDB();
$stmt = $db->prepare("SELECT id, username, email, created FROM Users WHERE id = :id LIMIT 1");
$stmt->execute([":id" => $view_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    flash("User not found", "warning");
    die(header("Location: " . get_url("landing.php")));
}

// Convenience vars
$profile_username = se($profile_user, "username", "", false);
$profile_email = se($profile_user, "email", "", false);

// -------------------- UPDATE (ONLY if editing your own profile) --------------------
if ($is_edit && isset($_POST["email"], $_POST["username"])) {
    $new_email = se($_POST, "email", "", false);
    $new_username = se($_POST, "username", "", false);
    $hasError = false;

    if (empty(trim($new_email))) {
        flash("Email must not be empty.", "danger");
        $hasError = true;
    }

    $new_email = sanitize_email($new_email);
    if (!is_valid_email($new_email)) {
        flash("Invalid email address.", "danger");
        $hasError = true;
    }

    if (!is_valid_username($new_username)) {
        flash("Username must be lowercase, alphanumerical, and can only contain _ or -", "danger");
        $hasError = true;
    }

    // Only update if changed
    if (!$hasError && ($new_email !== $profile_email || $new_username !== $profile_username)) {
        try {
            $stmt = $db->prepare("UPDATE Users SET email = :email, username = :username WHERE id = :id");
            $stmt->execute([
                ":email" => $new_email,
                ":username" => $new_username,
                ":id" => $session_id // IMPORTANT: only update logged-in user
            ]);

            if ($stmt->rowCount() > 0) {
                flash("Profile saved", "success");
            } else {
                flash("No changes made", "warning");
            }
        } catch (PDOException $e) {
            users_check_duplicate($e);
        } catch (Exception $e) {
            flash("An unexpected error occurred, please try again", "danger");
            error_log("Unexpected Error updating user details: " . var_export($e, true));
        }

        // Reload fresh data (for display)
        $stmt = $db->prepare("SELECT id, username, email, created FROM Users WHERE id = :id LIMIT 1");
        $stmt->execute([":id" => $session_id]);
        $profile_user = $stmt->fetch(PDO::FETCH_ASSOC) ?: $profile_user;

        $profile_username = se($profile_user, "username", "", false);
        $profile_email = se($profile_user, "email", "", false);

        // Update session too
        $_SESSION["user"]["email"] = $profile_email;
        $_SESSION["user"]["username"] = $profile_username;
    }
}

// Password update (ONLY if editing your own profile)
if (
    $is_edit &&
    isset($_POST["currentPassword"], $_POST["newPassword"], $_POST["confirmPassword"])
) {
    $current_password = se($_POST, "currentPassword", "", false);
    $new_password = se($_POST, "newPassword", "", false);
    $confirm_password = se($_POST, "confirmPassword", "", false);

    $can_update = !empty($current_password) && !empty($new_password) && !empty($confirm_password);

    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (!$can_update) {
            flash("To change your password, fill Current Password, New Password, and Confirm Password.", "warning");
        }
    }

    if ($can_update) {
        if (!is_valid_confirm($new_password, $confirm_password)) {
            flash("New passwords don't match", "warning");
        } else if (!is_valid_password($new_password)) {
            flash("Password must be at least 8 characters long.", "danger");
        } else {
            try {
                $stmt = $db->prepare("SELECT password FROM Users WHERE id = :id LIMIT 1");
                $stmt->execute([":id" => $session_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result && isset($result["password"])) {
                    if (!password_verify($current_password, $result["password"])) {
                        flash("Current password is invalid", "warning");
                    } else {
                        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("UPDATE Users SET password = :password WHERE id = :id");
                        $stmt->execute([":password" => $new_hash, ":id" => $session_id]);

                        if ($stmt->rowCount() > 0) {
                            flash("Password updated successfully", "success");
                        } else {
                            flash("No changes made to password", "warning");
                        }
                    }
                } else {
                    flash("Error processing password change", "danger");
                    error_log("No password field in result");
                }
            } catch (Exception $e) {
                flash("Error processing password change", "danger");
                error_log("Error processing password change: " . var_export($e, true));
            }
        }
    }
}
?>

<div class="container-fluid">
    <h3>Profile</h3>

    <?php if ($is_me): ?>
        <?php if ($is_edit): ?>
            <a class="btn btn-secondary mb-3" href="<?php echo get_url("profile.php", true); ?>?id=<?php echo $session_id; ?>">View Profile</a>
        <?php else: ?>
            <a class="btn btn-secondary mb-3" href="<?php echo get_url("profile.php", true); ?>?id=<?php echo $session_id; ?>&edit=1">Edit Profile</a>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($is_edit): ?>
        <!-- EDIT VIEW (only for your own profile) -->
        <form method="POST" onsubmit="return validate(this);">
            <div class="mb-3">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($profile_email); ?>" required />
            </div>
            <div class="mb-3">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($profile_username); ?>" required maxlength="30" />
            </div>

            <hr>
            <div class="fw-bold mb-2">Password Reset</div>
            <div class="mb-3">
                <label for="cp">Current Password</label>
                <input type="password" name="currentPassword" id="cp" />
            </div>
            <div class="mb-3">
                <label for="np">New Password</label>
                <input type="password" name="newPassword" id="np" />
            </div>
            <div class="mb-3">
                <label for="conp">Confirm Password</label>
                <input type="password" name="confirmPassword" id="conp" />
            </div>

            <input type="submit" value="Update Profile" class="btn btn-primary" />
        </form>

        <script>
        function validate(form) {
            const email = (form.email?.value || "").trim();
            const username = (form.username?.value || "").trim();

            const pw = (form.newPassword?.value || "").trim();
            const con = (form.confirmPassword?.value || "").trim();
            const cp = (form.currentPassword?.value || "").trim();

            const flash = document.getElementById("flash");
            if (flash) flash.innerHTML = "";

            function showMsg(message) {
                if (!flash) { alert(message); return; }
                const outerDiv = document.createElement("div");
                outerDiv.className = "row justify-content-center";
                const innerDiv = document.createElement("div");
                innerDiv.className = "alert alert-warning";
                innerDiv.innerText = message;
                outerDiv.appendChild(innerDiv);
                flash.appendChild(outerDiv);
            }

            if (!email || !email.includes("@")) {
                showMsg("Email must be a valid email address.");
                return false;
            }

            const userRegex = /^[a-z0-9_-]+$/;
            if (!username || !userRegex.test(username)) {
                showMsg("Username must be lowercase and can only contain letters, numbers, _ or -.");
                return false;
            }

            // If not changing password, allow submit
            if (!pw && !con && !cp) return true;

            if (!cp || !pw || !con) {
                showMsg("To change your password, fill Current Password, New Password, and Confirm Password.");
                return false;
            }

            if (pw.length < 8) {
                showMsg("New password must be at least 8 characters.");
                return false;
            }

            if (pw !== con) {
                showMsg("New Password and Confirm password must match.");
                return false;
            }

            return true;
        }
        </script>

    <?php else: ?>
        <!-- PUBLIC VIEW -->
        <div class="card" style="max-width: 450px;">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($profile_username); ?></h5>
                <p><b>Joined:</b> <?php echo date("F j, Y", strtotime(se($profile_user, "created", "", false))); ?></p>

                <?php if ($is_me): ?>
                    <p><b>Email:</b> <?php echo htmlspecialchars($profile_email); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once(__DIR__ . "/../../partials/flash.php"); ?>