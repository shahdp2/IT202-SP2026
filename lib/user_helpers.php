<?php
/**
 * Check if the user is logged in and optionally redirect to $destination.
 * @param bool $redirect Whether to redirect if not logged in.
 * @param string $destination The destination to redirect to if not logged in (relative to BASE_PATH or absolute).
 * @return bool True if the user is logged in, false otherwise.
 */
/* UCID dns33 | date 04/10/2026 */
function is_logged_in($redirect = false, $destination = "login.php")
{
    $isLoggedIn = isset($_SESSION["user"]);

    if ($redirect && !$isLoggedIn) {
        flash("You must be logged in to view this page", "warning");
        $path = get_url($destination);

        header("Location: $path");
        exit; 
    }
    return $isLoggedIn;
}
function has_role($role)
{
    if (is_logged_in() && isset($_SESSION["user"]["roles"])) {
        foreach ($_SESSION["user"]["roles"] as $r) {
            if ($r["name"] === $role) {
                return true;
            }
        }
    }
    return false;
}
function get_username()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "username", "", false);
    }
    return "";
}
function get_user_email()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "email", "", false);
    }
    return "";
}
function get_user_id()
{
    if (is_logged_in()) { //we need to check for login first because "user" key may not exist
        return se($_SESSION["user"], "id", false, false);
    }
    return -1;
}