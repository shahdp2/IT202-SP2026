<?php
ob_start();
require(__DIR__ . "/../../../partials/nav.php");

is_logged_in(true); // must be logged in first

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    header("Location: " . get_url("landing.php"));
    exit;
}

if (isset($_POST["users"], $_POST["roles"])) {
    $user_ids = $_POST["users"]; //se() doesn't like arrays so we'll just do this
    $role_ids = $_POST["roles"]; //se() doesn't like arrays so we'll just do this
    if (empty($user_ids) || empty($role_ids)) {
        flash("Both users and roles need to be selected", "warning");
    } else {
        //for sake of simplicity, this will be a tad inefficient (normally bulk operations should fail/pass together)
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO UserRoles (user_id, role_id, is_active) VALUES (:uid, :rid, 1) 
        ON DUPLICATE KEY UPDATE is_active = !is_active");

        // triggers 1 query per pair, that way an exception will only affect that pair rather than the bulk operation
        foreach ($user_ids as $uid) {
            foreach ($role_ids as $rid) {
                try {
                    $stmt->execute([":uid" => $uid, ":rid" => $rid]);
                    if ($stmt->rowCount() > 0) {
                        flash("Toggled role for user $uid and role $rid", "success");
                    } else {
                        flash("No changes made for user $uid and role $rid", "warning");
                    }
                } catch (PDOException $e) {
                    flash("There was an error toggling the role, please try again later", "danger");
                    error_log("Error toggling role for user $uid and role $rid: " . var_export($e->errorInfo, true));
                }
            }
        }
    }
}



//search for user by username
$users = [];
$active_roles = [];
$username = "";
if (isset($_POST["username"])) {

    $username = trim(se($_POST, "username", "", false));
    if (!empty($username)) {
        //get active roles only if a username was submitted
        $active_roles = [];
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, description FROM Roles WHERE is_active = 1 LIMIT 10");
        try {
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $active_roles = $results;
            }
        } catch (PDOException $e) {
            flash(var_export($e->errorInfo, true), "danger");
        }
        //fetch usernames with a csv of roles and their active status
        // Note: the role status will show inactive only if the role has been assigned at least once
        // We're effectively doing a soft delete by toggling `is_active` to 0.
        // Alternatively, we could simply delete the UserRole entry, but that would lose history.
        $stmt = $db->prepare("SELECT Users.id, username, 
        (SELECT GROUP_CONCAT(name, ' (' , IF(ur.is_active = 1,'active','inactive') , ')') from 
        UserRoles ur 
        JOIN Roles on ur.role_id = Roles.id 
        WHERE ur.user_id = Users.id) as roles
        from Users WHERE username like :username");
        try {
            $stmt->execute([":username" => "%$username%"]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $users = $results;
            }
        } catch (PDOException $e) {
            flash(var_export($e->errorInfo, true), "danger");
        }
    } else {
        flash("Username must not be empty", "warning");
    }
}


?>
<h3 class="text-center my-3">Assign Roles</h3>

<div class="container-fluid" style="max-width: 1100px;">

  <!-- Search Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="POST" class="row g-2 align-items-center">
        <div class="col-md-9">
          <label class="form-label fw-semibold">Username search</label>
          <input
            type="search"
            class="form-control"
            name="username"
            placeholder="Search by username (partial match)"
            value="<?php se($username, false); ?>"
          />
        </div>
        <div class="col-md-3 d-grid mt-4 mt-md-0">
          <button type="submit" class="btn btn-primary">Search</button>
        </div>
      </form>

      <?php if (isset($username) && !empty($username)) : ?>
        <div class="mt-2 text-muted">
          Showing results for: <span class="fw-semibold"><?php se($username, false); ?></span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Toggle Form -->
  <form id="toggleForm" method="POST"></form>
  <?php if (isset($username) && !empty($username)) : ?>
    <input form="toggleForm" type="hidden" name="username" value="<?php se($username, false); ?>" />
  <?php endif; ?>

  <div class="row g-4">
    <!-- Users -->
    <div class="col-lg-7">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold">Users</div>
            <small class="text-muted">Select user(s) to toggle roles</small>
          </div>
        </div>

        <div class="card-body">
          <?php if (!isset($username) || empty($username)) : ?>
            <p class="text-muted mb-0">Search a username to load users and roles.</p>

          <?php elseif (count($users) === 0) : ?>
            <p class="text-muted mb-0">No results available.</p>

          <?php else : ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th style="width: 80px;">Pick</th>
                    <th>User</th>
                    <th>Current Roles</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $user) : ?>
                    <tr>
                      <td>
                        <input
                          form="toggleForm"
                          class="form-check-input"
                          id="user_<?php se($user, 'id'); ?>"
                          type="checkbox"
                          name="users[]"
                          value="<?php se($user, 'id'); ?>"
                        />
                      </td>
                      <td>
                        <label class="fw-semibold" for="user_<?php se($user, 'id'); ?>">
                          <?php se($user, "username"); ?>
                        </label>
                      </td>
                      <td>
                        <?php
                          $rolesText = se($user, "roles", "", false);
                          if (!$rolesText) {
                            echo '<span class="badge text-bg-secondary">No Roles</span>';
                          } else {
                            // Render as light badge chips
                            $parts = array_filter(array_map("trim", explode(",", $rolesText)));
                            foreach ($parts as $p) {
                              echo '<span class="badge text-bg-light border me-1 mb-1">' . htmlspecialchars($p) . '</span>';
                            }
                          }
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Roles -->
    <div class="col-lg-5">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white">
          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-bold">Roles to Assign</div>
            <small class="text-muted">Select role(s)</small>
          </div>
        </div>

        <div class="card-body">
          <?php if (!isset($username) || empty($username)) : ?>
            <p class="text-muted mb-0">Search a username first.</p>

          <?php elseif (count($active_roles) === 0) : ?>
            <p class="text-muted mb-0">No active roles available.</p>

          <?php else : ?>
            <div class="d-flex flex-column gap-2">
              <?php foreach ($active_roles as $role) : ?>
                <div class="form-check">
                  <input
                    form="toggleForm"
                    class="form-check-input"
                    id="role_<?php se($role, 'id'); ?>"
                    type="checkbox"
                    name="roles[]"
                    value="<?php se($role, 'id'); ?>"
                  />
                  <label class="form-check-label fw-semibold" for="role_<?php se($role, 'id'); ?>">
                    <?php se($role, "name"); ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="card-footer bg-white">
          <button form="toggleForm" type="submit" class="btn btn-success w-100" <?php echo (empty($users) || empty($active_roles)) ? "disabled" : ""; ?>>
            Toggle Roles
          </button>
          <small class="text-muted d-block mt-2">
            If the user already has the role, it will be disabled; otherwise it will be added.
          </small>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>

<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>