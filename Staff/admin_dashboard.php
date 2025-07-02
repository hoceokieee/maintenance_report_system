<?php
session_start();
require_once "config/database.php";
include "includes/header.php";

// Protect: only Admins
if (!isset($_SESSION['id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch stats
$total_users = $conn->query("SELECT COUNT(*) AS total FROM USERS")->fetch_assoc()['total'];
$total_reports = $conn->query("SELECT COUNT(*) AS total FROM REPORT")->fetch_assoc()['total'];
$total_staff = $conn->query("SELECT COUNT(*) AS total FROM USERS WHERE LOWER(role) = 'staff'")->fetch_assoc()['total'];
$status_counts = $conn->query("
    SELECT
      SUM(CASE WHEN sl.status = 'Pending' THEN 1 ELSE 0 END) AS pending,
      SUM(CASE WHEN sl.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
      SUM(CASE WHEN sl.status = 'Completed' THEN 1 ELSE 0 END) AS completed
    FROM STATUS_LOG sl
    WHERE sl.Status_ID IN (
      SELECT MAX(Status_ID) FROM STATUS_LOG GROUP BY Report_ID
    )
")->fetch_assoc();

// Fetch recent reports
$recent_reports = $conn->query("
  SELECT r.Report_ID, r.Title, u.name AS reporter, r.report_date
  FROM REPORT r
  JOIN USERS u ON r.User_ID = u.User_ID
  ORDER BY r.report_date DESC
  LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard</title>
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding-top: 60px; }
    .card-stats .card-body { padding: 1rem; text-align: center; }
    .status-badge { padding: 0.4em 0.8em; border-radius: 12px; font-size: 0.9em; }
    .status-pending { background: #ffebee; color: #c62828; }
    .status-in-progress { background: #e3f2fd; color: #1565c0; }
    .status-completed { background: #e8f5e9; color: #2e7d32; }
  </style>
</head>
<body>
<div class="container">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Admin Dashboard</h1>
    <span class="badge bg-secondary">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
  </div>

  <!-- Stats -->
  <div class="row card-stats mb-4">
    <div class="col-md-3 mb-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <h5>Total Users</h5>
          <p class="display-6"><?= $total_users ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <h5>Total Reports</h5>
          <p class="display-6"><?= $total_reports ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card bg-info text-white">
        <div class="card-body">
          <h5>Total Staff</h5>
          <p class="display-6"><?= $total_staff ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Status Overview -->
  <h4>Status Overview</h4>
  <div class="row mb-4">
    <div class="col-md-4 mb-3">
      <div class="card border-warning">
        <div class="card-body">
          <h6>Pending</h6>
          <p class="fs-3"><?= $status_counts['pending'] ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card border-primary">
        <div class="card-body">
          <h6>In Progress</h6>
          <p class="fs-3"><?= $status_counts['in_progress'] ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-3">
      <div class="card border-success">
        <div class="card-body">
          <h6>Completed</h6>
          <p class="fs-3"><?= $status_counts['completed'] ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Reports -->
  <h4>Latest Reports</h4>
  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>Report ID</th>
        <th>Title</th>
        <th>Reporter</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($r = $recent_reports->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($r['Report_ID']) ?></td>
          <td><?= htmlspecialchars($r['Title']) ?></td>
          <td><?= htmlspecialchars($r['reporter']) ?></td>
          <td><?= htmlspecialchars($r['report_date']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Optional Footer -->
<?php include "includes/footer.php"; ?>

</body>
</html>
