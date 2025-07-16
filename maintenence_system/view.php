<?php
require_once "config/database.php";
require_once "includes/session.php";
include "includes/header.php";

// Check login
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Check for Report_ID in URL
if (!isset($_GET['report_id'])) {
    echo "Report ID not provided.";
    exit();
}
$report_id = $_GET['report_id'];

// Prepare query
$query = "SELECT 
    r.Title,
    r.Description,
    r.Location,
    r.report_date,
    (
        SELECT m.file_path 
        FROM MEDIA m 
        WHERE m.Report_ID = r.Report_ID 
        LIMIT 1
    ) AS media_path,
    ul.label AS urgency_label,
    u.name AS reporter_name,
    c.name AS category_name,
    (
        SELECT sl.status 
        FROM STATUS_LOG sl 
        WHERE sl.Report_ID = r.Report_ID 
        ORDER BY sl.Status_ID DESC 
        LIMIT 1
    ) AS current_status
FROM REPORT r
LEFT JOIN URGENCY_LEVEL ul ON r.Urgency_ID = ul.Urgency_ID
LEFT JOIN USERS u ON r.User_ID = u.User_ID
LEFT JOIN CATEGORY c ON r.Category_ID = c.Category_ID
WHERE r.Report_ID = ?
LIMIT 1;";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Report</title>
    <!-- Include your styles here -->
    <style>
    :root {
      --primary-bg: #bbdefb;
      --sidebar-width: 250px;
      --sidebar-collapsed-width: 70px;
      --transition-speed: 0.5s;
      --transition-curve: cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
      margin: 0;
      background: #f8f9fa;
      font-family: 'DM Sans', sans-serif;
    }

    .main-content {
      transition: all var(--transition-speed) var(--transition-curve);
    }

    .container-fluid {
      padding: 25px;
      margin-left: var(--sidebar-width);
      transition: all var(--transition-speed) var(--transition-curve);
    }

    .main-content.expanded .container-fluid {
      margin-left: var(--sidebar-collapsed-width);
    }

    .page-header {
      background: var(--primary-bg);
      padding: 20px;
      margin-bottom: 25px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: margin-left var(--transition-speed) var(--transition-curve);
    }

    .page-header h1 {
      margin: 0;
      font-size: 24px;
      color: #1a237e;
    }

    .reports-container {
      background: white;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: all var(--transition-speed) var(--transition-curve);
    }

    .reports-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin-top: 20px;
    }

    .reports-table th {
      background: var(--primary-bg);
      padding: 12px 15px;
      text-align: left;
      font-weight: 500;
      color: #1a237e;
      border: none;
      width: 180px;
    }

    .reports-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #e3f2fd;
      color: #333;
      font-size: 14px;
    }

    .btn-action {
      padding: 10px 16px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      color: white;
      background: #1976d2;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      margin-top: 30px;
      transition: background 0.3s ease;
    }

    .btn-action:hover {
      background: #1565c0;
    }
  </style>
</head>
<body>
    <div class="main-content">
    <div class="container-fluid py-4">
        <div class="page-header">
            <h1>Report Details: <?= htmlspecialchars($report_id) ?></h1>
        </div>

        <div class="reports-container">
            <table class="reports-table">
                <tr><th>Title</th><td><?= htmlspecialchars($report['Title']) ?></td></tr>
                <tr><th>Reported by</th><td><?= htmlspecialchars($report['reporter_name']) ?></td></tr>
                <tr><th>Date</th><td><?= htmlspecialchars($report['report_date']) ?></td></tr>
                <tr><th>Location</th><td><?= htmlspecialchars($report['Location']) ?></td></tr>
                <tr><th>Category</th><td><?= htmlspecialchars($report['category_name']) ?></td></tr>
                <tr><th>Urgency</th><td><?= htmlspecialchars($report['urgency_label']) ?></td></tr>
                <tr><th>Status</th><td><?= htmlspecialchars($report['current_status']) ?></td></tr>
                <tr><th>Description</th><td><?= nl2br(htmlspecialchars($report['Description'])) ?></td></tr>
                <?php if (!empty($report['media_path'])): ?>
                    <tr>
                        <th>Media</th>
                        <td><img src="<?= htmlspecialchars($report['media_path']) ?>" alt="Report Media" style="max-width: 100%;"></td>
                    </tr>
                <?php endif; ?>
            </table>
            
            <a href="view_reports.php" class="btn-action btn-view">
                ← Back to Reports
            </a>
        </div>
    </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        const observer = new MutationObserver(function () {
            if (sidebar.classList.contains('collapsed')) {
                mainContent.classList.add('expanded');
            } else {
                mainContent.classList.remove('expanded');
            }
        });

        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

        // On initial load
        if (sidebar.classList.contains('collapsed')) {
            mainContent.classList.add('expanded');
        }
    });
    </script>
</body>
</html>

<?php include "includes/footer.php"; ?>