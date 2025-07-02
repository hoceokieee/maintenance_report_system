<?php
session_start();
require_once "config/database.php";
include "includes/staff_header.php";

// Check if staff is logged in
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

$search = $_GET['search'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

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

-- Join latest status from status_log
LEFT JOIN (
    SELECT sl1.*
    FROM status_log sl1
    INNER JOIN (
        SELECT Report_ID, MAX(Status_ID) AS max_id
        FROM status_log
        GROUP BY Report_ID
    ) sl2 ON sl1.Report_ID = sl2.Report_ID AND sl1.Status_ID = sl2.max_id
) sl ON r.Report_ID = sl.Report_ID

-- Join urgency label
LEFT JOIN urgency_level u ON r.Urgency_ID = u.Urgency_ID

-- Join first image per report
LEFT JOIN (
    SELECT Report_ID, MIN(Media_ID) AS first_media_id
    FROM media
    GROUP BY Report_ID
) first_media ON r.Report_ID = first_media.Report_ID
LEFT JOIN media m ON m.Media_ID = first_media.first_media_id

WHERE 1 = 1
";

if (!empty($search)) {
    $safeSearch = $conn->real_escape_string($search);
    $sql .= " AND (r.title LIKE '%$safeSearch%' OR r.location LIKE '%$safeSearch%')";
}

if (!empty($from_date)) {
    $safeFrom = $conn->real_escape_string($from_date);
    $sql .= " AND r.report_date >= '$safeFrom'";
}

if (!empty($to_date)) {
    $safeTo = $conn->real_escape_string($to_date);
    $sql .= " AND r.report_date <= '$safeTo'";
}

$sql .= " ORDER BY r.report_date DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Report Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.querySelector("input[name='search']");
    const form = searchInput.closest("form");

    let timer;
    searchInput.addEventListener("input", function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            form.submit(); // auto-submit after user stops typing
        }, 500); // 0.5s delay
    });
});
</script>

<style>
    .filter-form {
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        background-color: #fff;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .filter-form input[type="text"],
    .filter-form input[type="date"] {
        padding: 8px 10px;
        border-radius: 6px;
        border: 1px solid #ccc;
        flex: 1;
        min-width: 180px;
    }

    .filter-form button {
        background-color: #007bff;
        border: none;
        color: white;
        padding: 8px 14px;
        border-radius: 6px;
        cursor: pointer;
    }

    .filter-form a {
        padding: 8px 14px;
        background-color: #dc3545;
        color: white;
        text-decoration: none;
        border-radius: 6px;
    }

    .filter-form label {
        font-size: 14px;
        font-weight: 500;
    }


        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .main-wrapper {
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .main-content {
            max-width: 1200px;
            width: 100%;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 16px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #a3d4f7;
        }

        select, button {
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        form.inline-form {
            display: flex;
            flex-wrap: nowrap;
            gap: 5px;
            align-items: center;
        }

        @media (max-width: 768px) {
            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }
        }

        
    </style>
</head>
<body>

<?php if (isset($_SESSION['status_success'])): ?>
    <script>alert("<?= $_SESSION['status_success'] ?>");</script>
    <?php unset($_SESSION['status_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['status_error'])): ?>
    <script>alert("<?= $_SESSION['status_error'] ?>");</script>
    <?php unset($_SESSION['status_error']); ?>
<?php endif; ?>

<div class="main-wrapper">
    <div class="main-content">
        <h2>Report Management</h2>

        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search by title or location" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            
            <label>From:
                <input type="date" name="from_date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
            </label>

            <label>To:
                <input type="date" name="to_date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
            </label>

            <button type="submit">Filter</button>
            <a href="report_management.php" style="margin-left: 10px; text-decoration: none;">Reset</a>
        </form>


        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Location</th>
                        <th>Image</th>
                        <th>Report Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>View</th>
                        <th>Change Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Report_ID']) ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= htmlspecialchars($row['location']) ?></td>
                        <td>
                            <?php if (!empty($row['media_path'])): ?>
                                <img src="assets/uploads/reports/<?= htmlspecialchars($row['media_path']) ?>" alt="Report Image" style="width: 100px; height: auto;">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['report_date']) ?></td>
                        <td><?= htmlspecialchars($row['urgency_label'] ?? 'Not Set') ?></td>
                        <td><?= htmlspecialchars($row['latest_status'] ?? 'Not Updated') ?></td>
                        <td>
                            <a href="staff_view_report.php?id=<?= $row['Report_ID'] ?>" 
                            style="display: inline-block; padding: 6px 12px; background-color: #007bff; color: white; border-radius: 4px; text-decoration: none;">
                            View
                            </a>
                        </td>
                        <td>
                            <?php
                            $currentStatus = $row['latest_status'] ?? '';
                            $reportId = htmlspecialchars($row['Report_ID']);
                            $isCompleted = $currentStatus === 'Completed';
                            $isInProgress = $currentStatus === 'In Progress';
                            $isCancelled = $currentStatus === 'Cancel';
                            ?>

                            <?php if ($isCompleted): ?>
                                <form class="inline-form">
                                    <select disabled style="background-color: #eee;">
                                        <option selected>Completed</option>
                                    </select>
                                    <button disabled>Update</button>
                                </form>

                            <?php else: ?>
                                <form class="inline-form" method="POST" action="update_report.php">
                                    <input type="hidden" name="Report_ID" value="<?= $reportId ?>">
                                    <select name="status" <?= $isCompleted ? 'disabled style="background-color: #eee;"' : '' ?>>
                                        <option value="Pending" <?= $currentStatus === 'Pending' ? 'selected' : '' ?> <?= $isInProgress ? 'disabled' : '' ?>>Pending</option>
                                        <option value="In Progress" <?= $currentStatus === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                        <option value="Completed" <?= $currentStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <?php if ($isInProgress): ?>
                                            <option value="Cancel">Cancel</option>
                                        <?php endif; ?>
                                        <?php if ($isCancelled): ?>
                                            <option value="Pending">Pending</option>
                                        <?php endif; ?>
                                    </select>
                                    <button type="submit" <?= $isCompleted ? 'disabled' : '' ?>>Update</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
