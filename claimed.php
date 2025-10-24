<?php
include('db.php');
$result = $con->query("SELECT * FROM claimed_items ORDER BY claim_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claimed Items</title>
    <link rel="stylesheet" href="/lostmate/search.css">
</head>
<body>
<main>
    <nav>
        <h2>LOSTMATE</h2>
        <div class="icons">
            <a href="/lostmate/home.html">HOME</a>
            <a href="/lostmate/lost.php">Report Lost</a>
            <a href="/lostmate/found.php">Report Found</a>
            <a href="/lostmate/claimed.php">Claimed Items</a>
        </div>
    </nav>

    <h1>Claimed Items History</h1>

    <?php
    // --- NEW: Check if there are any results ---
    if ($result && $result->num_rows > 0) {
        // If there are results, loop through and display them
        while ($row = $result->fetch_assoc()) {
            echo "<div class='card'>";

            if (!empty($row['photo']) && file_exists($row['photo'])) {
                echo "<img src='/lostmate/" . htmlspecialchars($row['photo']) . "' alt='Item Photo' style='max-width: 200px; height: auto; border-radius: 8px;'><br>";
            }

            echo "<h3>" . htmlspecialchars($row['item_name']) . "</h3>";
            echo "<p><strong>Description:</strong> " . htmlspecialchars($row['description']) . "</p>";
            echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location']) . "</p>";
            echo "<p><strong>Founded By:</strong> " . htmlspecialchars($row['finder_name']) . "</p>";
            echo "<p><strong>Claimed By:</strong> " . htmlspecialchars($row['claimer_name']) . "</p>";
            echo "<p><strong>Claimed On:</strong> " . htmlspecialchars($row['claim_date']) . "</p>";
            echo "</div>";
        }
    } else {
        // If there are no results, display the user-friendly message
        echo "<div class='card message'>";
        echo "<h3>Nothing Here Yet!</h3>";
        echo "<p>No items have been claimed yet. This page will fill up as items are successfully returned to their owners.</p>";
        echo "</div>";
    }
    $con->close();
    ?>
</main>
</body>
</html>