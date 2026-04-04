<?php
require(__DIR__."/../../partials/nav.php");
?>
<h1>Landing Page</h1>
<?php
// error_log("Session: ". var_export($_SESSION, true));
// if(isset($_SESSION["user"], $_SESSION["user"]["email"])){
//  echo "Welcome, ";
//  // Always escape user data before outputting it to prevent XSS, even if you trust your session data
//  se($_SESSION["user"]["email"]);
// }
// else{
//   echo "You're not logged in";
// }

error_log("Session: " . var_export($_SESSION, true));
if (is_logged_in()) {
    // Note, we don't need to use `se()` here since `get_user_email()` uses it internally
    echo "Welcome, " . get_user_email();
} else {
    echo "You're not logged in";
}
?>