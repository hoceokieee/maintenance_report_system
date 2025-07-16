<?php
require_once "includes/session.php";
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

require_once "config/database.php";
include "includes/header.php";

// Get filter values
$urgency_id = isset($_GET["urgency_id"]) ? $_GET["urgency_id"] : "";
$status = isset($_GET["status"]) ? $_GET["status"] : "";
$search = isset($_GET["search"]) ? $_GET["search"] : "";
$category_id = isset($_GET["category_id"]) ? $_GET["category_id"] : "";

// Pagination
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build query
$where_conditions = [];
$params = [];
$param_types = "";

if ($urgency_id) {
    $where_conditions[] = "r.Urgency_ID = ?";
    $params[] = $urgency_id;
    $param_types .= "s";
}

if ($status) {
    $where_conditions[] = "COALESCE(sl.status, 'Pending') = ?";
    $params[] = $status;
    $param_types .= "s";
}

if ($search) {
    $where_conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ssss";
}

if ($category_id) {
    $where_conditions[] = "r.Category_ID = ?";
    $params[] = $category_id;
    $param_types .= "i";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records for pagination
$count_sql = "SELECT COUNT(DISTINCT r.Report_ID) as total 
              FROM REPORT r 
              JOIN USERS u ON r.User_ID = u.User_ID 
              LEFT JOIN STATUS_LOG sl ON r.Report_ID = sl.Report_ID 
              AND sl.Status_ID = (
                  SELECT MAX(Status_ID) 
                  FROM STATUS_LOG 
                  WHERE Report_ID = r.Report_ID
              ) 
              $where_clause";

$stmt = $conn->prepare($count_sql);
if ($params) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()["total"];
$total_pages = ceil($total_records / $records_per_page);

// Get reports
$sql = "SELECT 
    r.Report_ID,
    r.title,
    r.description,
    r.location,
    r.report_date,
    r.User_ID,
    c.name AS category_name,
    ul.label AS urgency_label,
    (
        SELECT sl.status
        FROM STATUS_LOG sl
        WHERE sl.Report_ID = r.Report_ID
        ORDER BY sl.Status_ID DESC
        LIMIT 1
    ) AS current_status,
    (
        SELECT m.file_path
        FROM MEDIA m
        WHERE m.Report_ID = r.Report_ID
        LIMIT 1
    ) AS media_path
FROM REPORT r
LEFT JOIN CATEGORY c ON r.Category_ID = c.Category_ID
LEFT JOIN URGENCY_LEVEL ul ON r.Urgency_ID = ul.Urgency_ID
WHERE r.User_ID = ?
ORDER BY r.report_date DESC
LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $_SESSION['id'], $offset, $records_per_page);
$stmt->execute();
$result = $stmt->get_result();

// Get filter options
$urgency_levels = $conn->query("SELECT * FROM URGENCY_LEVEL ORDER BY Urgency_ID");
$statuses = ["Pending", "In Progress", "Completed"];
$categories = $conn->query("SELECT * FROM CATEGORY ORDER BY Category_ID");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        .page-header {
            background: var(--primary-bg);
            padding: 20px 25px;
            margin-left: var(--sidebar-width);
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
            margin-left: var(--sidebar-width);
            transition: all var(--transition-speed) var(--transition-curve);
            width: calc(100% - var(--sidebar-width));
        }

        @media (max-width: 1200px) {
            .reports-container {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 80px 25px 25px 25px;
            }

            .page-header {
        margin-left: 0 !important;
        border-radius: 8px;
    }

        }

        .filters-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 500;
            color: #1a237e;
            margin-bottom: 8px;
        }

        .filter-select {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-select:focus {
            border-color: #bbdefb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(187, 222, 251, 0.25);
        }

        .search-box {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: #bbdefb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(187, 222, 251, 0.25);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
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
            white-space: nowrap;
        }

        .reports-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e3f2fd;
            color: #333;
            font-size: 14px;
            vertical-align: middle;
        }

        .reports-table tr:hover {
            background-color: #f5f5f5;
        }

        .priority-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 500;
        }

        .priority-high {
            background: #ffebee;
            color: #d32f2f;
        }

        .priority-medium {
            background: #fff3e0;
            color: #f57c00;
        }

        .priority-low {
            background: #e8f5e9;
            color: #388e3c;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-completed {
            background: #e8f5e9;
            color: #388e3c;
        }

        .status-progress {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-pending {
            background: #f5f5f5;
            color: #616161;
        }

        .media-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 500;
            background: #e3f2fd;
            color: #1976d2;
        }

        .media-badge i {
            margin-right: 5px;
        }

        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .btn-view {
            background: #1976d2;
        }

        .btn-edit {
            background: #43a047;
        }

        .btn-delete {
            background: #e53935;
        }

        .btn-action:hover {
            opacity: 0.9;
        }

        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #bbdefb;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .filters-section {
                grid-template-columns: 1fr;
            }

            .reports-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }

            .main-content.expanded .container-fluid {
            margin-left: var(--sidebar-collapsed-width);
            }

            .main-content.expanded .page-header,
            .main-content.expanded .reports-container {
                margin-left: var(--sidebar-collapsed-width);
                width: calc(100% - var(--sidebar-collapsed-width));
            }
        }
    </style>
</head>
<body>
  <div class="main-content">
    <div class="container-fluid py-4">
      <div class="page-header">
        <h1 class="section-title">View Reports</h1>
      </div>

      <div class="reports-container">
        <!-- Filters -->
        <div class="filters-section">
          <!-- Category Filter -->
          <div class="filter-group">
            <label class="filter-label">Category</label>
            <select class="filter-select" id="categoryFilter">
              <option value="">All Categories</option>
              <?php while ($category = $categories->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($category['Category_ID']) ?>">
                  <?= htmlspecialchars($category['name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Status Filter -->
          <div class="filter-group">
            <label class="filter-label">Status</label>
            <select class="filter-select" id="statusFilter">
              <option value="">All Statuses</option>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
              <option value="Completed">Completed</option>
            </select>
          </div>

          <!-- Priority Filter -->
          <div class="filter-group">
            <label class="filter-label">Priority</label>
            <select class="filter-select" id="priorityFilter">
              <option value="">All Priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>

          <!-- Search Filter -->
          <div class="filter-group">
            <label class="filter-label">Search</label>
            <div class="search-box">
              <i class="bi bi-search search-icon"></i>
              <input type="text" class="search-input" placeholder="Search reports...">
            </div>
          </div>
        </div>

        <!-- Reports Table -->
        <div class="table-responsive">
          <table class="reports-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Location</th>
                <th>Date</th>
                <th>User ID</th>
                <th>Category</th>
                <th>Urgency</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($report = $result->fetch_assoc()) { ?>
                <tr>
                  <td>#<?= htmlspecialchars($report["Report_ID"]) ?></td>
                  <td><?= htmlspecialchars($report["title"]) ?></td>
                  <td><?= htmlspecialchars($report["description"]) ?></td>
                  <td><?= htmlspecialchars($report["location"]) ?></td>
                  <td><?= htmlspecialchars($report["report_date"]) ?></td>
                  <td><?= htmlspecialchars($report["User_ID"]) ?></td>
                  <td><?= htmlspecialchars($report["category_name"] ?? "Unknown") ?></td>
                  <td><?= htmlspecialchars($report["urgency_label"] ?? "Unknown") ?></td>
                  <td>
                    <a href="view.php?report_id=<?= htmlspecialchars($report['Report_ID']) ?>" class="btn-action btn-view">View</a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
          <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
              <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&urgency_id=<?= $urgency_id ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>">Previous</a>
              </li>

              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&urgency_id=<?= $urgency_id ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>">
                    <?= $i ?>
                  </a>
                </li>
              <?php endfor; ?>

              <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&urgency_id=<?= $urgency_id ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>">Next</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- JavaScript for Filters -->
  <script>
    const filters = {
      category: document.getElementById('categoryFilter'),
      status: document.getElementById('statusFilter'),
      priority: document.getElementById('priorityFilter'),
      search: document.querySelector('.search-input')
    };

    function applyFilters() {
      const params = new URLSearchParams(window.location.search);

      if (filters.category.value) params.set('category_id', filters.category.value);
      if (filters.status.value) params.set('status', filters.status.value);
      if (filters.priority.value) params.set('urgency_id', filters.priority.value);
      if (filters.search.value) params.set('search', filters.search.value);

      params.set('page', '1');
      window.location.href = window.location.pathname + '?' + params.toString();
    }

    let searchTimeout;
    filters.search.addEventListener('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(applyFilters, 500);
    });

    filters.category.addEventListener('change', applyFilters);
    filters.status.addEventListener('change', applyFilters);
    filters.priority.addEventListener('change', applyFilters);
  </script>

  <!-- JavaScript for Sidebar Collapse Sync -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.querySelector('.sidebar');
      const mainContent = document.querySelector('.main-content');

      const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function () {
          if (sidebar.classList.contains('collapsed')) {
            mainContent.classList.add('expanded');
          } else {
            mainContent.classList.remove('expanded');
          }
        });
      });

      observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

      if (sidebar.classList.contains('collapsed')) {
        mainContent.classList.add('expanded');
      }
    });
  </script>
</body>
</html>

<?php include "includes/footer.php"; ?>