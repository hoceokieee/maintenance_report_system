<?php
session_start();
require_once "config/database.php";
include "includes/staff_header.php";

// Check if staff is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

// Get Report ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid report ID.";
    exit();
}

// $reportId = intval($_GET['id']);
$reportId = $_GET['id'];

// Fetch single report details
$sql = "
SELECT 
    r.Report_ID,
    r.title,
    r.description,
    r.location,
    r.report_date,
    u.label AS urgency_label,
    sl.status AS latest_status,
    m.file_path AS media_path
FROM report r
LEFT JOIN (
    SELECT sl1.*
    FROM status_log sl1
    INNER JOIN (
        SELECT Report_ID, MAX(Status_ID) AS max_id
        FROM status_log
        GROUP BY Report_ID
    ) sl2 ON sl1.Report_ID = sl2.Report_ID AND sl1.Status_ID = sl2.max_id
) sl ON r.Report_ID = sl.Report_ID
LEFT JOIN urgency_level u ON r.Urgency_ID = u.Urgency_ID
LEFT JOIN (
    SELECT Report_ID, MIN(Media_ID) AS first_media_id
    FROM media
    GROUP BY Report_ID
) first_media ON r.Report_ID = first_media.Report_ID
LEFT JOIN media m ON m.Media_ID = first_media.first_media_id
WHERE r.Report_ID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $reportId);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    echo "Report not found.";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background-color: #f8f9fa;
        }

        .report-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 12px;
            vertical-align: top;
            border-bottom: 1px solid #ddd;
        }

        td.label {
            font-weight: bold;
            width: 25%;
            background-color: #f0f0f0;
        }

        .report-image {
            text-align: center;
            margin-top: 20px;
        }

        .report-image img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.1);
        }

        .back-link {
            display: block;
            margin-top: 30px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            color: #007bff;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="report-container">
    <h2>Report Details</h2>
    <table>
        <tr>
            <td class="label">Title</td>
            <td><?= htmlspecialchars($report['title']) ?></td>
        </tr>
        <tr>
            <td class="label">Description</td>
            <td><?= nl2br(htmlspecialchars($report['description'])) ?></td>
        </tr>
        <tr>
            <td class="label">Location</td>
            <td><?= htmlspecialchars($report['location']) ?></td>
        </tr>
        <tr>
            <td class="label">Report Date</td>
            <td><?= htmlspecialchars($report['report_date']) ?></td>
        </tr>
        <tr>
            <td class="label">Urgency</td>
            <td><?= htmlspecialchars($report['urgency_label'] ?? 'Not Set') ?></td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td><?= htmlspecialchars($report['latest_status'] ?? 'Not Updated') ?></td>
        </tr>
        <tr>
            <td class="label">Image</td>
            <td>
                <?php if (!empty($report['media_path'])): ?>
                    <div class="report-image">
                        <img src="assets/uploads/reports/<?= htmlspecialchars($report['media_path']) ?>" alt="Report Image">
                    </div>
                <?php else: ?>
                    No image uploaded.
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <a class="back-link" href="report_management.php">&larr; Back to Reports</a>
</div>

</body>
</html>
