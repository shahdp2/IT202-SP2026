<?php
ob_start();
require(__DIR__ . "/../../partials/nav.php");
?>
<h3>Login</h3>
<!--  UCID dns33  date 04/10/2026 -->
<form onsubmit="return validate(this)" method="POST">
    <div>
        <label for="email">Email or Username</label>
        <input id="email" type="text" name="email" required value="<?php se($_POST,'email'); ?>" />
    </div>
    <div>
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required minlength="8" />
    </div>
    <input type="submit" value="Login" />
</form>
<script>
//   UCID: dns33 | Date: 04/10/2026
//   Summary: Client-side validation for login (email/username + password length)

  function showMsg(message, color = "warning") {
    const flash = document.getElementById("flash");
    if (!flash) return;

    // clear old messages so they don't stack
    flash.innerHTML = "";

    const outerDiv = document.createElement("div");
    outerDiv.className = "row justify-content-center";

    const innerDiv = document.createElement("div");
    innerDiv.className = `alert alert-${color}`;
    innerDiv.innerText = message;

    outerDiv.appendChild(innerDiv);
    flash.appendChild(outerDiv);
  }

  function validate(form) {
    const login = form.email.value.trim();      // "Email or Username" input
    const pw = form.password.value;

    // password format
    if (pw.length < 8) {
      showMsg("Password must be at least 8 characters.", "warning");
      return false;
    }

    // Decide: email vs username
    const looksLikeEmail = login.includes("@");

    // email format
    if (looksLikeEmail) {
      // simple email check good enough for JS layer
      if (!login.includes(".") || login.startsWith("@") || login.endsWith("@")) {
        showMsg("Email format is invalid. Example: user@example.com", "warning");
        return false;
      }
    } else {
      // username format
      const userRegex = /^[a-z0-9_-]+$/;
      if (!userRegex.test(login)) {
        showMsg("Username must be lowercase and only contain letters, numbers, _ or -.", "warning");
        return false;
      }
    }

    return true;
  }
</script>

<!-- UCID dns33 | date 04/10/2026 -->
<?php
//TODO 2: add PHP Code
if (isset($_POST["email"], $_POST["password"])) {
    // still leveraging the property as "email", but it can be a username
    $email = se($_POST, "email", "", false);
    $password = se($_POST, "password", "", false);
    // TODO 3: validate/use
    $hasError = false;

    if (empty($email)) {
        flash("Email/Username must not be empty.", "danger");
        $hasError = true;
    }
    if (str_contains($email, "@")) {
        // if it contains an @, treat it as an email

        // Sanitize and validate email
        $email = sanitize_email($email);
        if (!is_valid_email($email)) {
            flash("Invalid email address.", "danger");
            $hasError = true;
        }
    } else {
        // otherwise, treat it as a username
        $email = strtolower(trim($email));
        if (!is_valid_username($email)) {
            flash("Username must be lowercase, alphanumerical, and can only contain _ or -", "danger");
            $hasError = true;
        }
    }


    if (empty($password)) {
        flash("Password must not be empty.", "danger");
        $hasError = true;
    }

    if (!is_valid_password($password)) {
        //echo "Password too short<br>";
        flash("Password must be at least 8 characters long.", "danger");
        $hasError = true;
    }

    if (!$hasError) {


        //TODO 4: Check password and fetch user
        $db = getDB();
        // fetch by email or username
        $stmt = $db->prepare("SELECT id, email, password, username from Users where email = :email OR username = :email");
        try {
            $r = $stmt->execute([":email" => $email]);
            if ($r) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $ambigify = false; // flag to indicate ambiguous login attempt (reduce TMI)
                if ($user) {
                    $hash = $user["password"];
                    unset($user["password"]);
                    if (password_verify($password, $hash)) {

                        $_SESSION["user"] = $user; // add the data to the active session
                        try {
                            //lookup potential roles
                            $stmt = $db->prepare("SELECT Roles.name FROM Roles
                                JOIN UserRoles on Roles.id = UserRoles.role_id
                                where UserRoles.user_id = :user_id and Roles.is_active = 1 
                                and UserRoles.is_active = 1");
                            $stmt->execute([":user_id" => get_user_id()]);
                            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC); //fetch all since we'll want multiple
                        } catch (Exception $e) {
                            error_log(var_export($e, true));
                        }
                        //save roles or empty array
                        $_SESSION["user"]["roles"] = isset($roles) ? $roles : [];

                        die(header("Location: landing.php"));
                    } else {
                        //echo "Invalid password<br>";
                        $ambigify = true; // ambiguous login attempt
                    }
                } else {
                    //echo "Email not found<br>";
                    $ambigify = true; // ambiguous login attempt
                }
                if ($ambigify) {
                    flash("Invalid login attempt. Please check your email and password.", "danger");
                }
            }
        } catch (Exception $e) {
            //echo "There was an error logging in<br>"; // user-friendly message
            flash("There was an error logging in. Please try again later.", "danger");
            error_log("Login Error: " . var_export($e, true)); // log the technical error for debugging
        }
    }
}
?>

<?php
require(__DIR__ . "/../../partials/flash.php");
?>