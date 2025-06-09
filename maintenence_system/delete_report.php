<?php
session_start();
require_once "config/database.php";

// Check if user is logged in and has permission
if (!isset($_SESSION["id"]) || !isset($_POST["report_id"])) {
    header("Location: view_reports.php");
    exit();
}

$report_id = $_POST["report_id"];

// Get report details to check permissions
$sql = "SELECT User_ID FROM REPORT WHERE Report_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

// Check if user has permission to delete
if (!$report || ($_SESSION["role"] != "Admin" && $_SESSION["role"] != "Manager" && $_SESSION["id"] != $report["User_ID"])) {
    $_SESSION["error_msg"] = "You don't have permission to delete this report.";
    header("Location: view_reports.php");
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Delete related records first
    // Delete comments
    $sql = "DELETE FROM COMMENTS WHERE Report_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    // Delete status logs
    $sql = "DELETE FROM STATUS_LOG WHERE Report_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    // Delete media files
    $sql = "SELECT file_path FROM MEDIA WHERE Report_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($media = $result->fetch_assoc()) {
        if (file_exists($media['file_path'])) {
            unlink($media['file_path']);
        }
    }

    // Delete media records
    $sql = "DELETE FROM MEDIA WHERE Report_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    // Finally, delete the report
    $sql = "DELETE FROM REPORT WHERE Report_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    
    $_SESSION["success_msg"] = "Report deleted successfully.";
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $_SESSION["error_msg"] = "Error deleting report: " . $e->getMessage();
}

header("Location: view_reports.php");
exit();
?> 