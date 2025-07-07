<?php
session_start();
require_once "config/database.php";

// Redirect if not staff
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $targetDir = "assets/uploads/users/";
    $fileName = basename($_FILES["profile_picture"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE User_ID = ?");
        $stmt->bind_param("ss", $fileName, $user_id);
        $stmt->execute();
    }
}

// Get user data
$stmt = $conn->prepare("SELECT name, email, role, profile_picture FROM users WHERE User_ID = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $role, $profile_picture);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Staff Profile</title>
    <style>
        body {
            font-family: Arial;
            background: #f0f0f0;
            padding: 40px;
        }

        .profile-container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .profile-picture {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-picture img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }

        table {
            width: 100%;
        }

        td {
            padding: 10px;
        }

        input[type="file"] {
            margin-top: 10px;
        }

        button {
            margin-top: 10px;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
        }

    </style>
</head>
<body>
    <div class="profile-container">
        <h2 style="text-align:center;">My Profile</h2>

        <div class="profile-picture">
            <img src="assets/uploads/users/<?= htmlspecialchars($profile_picture ?: 'default.jpg') ?>" alt="Profile Picture">
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_picture" accept="image/*" required>
                <br>
                <button type="submit">Upload New Picture</button>
            </form>
        </div>

        <table>
            <tr><td><strong>Name:</strong></td><td><?= htmlspecialchars($name) ?></td></tr>
            <tr><td><strong>Email:</strong></td><td><?= htmlspecialchars($email) ?></td></tr>
            <tr><td><strong>Role:</strong></td><td><?= htmlspecialchars($role) ?></td></tr>
        </table>
    </div>
</body>
</html>
