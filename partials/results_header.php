<?php
// UCID: dns33
// Date: 04/26/2026
// Summary: Results header partial (shown vs total).

if (!isset($result_stats) || !is_array($result_stats)) {
    echo "<p>No Result Data</p>";
    return;
}

$current = (int)($result_stats["current"] ?? 0);
$total = (int)($result_stats["total"] ?? 0);
?>
<div class="mb-2">
    <b>Results:</b> <?php echo htmlspecialchars((string)$current); ?> / <?php echo htmlspecialchars((string)$total); ?>
</div>