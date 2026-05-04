<?php
ob_start();
require(__DIR__ . "/../../../partials/nav.php");

is_logged_in(true); // must be logged in first

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    header("Location: " . get_url("landing.php"));
    exit;
}

if (isset($_POST["role_id"])) {
    $role_id = se($_POST, "role_id", "", false);
    if (!empty($role_id)) {
        $db = getDB();
        // toggle is_active via negation
        $stmt = $db->prepare("UPDATE Roles SET is_active = !is_active WHERE id = :role_id");
        try {
            $stmt->execute([":role_id" => $role_id]);
            flash("Updated Role", "success");
        } catch (PDOException $e) {
            flash("There was an error toggling the role, please try again later", "danger");
            error_log("Error toggling role: " . var_export($e->errorInfo, true));
        }
    }
}
$query = "SELECT id, name, description, is_active from Roles";
$params = null;
if (isset($_POST["role"])) {
    $search = se($_POST, "role", "", false);
    $query .= " WHERE name LIKE :role";
    // for LIKE queries, we need to use wildcards that get added to the data rather than the query
    $params =  [":role" => "%$search%"];
}
// always apply some finite limit to avoid performance issues
$query .= " ORDER BY modified desc LIMIT 10";
$db = getDB();
$stmt = $db->prepare($query);
$roles = [];
try {
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($results) {
        $roles = $results;
    } else {
        flash("No matches found", "warning");
    }
} catch (PDOException $e) {
    flash("There was an error fetching roles, please try again later", "danger");
    error_log("Error fetching roles: " . var_export($e->errorInfo, true));
}

?>
<div class="container-fluid" style="max-width: 1100px;">
  <h3 class="text-center my-3">List Roles</h3>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="POST" class="row g-2 align-items-center">
        <div class="col-md-9">
          <label class="form-label fw-semibold">Role Filter</label>
          <input
            type="search"
            class="form-control"
            name="role"
            placeholder="Search role name (partial match)"
            value="<?php se($_POST, "role"); ?>"
          />
        </div>
        <div class="col-md-3 d-grid mt-4 mt-md-0">
          <button type="submit" class="btn btn-primary">Search</button>
        </div>
      </form>

      <small class="text-muted d-block mt-2">
        Note: If you disable <b>Admin</b>, you may not be able to login as Admin again until it is re-enabled (may require a manual table edit).
      </small>
    </div>
  </div>

  <?php if (empty($roles)) : ?>
    <div class="alert alert-warning">No roles found.</div>
  <?php else : ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th style="width:80px;">ID</th>
                <th style="width:160px;">Name</th>
                <th>Description</th>
                <th style="width:120px;">Status</th>
                <th style="width:140px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($roles as $role) : ?>
                <tr>
                  <td><?php se($role, "id"); ?></td>
                  <td class="fw-semibold"><?php se($role, "name"); ?></td>
                  <td><?php se($role, "description", "—"); ?></td>
                  <td>
                    <?php if (se($role, "is_active", 0, false)) : ?>
                      <span class="badge text-bg-success">active</span>
                    <?php else : ?>
                      <span class="badge text-bg-secondary">disabled</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="POST" class="m-0">
                      <input type="hidden" name="role_id" value="<?php se($role, 'id'); ?>" />
                      <?php if (isset($search) && !empty($search)) : ?>
                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($search); ?>" />
                      <?php endif; ?>

                      <?php if (se($role, "is_active", 0, false)) : ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Disable</button>
                      <?php else : ?>
                        <button type="submit" class="btn btn-sm btn-outline-success w-100">Enable</button>
                      <?php endif; ?>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>
<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>