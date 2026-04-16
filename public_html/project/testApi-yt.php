<?php
// UCID: dns33
// Date: 04/10/2026
// Summary: Test YouTube Search & Download (RapidAPI) channel search using get() + env keys.

require(__DIR__ . "/../../partials/nav.php");

global $API_KEYS;

$result = [];
if (isset($_GET["id"], $_GET["query"])) {
    $id = trim(se($_GET, "id", "", false));
    $query = trim(se($_GET, "query", "", false));
    $next = trim(se($_GET, "next", "", false));

    $endpoint = "https://youtube-search-and-download.p.rapidapi.com/channel/search";

    // ✅ pull host from .env / Render env vars (fallback just in case)
    $rapidAPIHost = $API_KEYS["RAPIDAPI_HOST"] ?? "youtube-search-and-download.p.rapidapi.com";

    $data = [
        "id" => $id,
        "query" => $query,
    ];

    if (!empty($next)) {
        $data["next"] = $next; 
    }

    try {
        
        $raw = get($endpoint, "RAPIDAPI_KEY", $data, true, $rapidAPIHost);

        error_log("YT API Raw: " . var_export($raw, true));

        if (se($raw, "status", 400, false) == 200 && isset($raw["response"])) {
            $result = json_decode($raw["response"], true);
        } else {
            $result = $raw; // show error payload
        }
    } catch (Exception $e) {
        $result = ["error" => $e->getMessage()];
        error_log("YT API Error: " . var_export($e, true));
    }
}
?>
<div class="container-fluid">
    <h1>YouTube Channel Search Test</h1>

    <form>
        <div>
            <label>Channel ID</label>
            <input name="id" value="<?php echo htmlspecialchars(se($_GET, "id", "", false)); ?>" />
        </div>

        <div>
            <label>Query</label>
            <input name="query" value="<?php echo htmlspecialchars(se($_GET, "query", "", false)); ?>" />
        </div>

        <div>
            <label>Next (optional)</label>
            <input name="next" value="<?php echo htmlspecialchars(se($_GET, "next", "", false)); ?>" />
        </div>

        <input type="submit" value="Search" />
    </form>

    <?php if (!empty($result)) : ?>
        <pre><?php var_export($result); ?></pre>
    <?php endif; ?>
</div>

<?php require(__DIR__ . "/../../partials/flash.php"); ?>