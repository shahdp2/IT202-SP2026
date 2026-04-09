<?php
// require functions.php to pull in flash()
// UCID dns33
// date 04/09/2026
require(__DIR__ . "/../../lib/functions.php");
reset_session(); // clear session data and start a new session
flash("You have been logged out","success");
header("Location: " . get_url("login.php")); // redirect back to login