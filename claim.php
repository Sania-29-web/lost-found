<?php
include('db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Item</title>
    <link rel="stylesheet" href="claim.css">
</head>
<body>
<main>
    <nav>
        <h2>LOSTMATE</h2>
        <div class="icons">
            <a href="home.html">HOME</a>
            <a href="lost.php">Report Lost</a>
            <a href="found.php">Report Found</a>
            <a href="claimed.php">Claimed Items</a>

        </div>
    </nav>

    <div class="card">
        <?php
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);

            // Fetch found item details
            $getItem = "SELECT * FROM report_found WHERE id = $id";
            $result = $con->query($getItem);

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();

                $item_name = $row['item_name'];
                $description = $row['description'];
                $location = $row['location'];
                $finder_name = $row['name'];
                $finder_contact = $row['contact'];
                $finder_email = $row['email'];
                $photo = $row['photo'];

                if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                    echo '
                    <link rel="stylesheet" href="style.css">
                    <div class="container" style="padding: 10px;">
                        <h2>Enter Your Details to Claim This Item</h2>
                        <form method="POST">
                            <input type="text" name="claimer_name" placeholder="Your Name" required><br><br>
                            <input type="text" name="claimer_contact" placeholder="Your Contact Number" required><br><br>
                            <input type="email" name="claimer_email" placeholder="Your Email" required><br><br>
                            <button type="submit" class="btn">Claim Now</button>
                        </form>
                    </div>';
                    exit();
                }

                // Get claimer info from form
                $claimer_name = $_POST['claimer_name'];
                $claimer_contact = $_POST['claimer_contact'];
                $claimer_email = $_POST['claimer_email'];

                // Insert into claimed_items (also insert the photo)
                $insert = "INSERT INTO claimed_items (
                    item_name, description, location, finder_name, finder_contact, finder_email, 
                    claimer_name, claimer_contact, claimer_email, photo
                ) VALUES (
                    '$item_name', '$description', '$location', '$finder_name', '$finder_contact', '$finder_email',
                    '$claimer_name', '$claimer_contact', '$claimer_email', '$photo'
                )";
                $con->query($insert);

                // Delete from report_found
                $con->query("DELETE FROM report_found WHERE id = $id");

                // Delete from report_lost if exists
                $con->query("DELETE FROM report_lost WHERE item_name = '$item_name' AND location = '$location'");

                // Show confirmation
                echo '<link rel="stylesheet" href="style.css">';
                echo '<div class="container" style="padding: 40px;">';
                echo '<h2>Item Claimed Successfully!</h2>';

                // Show item photo if available
                if (!empty($photo) && file_exists($photo)) {
                    echo "<img src='" . htmlspecialchars($photo) . "' alt='Item Photo' style='max-width: 200px; height: auto;'><br><br>";
                }

                echo '<p><strong>Finder Name:</strong> ' . htmlspecialchars($finder_name) . '</p>';
                echo '<p><strong>Finder Contact:</strong> ' . htmlspecialchars($finder_contact) . '</p>';
                echo '<p><strong>Finder Email:</strong> ' . htmlspecialchars($finder_email) . '</p>';
                echo '<br><a href="search.php">Back to Search</a>';
                echo '</div>';
            } else {
                echo "<p>Item not found.</p>";
            }
        } else {
            echo "<p>Invalid request. No ID provided.</p>";
        }

        $con->close();
        ?>
    </div>
</main>
</body>
</html>
