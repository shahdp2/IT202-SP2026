<?php
require_once(__DIR__ . "/../../../lib/db.php"); ?>

<?php
// don't edit - this
$expected_fields = ["task", "due", "assigned"];
$diff = array_diff($expected_fields, array_keys($_GET));

if (empty($diff)) {

    // data variables, don't edit
    $task = $_GET["task"];
    $due = $_GET["due"]; //hint: must be a valid MySQL date format
    $assigned = $_GET["assigned"]; // Must be "self" or a valid format (not empty or equivalent)

    $is_valid = true;
    // TODO Validate the incoming data for correct format based on the SQL table definition.
    // When not valid, provide a user-friendly message of what specifically was wrong and set $is_valid to false.
    // Assigned should check for "self" if a valid format/value isn't provided.

    // Start validations
    // can edit here

    // UCID: dns33
    // Date: 2026-03-02
    // Summary: Validate task, due date format, and assigned (default to self if invalid).

    $errors = [];
    $task = trim($task);
    if ($task === "") {
        $errors[] = "Task cannot be empty.";
    } elseif (strlen($task) > 100) {
        $errors[] = "Task must be 100 characters or less.";
    }

    $due = trim($due);
    if ($due === "") {
        $errors[] = "Due date cannot be empty.";
    } else {
        $d = DateTime::createFromFormat("Y-m-d", $due);
        if (!($d && $d->format("Y-m-d") === $due)) {
            $errors[] = "Due date must be a valid date (YYYY-MM-DD).";
        }
    }

    $assigned = trim($assigned);
    if ($assigned === "" || strtolower($assigned) === "null" || strtolower($assigned) === "undefined") {
        $assigned = "self";
    } else {
        if (!preg_match("/^[a-zA-Z0-9 _-]{2,30}$/", $assigned)) {
            $assigned = "self";
        }
    }

    if (!empty($errors)) {
        $is_valid = false;
        echo "<div style='color:red; font-weight:bold;'>Please fix the following:</div><ul>";
        foreach ($errors as $e) {
            echo "<li style='color:red;'>" . htmlspecialchars($e) . "</li>";
        }
        echo "</ul>";
    }
    // End validations

    
    if ($is_valid) {
        /*
        Design a query to insert the incoming data to the proper columns.
        Ensure valid and proper PDO named placeholders are used.
        https://phpdelusions.net/pdo
        */
        // $query = "INSERT INTO M4_Todos (task, due, assigned) VALUES (:task, :due, :assigned)";
        // $params = [
        //     ":task" => $task,
        //     ":due" => $due,
        //     ":assigned" => $assigned
        // ];
        try {
            $db = getDB();
            $stmt = $db->prepare($query);
            $r = $stmt->execute($params);
            if ($r) {
                echo "Inserted new Todo with id " . $db->lastInsertId();
            } else {
                echo "Failed to insert";
            }
        } catch (PDOException $e) {
            // extra credit
            // check if the exception was related to a unique constraint
            // provide an appropriate user-friendly message for this scenario
            // Otherwise show the default message below
            echo "There was an error inserting the record; check the logs (terminal)";
            error_log("Insert Error: " . var_export($e, true)); // shows in the terminal
        }
    } else {
        error_log("Creation input wasn't valid");
    }
}
?>
<html>

<body>
    <?php require_once(__DIR__ . "/../nav.php"); ?>
    <section>
        <h2>Create ToDo </h2>
        <form method="GET">
            <!-- design the form with proper labels and input fields with the correct types based on the SQL table.
             Wrap each label/input pair in a div tag.
             For "Assigned" ensure the default value is "self". -->
             
            <div>
              <label for="task">Task</label>
              <input id="task" type="text" name="task" required />
            </div>
            
            <div>
              <label for="due">Due Date</label>
              <input id="due" type="date" name="due" required />
            </div>
            
            <div>
              <label for="assigned">Assigned</label>
              <input id="assigned" type="text" name="assigned" value="self" />
            </div>
            
            <div>
              <input type="submit" value="Create Todo" />
            </div>
        </form>
    </section>
</body>
</body>

</html>