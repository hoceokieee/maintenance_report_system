<?php
session_start();
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

// Add user role condition
if ($_SESSION["role"] != "Admin" && $_SESSION["role"] != "Manager") {
    $where_conditions[] = "r.User_ID = ?";
    $params[] = $_SESSION["id"];
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
$sql = "SELECT r.*, u.name as reporter_name, 
        ul.label as urgency_label, 
        COALESCE(sl.status, 'Pending') as current_status,
        (SELECT COUNT(*) FROM MEDIA m WHERE m.Report_ID = r.Report_ID) as media_count
        FROM REPORT r 
        JOIN USERS u ON r.User_ID = u.User_ID 
        JOIN URGENCY_LEVEL ul ON r.Urgency_ID = ul.Urgency_ID 
        LEFT JOIN STATUS_LOG sl ON r.Report_ID = sl.Report_ID 
        AND sl.Status_ID = (
            SELECT MAX(Status_ID) 
            FROM STATUS_LOG 
            WHERE Report_ID = r.Report_ID
        ) 
        $where_clause 
        ORDER BY r.Report_ID DESC 
        LIMIT ? OFFSET ?";

$params[] = $records_per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$reports = $stmt->get_result();

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
            padding: 20px;
            margin: -25px -25px 25px -25px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="page-header">
            <h1>View Reports</h1>
        </div>
        
        <div class="reports-container">
            <div class="filters-section">
                <div class="filter-group">
                    <label class="filter-label">Category</label>
                    <select class="filter-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($category['Category_ID']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select class="filter-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Priority</label>
                    <select class="filter-select" id="priorityFilter">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>

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
                            <th>Reporter</th>
                            <th>Location</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Media</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($report = $reports->fetch_assoc()) { ?>
                            <tr>
                                <td>#<?php echo $report["Report_ID"]; ?></td>
                                <td><?php echo htmlspecialchars($report["title"]); ?></td>
                                <td><?php echo htmlspecialchars($report["reporter_name"]); ?></td>
                                <td><?php echo htmlspecialchars($report["location"]); ?></td>
                                <td>
                                    <span class="priority-badge priority-<?php 
                                        echo strtolower($report["urgency_label"]); 
                                    ?>">
                                        <?php echo htmlspecialchars($report["urgency_label"]); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php 
                                        echo strtolower(str_replace(' ', '', $report["current_status"])); 
                                    ?>">
                                        <?php echo $report["current_status"]; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($report["media_count"] > 0) { ?>
                                        <span class="media-badge">
                                            <i class="bi bi-image"></i>
                                            <?php echo $report["media_count"]; ?> files
                                        </span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="view_report.php?id=<?php echo $report["Report_ID"]; ?>" 
                                       class="btn btn-primary btn-sm">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1) { ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? "disabled" : ""; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&urgency_id=<?php echo $urgency_id; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                Previous
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                            <li class="page-item <?php echo $page == $i ? "active" : ""; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&urgency_id=<?php echo $urgency_id; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php } ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? "disabled" : ""; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&urgency_id=<?php echo $urgency_id; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php } ?>
        </div>
    </div>

    <script>
        // Filter functionality
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
            
            // Reset to first page when applying new filters
            params.set('page', '1');
            
            window.location.href = window.location.pathname + '?' + params.toString();
        }

        // Add debounce for search input
        let searchTimeout;
        filters.search.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 500);
        });

        // Apply filters immediately for other inputs
        filters.category.addEventListener('change', applyFilters);
        filters.status.addEventListener('change', applyFilters);
        filters.priority.addEventListener('change', applyFilters);
    </script>
</body>
</html>

<?php include "includes/footer.php"; ?> 