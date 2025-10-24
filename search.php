<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Lost Items</title>
    <link rel="stylesheet" href="search.css">
</head>
<body>
<main>
    <nav>
        <h2>LOSTMATE</h2>
        <div class="icons">
            <a href="home.html">HOME</a>
            <a href="lost.php">Report Lost</a>
            <a href="found.php">Report Found</a>
            <a href="search.php">Search</a>
            <a href="claimed.php">Claimed Items</a>
        </div>
    </nav>

    <h1>Search</h1>
    <form action="search.php" method="POST">
        <div class="form">
            <input type="text" name="name" placeholder="Item to find">
            <button class="btn">Search</button>
        </div>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        include('db.php');

        $search = mysqli_real_escape_string($con, $_POST['name']);
        $sql = "SELECT * FROM report_found WHERE item_name LIKE '%$search%' OR description LIKE '%$search%' OR location LIKE '%$search%'";
        $result = $con->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "<h4>Search Results:</h4>";
            while ($row = $result->fetch_assoc()) {
                echo "<div class='card'>";
                
                // Show image if available
                if (!empty($row['photo']) && file_exists($row['photo'])) {
                    echo "<img src='" . htmlspecialchars($row['photo']) . "' alt='Item Photo' style='max-width: 200px; height: auto;'><br>";
                }

                echo "<h3>" . htmlspecialchars($row['item_name']) . "</h3>";
                echo "<p><strong>Description:</strong> " . htmlspecialchars($row['description']) . "</p>";
                echo "<p><strong>Location:</strong> " . htmlspecialchars($row['location']) . "</p>";
                echo "<a href='claim.php?id=" . $row['id'] . "'><button class='btn'>Claim</button></a>";
                echo "</div>";
            }
        } else {
            echo "<div class='card message'>";
            echo "<h3>No Match Found</h3>";
            echo "<p>We couldn't find any items that match your search for <strong>" . htmlspecialchars($search) . "</strong>.</p>";
            echo "<p>Try searching with different keywords or check back later.</p>";
            echo "</div>";
        }

        $con->close();
    }
    ?>
</main>
</body>
</html>
