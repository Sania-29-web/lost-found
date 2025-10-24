<?php
$insert = false;
$errors = []; // Array for validation errors

if (isset($_POST['name'])) {
    $server = "localhost";
    $username = "root";
    $password = "";
    $database = "lostmate";
    $con = mysqli_connect($server, $username, $password, $database);

    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // --- SERVER-SIDE VALIDATION ---
    if (isset($_POST['contact'])) {
        if (!is_numeric($_POST['contact']) || strlen($_POST['contact']) != 10) {
            $errors[] = "Contact number must be exactly 10 digits.";
        }
    }
    if (isset($_FILES['item_photo']) && $_FILES['item_photo']['error'] === 0) {
        $check = getimagesize($_FILES["item_photo"]["tmp_name"]);
        if ($check === false) {
            $errors[] = "The uploaded file is not a valid image.";
        }
        if ($_FILES["item_photo"]["size"] > 5000000) { // 5MB limit
            $errors[] = "Image file is too large. Maximum size is 5MB.";
        }
    } else {
        $errors[] = "An item photo is required.";
    }

    // --- PROCESS ONLY IF VALIDATION PASSES ---
    if (empty($errors)) {
        $finder_name = mysqli_real_escape_string($con, $_POST['name']);
        $item_name = mysqli_real_escape_string($con, $_POST['item_name']);
        $description = mysqli_real_escape_string($con, $_POST['description']);
        $location = mysqli_real_escape_string($con, $_POST['location']);
        $finder_contact = mysqli_real_escape_string($con, $_POST['contact']);
        $finder_email = mysqli_real_escape_string($con, $_POST['email']);
        $date = mysqli_real_escape_string($con, $_POST['date']); // This is the 'found_date'

        $target_dir = "uploads/";
        $filename = time() . "_" . basename($_FILES["item_photo"]["name"]);
        $photo_path = $target_dir . $filename;
        move_uploaded_file($_FILES["item_photo"]["tmp_name"], $photo_path);
        
        // Final, stricter search query with date check
        $search_sql = "SELECT * FROM report_lost 
                       WHERE LOWER(item_name) LIKE LOWER('%$item_name%') 
                       AND LOWER(location) LIKE LOWER('%$location%') 
                       AND date <= '$date'";
        $result = $con->query($search_sql);

        if ($result && $result->num_rows > 0) {
            // MATCH FOUND! Automatically move to claimed and notify.
            while ($lost_row = $result->fetch_assoc()) {
                $owner_email = $lost_row['email'];
                $owner_name = $lost_row['name'];
                $owner_contact = $lost_row['contact'];

                // Send email with finder's details
                $to = $owner_email;
                $subject = "Good News! An item matching your description was found! - LostMate";
                $message = "<html><body>"; // ... (your full email message body) ... </body></html>";

                // --- THIS IS THE CRITICAL FIX ---
                // The 'From' address MUST match the one in sendmail.ini
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                $headers .= "From: <saniashaikh54256@gmail.com>\r\n"; // CHANGE THIS TO YOUR GMAIL

                if (mail($to, $subject, $message, $headers)) {
                    $insert_claimed_sql = "INSERT INTO claimed_items (item_name, description, location, finder_name, finder_contact, finder_email, claimer_name, claimer_contact, claimer_email, photo)
                                           VALUES ('$item_name', '$description', '$location', '$finder_name', '$finder_contact', '$finder_email', '$owner_name', '$owner_contact', '$owner_email', '$photo_path')";
                    $con->query($insert_claimed_sql);
                    $con->query("DELETE FROM report_lost WHERE id = {$lost_row['id']}");
                    $insert = true;
                } else {
                    // ADDED ERROR MESSAGE: This will tell you if the email fails
                    echo "ERROR: A match was found and database was NOT updated because the notification email failed to send. Please check your XAMPP email configuration.";
                }
            }
        } else {
            // NO MATCH FOUND - Save the found item to the database as normal
            $sql = "INSERT INTO report_found (name, item_name, description, location, contact, email, date, photo)
                    VALUES ('$finder_name', '$item_name', '$description', '$location', '$finder_contact', '$finder_email', '$date', '$photo_path')";
            if ($con->query($sql) === TRUE) {
                $insert = true;
            } else {
                echo "Error inserting data: " . $con->error;
            }
        }
    }
    
    $con->close(); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Found</title>
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
        <div class="form-wrapper">
            <h1>REPORT FOUND ITEMS</h1>
            <form action="found.php" method="post" enctype="multipart/form-data" id="found-form">
                <div class="forms">
                    <a>Name:</a><br>
                    <input type="text" name="name" placeholder="Enter your name" required><br>
                    <a>Item Name:</a><br>
                    <input type="text" name="item_name" placeholder="What is the item" required><br>
                    <a>Description:</a><br>
                    <input type="text" name="description" placeholder="Details of item" required><br>
                    <a>Location:</a><br>
                    <input type="text" name="location" placeholder="Where the item was found" required><br>
                    <a>Contact:</a><br>
                    <input type="text" name="contact" id="contact" placeholder="Enter your contact number" required><br>
                    <a>E-mail Address:</a><br>
                    <input type="email" name="email" placeholder="Enter your email address" required><br>
                    <a>Date:</a><br>
                    <input type="date" name="date" required><br>
                    <a>Upload Item Photo:</a><br>
                    <input type="file" name="item_photo" id="photo" accept="image/*" required><br><br>
                    <button type="submit" class="btn">SUBMIT</button>
                    <button type="reset" class="btn">RESET</button>
                </div>
            </form>
        </div>
        
        <div class="results-wrapper">
            <?php
            if($insert == true){
                echo "<div style='text-align:center; margin-top: 30px;'>";
                echo "<h1>THANKS FOR REPORTING A FOUND ITEM</h1>";
                echo "<h2>If a match was found, the owner has been notified.</h2>";
                echo "</div>";
            }
            ?>
        </div>
    </main>
    <script>
    // Client-side validation
    document.getElementById('found-form').addEventListener('submit', function(event) {
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