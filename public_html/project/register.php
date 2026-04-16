<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<h3>Register</h3>

<!-- UCID: dns33 | Date: 04/10/2026 | JS validation -->
<form method="POST" onsubmit="return validate(this);">
  <div>
    <label for="email">Email</label>
    <input id="email" type="email" name="email" required value="<?php se($_POST,'email'); ?>" />
  </div>

  <div>
    <label for="username">Username</label>
    <input id="username" type="text" name="username" required maxlength="30" value="<?php se($_POST,'username'); ?>" />
  </div>

  <div>
    <label for="pw">Password</label>
    <input id="pw" type="password" name="password" required minlength="8" />
  </div>

  <div>
    <label for="confirm">Confirm</label>
    <input id="confirm" type="password" name="confirm" required minlength="8" />
  </div>

  <input type="submit" value="Register" />
</form>
<script>
  // UCID: dns33 | Date: 04/10/2026
  // Summary: Client-side validation for email, username, password, confirm match
  function validate(form) {
    const email = form.email.value.trim();
    const username = form.username.value.trim();
    const pw = form.password.value;
    const confirm = form.confirm.value;

    // 1) email format
    if (!email.includes("@") || !email.includes(".")) {
      flash("Email format is invalid. Example: user@example.com");
      return false;
    }

    // 2) username format: lowercase + numbers + _ or -
    const userRegex = /^[a-z0-9_-]+$/;
    if (!userRegex.test(username)) {
      flash("Username must be lowercase and only contain letters, numbers, _ or -");
      return false;
    }

    // 3) password format (length)
    if (pw.length < 8) {
      flash("Password must be at least 8 characters.");
      return false;
    }

    // 4) confirm match
    if (pw !== confirm) {
      flash("Password and Confirm must match.");
      return false;
    }

    return true;
  }
</script>
<?php
// UCID: dns33
// Date: 04/10/2026
// Summary: Server-side validation + insert + duplicate handling
//TODO 2: add PHP Code
if (isset($_POST["email"], $_POST["password"], $_POST["confirm"], $_POST["username"])) {

    $email = se($_POST, "email", "", false);
    $password = se($_POST, "password", "", false);
    $confirm = se($_POST, "confirm", "", false);
    $username = se($_POST, "username", "", false);
    // TODO 3: validate/use
    $hasError = false;

    if (empty($email)) {
        flash("Email must not be empty.", "danger");
        $hasError = true;
    }
    // Sanitize and validate email
    $email = sanitize_email($email);
    if (!is_valid_email($email)) {
        flash("Invalid email address.", "danger");
        $hasError = true;
    }
    if (!is_valid_username($username)) {
        flash("Username must be lowercase, alphanumerical, and can only contain _ or -", "danger");
        $hasError = true;
    }
    if (empty($password)) {
        flash("Password must not be empty.", "danger");
        $hasError = true;
    }

    if (empty($confirm)) {
        flash("Confirm password must not be empty.", "danger");
        $hasError = true;
    }

    if (!is_valid_password($password)) {
        flash("Password must be at least 8 characters long.", "danger");
        $hasError = true;
    }

    if (!is_valid_confirm($password, $confirm)) {
        flash("Passwords must match.", "danger");
        $hasError = true;
    }

    if (!$hasError) {
        // TODO 4: Hash password and store record in DB
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $db = getDB(); // available due to the `require()` of `functions.php`
        // Code for inserting user data into the database
        $stmt = $db->prepare("INSERT INTO Users (email, password, username) VALUES (:email, :password, :username)");
        try {
            $stmt->execute([':email' => $email, ':password' => $hashed_password, ':username' => $username]);
   
            flash("Successfully registered! You can now log in.", "success");
        } catch(PDOException $e) {
            // Handle duplicate email/username
            users_check_duplicate($e);
        }
        catch (Exception $e) {
            flash("There was an error registering. Please try again.", "danger");
            error_log("Registration Error: " . var_export($e, true)); // log the technical error for debugging
        }
    }
}
?>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>