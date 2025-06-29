<?php
session_start();
// Optional: check if user is logged in and authorized to upload

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'db_connection.php';

    $targetDir = "kit_images/";
    $fileName = basename($_FILES["product_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Check if the file is an image by checking its MIME type
    $check = getimagesize($_FILES["product_image"]["tmp_name"]);
    if ($check !== false) {
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetFilePath)) {
            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $image_url = $fileName;

            $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssds", $name, $description, $price, $image_url);

            if ($stmt->execute()) {
                echo "<p class='success'>Product and image uploaded successfully.</p>";
            } else {
                echo "<p class='error'>DB error: " . $stmt->error . "</p>";
            }
        } else {
            echo "<p class='error'>Sorry, there was an error uploading your file.</p>";
        }
    } else {
        echo "<p class='error'>File is not an image.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Product Image</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        h2 {
            color: #2c3e50;
            text-align: center;
        }
        form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        input[type="text"],
        input[type="number"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        input[type="submit"] {
            background: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background: #2980b9;
        }
        .success {
            color: #27ae60;
            background: #e8f8f0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            color: #e74c3c;
            background: #fde8e8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h2>Upload Product Image</h2>
    
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <!-- Messages will appear here from PHP -->
    <?php endif; ?>
    
    <form action="upload_image.php" method="post" enctype="multipart/form-data">
        <label>Product Name:</label>
        <input type="text" name="name" required>
        
        <label>Description:</label>
        <textarea name="description" required></textarea>
        
        <label>Price:</label>
        <input type="number" step="0.01" name="price" required>
        
        <label>Select Image:</label>
        <input type="file" name="product_image" accept="image/*" required>
        
        <input type="submit" value="Upload">
    </form>
</body>
</html>