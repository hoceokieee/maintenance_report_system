<?php
session_start();
require_once "config/database.php";

// Ensure staff is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form values
    $report_id = $_POST['Report_ID'] ?? null;
    $status = $_POST['status'] ?? null;
    $staff_id = $_SESSION['id'];

    // Validate input
    if (empty($report_id) || empty($status)) {
        header("Location: report_management.php?error=missing_data");
        exit();
    }

    // Insert into status_log
    $sql = "INSERT INTO status_log (Report_ID, updated_by, status, updated_time)
            VALUES (?, ?, NOW(), ?)";

    $stmt = $conn->prepare("INSERT INTO status_log (Report_ID, updated_by, status, updated_time)
                            VALUES (?, ?, ?, NOW())");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Use 's' for string Report_ID
    $stmt->bind_param("sis", $report_id, $staff_id, $status);

    if ($stmt->execute()) {
        header("Location: report_management.php?status_updated=1");
    } else {
        header("Location: report_management.php?error=insert_failed");
    }

    $stmt->close();
    $conn->close();
    exit();
} else {
    header("Location: report_management.php");
    exit();
}
