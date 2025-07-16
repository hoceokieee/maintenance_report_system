<?php
session_start();
require_once "config/database.php";
include "includes/staff_header.php";

// Check if staff is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Invalid report ID.";
    exit();
}

$reportId = $_GET['id'];

// Fetch report details
$sql = "
SELECT 
    r.Report_ID, r.title, r.description, r.location, r.report_date,
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

// Fetch all evidence media
$evidenceQuery = $conn->prepare("SELECT file_path FROM media WHERE Report_ID = ?");
$evidenceQuery->bind_param("s", $reportId);
$evidenceQuery->execute();
$evidenceResult = $evidenceQuery->get_result();

$evidences = [];
while ($row = $evidenceResult->fetch_assoc()) {
    $evidences[] = $row['file_path'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Report</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 40px;
            background-color: #f0f2f5;
        }

        .report-container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        h2, h3 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        td {
            padding: 12px 14px;
            vertical-align: top;
        }

        td.label {
            width: 25%;
            background-color: #f9f9f9;
            font-weight: bold;
            color: #444;
        }

        .main-image {
            text-align: center;
            margin-top: 10px;
        }

        .main-image img {
            max-width: 50%;
            height: auto;
            border-radius: 6px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.08);
        }

        .evidence-section {
            margin-top: 40px;
        }

        .evidence-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: flex-start;
        }

        .evidence-gallery img {
            width: 400px;
            height: auto;
            object-fit: cover;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #fafafa;
            padding: 4px;
            transition: transform 0.3s ease;
        }

        .evidence-gallery img:hover {
            transform: scale(2.0);
        }

        .evidence-gallery video,
        .evidence-gallery audio {
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #fafafa;
            padding: 4px;
        }

        .evidence-gallery img {
            width: 100px;
            height: auto;
        }

        .evidence-gallery video {
            width: 200px;
            height: 140px;
        }

        .evidence-gallery audio {
            width: 200px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 40px;
            text-decoration: none;
            font-weight: bold;
            color: #007bff;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .no-evidence {
            color: #888;
            text-align: center;
            margin-top: 10px;
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
            <td class="label">Main Image</td>
            <td>
                <?php if (!empty($report['media_path'])): ?>
                    <div class="main-image">
                        <img src="assets/uploads/reports/<?= htmlspecialchars($report['media_path']) ?>" alt="Main Report Image">
                    </div>
                <?php else: ?>
                    <div class="no-evidence">No main image uploaded.</div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="evidence-section">
        <h3>All Evidence Media</h3>
        <?php if (!empty($evidences)): ?>
            <div class="evidence-gallery">
                <?php foreach ($evidences as $evi): ?>
                    <?php
                        $ext = strtolower(pathinfo($evi, PATHINFO_EXTENSION));
                        $safePath = htmlspecialchars($evi);
                    ?>
                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <img src="<?= $safePath ?>" alt="">
                    <?php elseif ($ext === 'mp4'): ?>
                        <video controls>
                            <source src="<?= $safePath ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php elseif (in_array($ext, ['mp3', 'wav', 'ogg'])): ?>
                        <audio controls>
                            <source src="<?= $safePath ?>" type="audio/<?= $ext ?>">
                            Your browser does not support the audio element.
                        </audio>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-evidence">No evidence media available.</div>
        <?php endif; ?>
    </div>

    <a class="back-link" href="report_management.php">&larr; Back to Reports</a>
</div>

</body>
</html>
