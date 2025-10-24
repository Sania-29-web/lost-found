<?php
// Establish database connection details
$server = "localhost";
$username = "root";
$password = "";
$database = "lostmate";
$con = mysqli_connect($server, $username, $password, $database);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Flags and arrays for managing logic
$claim_successful = false;
$match_found = false;
$insert = false;
$results = [];
$errors = []; // Array to hold validation errors

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- SERVER-SIDE VALIDATION ---
    // Only validate the main form, not the claim action
    if (!isset($_POST['action'])) {
        // a. Validate Contact Number
        if (isset($_POST['contact'])) {
            if (!is_numeric($_POST['contact']) || strlen($_POST['contact']) != 10) {
                $errors[] = "Contact number must be exactly 10 digits.";
            }
        }
        // b. Validate Photo Upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $check = getimagesize($_FILES["photo"]["tmp_name"]);
            if ($check === false) {
                $errors[] = "The uploaded file is not a valid image.";
            }
            if ($_FILES["photo"]["size"] > 5000000) { // 5MB limit
                $errors[] = "Image file is too large. Maximum size is 5MB.";
            }
        } else {
            $errors[] = "An item photo is required.";
        }
    }


    // --- PROCESS FORM ONLY IF VALIDATION PASSES ---
    if (empty($errors)) {

        // Handle a "Claim Now" Action
        if (isset($_POST['action']) && $_POST['action'] === 'claim') {
            $found_item_id = intval($_POST['found_item_id']);

            $claimer_name = mysqli_real_escape_string($con, $_POST['claimer_name']);
            $claimer_contact = mysqli_real_escape_string($con, $_POST['claimer_contact']);
            $claimer_email = mysqli_real_escape_string($con, $_POST['claimer_email']);

            $getItem = "SELECT * FROM report_found WHERE id = $found_item_id";
            $result = $con->query($getItem);
            if ($result && $result->num_rows > 0) {
                $found_item = $result->fetch_assoc();

                $insert_claimed_sql = "INSERT INTO claimed_items (item_name, description, location, finder_name, finder_contact, finder_email, claimer_name, claimer_contact, claimer_email, photo)
                                       VALUES ('{$found_item['item_name']}', '{$found_item['description']}', '{$found_item['location']}', '{$found_item['name']}', '{$found_item['contact']}', '{$found_item['email']}', '$claimer_name', '$claimer_contact', '$claimer_email', '{$found_item['photo']}')";
                
                if ($con->query($insert_claimed_sql)) {
                    $con->query("DELETE FROM report_found WHERE id = $found_item_id");
                    $con->query("DELETE FROM report_lost WHERE LOWER(item_name) LIKE LOWER('%{$found_item['item_name']}%') AND LOWER(location) LIKE LOWER('%{$found_item['location']}%')");
                    $claim_successful = true;
                    $finder_details = $found_item;
                }
            }
        // Handle an initial "Report Lost" search
        } elseif (isset($_POST['item_name'])) {
            $name = mysqli_real_escape_string($con, $_POST['name']);
            $item_name = mysqli_real_escape_string($con, $_POST['item_name']);
            $description = mysqli_real_escape_string($con, $_POST['description']);
            $location = mysqli_real_escape_string($con, $_POST['location']);
            $contact = mysqli_real_escape_string($con, $_POST['contact']);
            $email = mysqli_real_escape_string($con, $_POST['email']);
            $date = mysqli_real_escape_string($con, $_POST['date']);

            $target_dir = "uploads/";
            $filename = time() . "_" . basename($_FILES["photo"]["name"]);
            $photo = $target_dir . $filename;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo);

            // Stricter search query using AND
            $search_sql = "SELECT * FROM report_found WHERE LOWER(item_name) LIKE LOWER('%$item_name%') AND LOWER(location) LIKE LOWER('%$location%')";
            $result = $con->query($search_sql);

            if ($result && $result->num_rows > 0) {
                $match_found = true;
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }
            } else {
                $sql = "INSERT INTO report_lost (name, item_name, description, location, contact, email, date, photo)
                        VALUES ('$name', '$item_name', '$description', '$location', '$contact', '$email', '$date', '$photo')";
                if ($con->query($sql) === TRUE) {
                    $insert = true;
                } else {
                    echo "Error: " . $con->error;
                }
            }
        }
    }
}

// Close the connection only if it was established
if (isset($con)) {
    $con->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Lost</title>
    <link rel="stylesheet" href="/lostmate/report.css">
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
        
        <?php
        // Display validation errors if any
        if (!empty($errors)) {
            echo '<div class="error-box">';
            echo '<h4>Please fix the following errors:</h4>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . $error . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        ?>

        <?php if (!$claim_successful): ?>
        <div class="form-wrapper">
            <h1>REPORT LOST ITEMS</h1>
            <form action="lost.php" method="post" enctype="multipart/form-data" id="lost-form">       
                <div class="forms">
                    <a>Name:</a><br>
                    <input type="text" name="name" id="name" placeholder="Enter your name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"><br>
                    <a>Item Name:</a><br>
                    <input type="text" name="item_name" id="item_name" placeholder="What is the item" required value="<?php echo isset($_POST['item_name']) ? htmlspecialchars($_POST['item_name']) : ''; ?>"><br>
                    <a>Description:</a><br>
                    <input type="text" name="description" id="description" placeholder="Details of item" required value="<?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?>"><br>
                    <a>Location:</a><br>
                    <input type="text" name="location" id="location" placeholder="Where the item is lost" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"><br>
                    <a>Contact:</a><br>
                    <input type="text" name="contact" id="contact" placeholder="Enter your Contact number" required value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>"><br>
                    <a>E-mail Address:</a><br>
                    <input type="email" name="email" id="email" placeholder="Enter your email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"><br>
                    <a>Date:</a><br>
                    <input type="date" name="date" id="date" required value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>"><br>
                    <a>Upload Item Photo:</a><br>
                    <input type="file" name="photo" id="photo" accept="image/*" required><br><br>

                    <button type="submit" class="btn">SUBMIT</button>
                    <button type="reset" class="btn">RESET</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="results-wrapper">
            <?php
            // --- DISPLAY LOGIC ---
            
            if ($claim_successful) {
                echo "<div class='card' style='margin: 40px; text-align: center;'>";
                echo "<h2>Claim Successful! ✅</h2>";
                echo "<p>Your item has been moved to the Claimed Items history.</p>";
                echo "<p>Please contact the finder directly to arrange collection:</p><br>";
                echo "<p><strong>Finder's Name:</strong> " . htmlspecialchars($finder_details['name']) . "</p>";
                echo "<p><strong>Finder's Contact:</strong> " . htmlspecialchars($finder_details['contact']) . "</p>";
                echo "<p><strong>Finder's Email:</strong> " . htmlspecialchars($finder_details['email']) . "</p>";
                echo "</div>";
            }

            if ($match_found) {
                echo "<h1 style='text-align: center;'>We found items that match your description!</h1>";
                foreach ($results as $row) {
                    echo "<div class='card'>";
                    if (!empty($row['photo']) && file_exists($row['photo'])) {
                        echo "<img src='/lostmate/" . htmlspecialchars($row['photo']) . "' alt='Item Photo' style='max-width: 200px; height: auto; border-radius: 8px;'><br>";
                    }
                    echo "<h3>" . htmlspecialchars($row['item_name']) . "</h3>";
                    echo "<p><strong>Description:</strong> " . htmlspecialchars($row['description']) . "</p>";
                    echo "<p><strong>Location Found:</strong> " . htmlspecialchars($row['location']) . "</p>";
                    
                    echo '<form action="lost.php" method="post" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="claim">
                            <input type="hidden" name="found_item_id" value="' . $row['id'] . '">
                            <input type="hidden" name="claimer_name" value="' . htmlspecialchars($_POST['name']) . '">
                            <input type="hidden" name="claimer_contact" value="' . htmlspecialchars($_POST['contact']) . '">
                            <input type="hidden" name="claimer_email" value="' . htmlspecialchars($_POST['email']) . '">
                            <button type="submit" class="btn">Claim Now</button>
                          </form>';
                    echo "</div>";
                }
            }

            if ($insert == true) {
                echo "<div style='text-align:center; margin-top: 30px;'>";
                echo "<h1>THANKS FOR REPORTING A LOST ITEM</h1>";
                echo "<h2>No matching items found at this time. We will notify you by email if it's found!</h2>";
                echo "</div>";
            }
            ?>
        </div>
    </main>

    <script>
    // Client-side validation
    document.getElementById('lost-form').addEventListener('submit', function(event) {
        let errors = [];
        
        const contactInput = document.getElementById('contact');
        const contactRegex = /^\d{10}$/;
        if (!contactRegex.test(contactInput.value)) {
            errors.push('Contact number must be exactly 10 digits.');
        }

        const photoInput = document.getElementById('photo');
        if (photoInput.files.length === 0) {
            errors.push('Please select an item photo to upload.');
        }

        if (errors.length > 0) {
            event.preventDefault();
            alert('Please fix the following issues:\n\n- ' + errors.join('\n- '));
        }
    });
    </script>
</body>
</html>