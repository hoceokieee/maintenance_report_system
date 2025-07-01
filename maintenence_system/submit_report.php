<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Define upload directory
define('UPLOAD_PATH', 'assets/uploads/reports/');
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $location = trim($_POST["location"]);
    $urgency_id = $_POST["urgency_id"];
    $category_id = $_POST["category_id"];
    $user_id = $_SESSION["id"];
    $report_date = $_POST["report_date"];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get the latest Report_ID
        $result = $conn->query("SELECT Report_ID FROM REPORT ORDER BY Report_ID DESC LIMIT 1");
        $next_id = 'R001'; // Default starting ID
        
        if ($row = $result->fetch_assoc()) {
            $last_id = $row['Report_ID'];
            $number = intval(substr($last_id, 1)) + 1;
            $next_id = 'R' . str_pad($number, 3, '0', STR_PAD_LEFT);
        }

        // Insert report with the new Report_ID format
        $sql = "INSERT INTO REPORT (Report_ID, Title, Description, Location, User_ID, Category_ID, Urgency_ID, report_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $next_id, $title, $description, $location, $user_id, $category_id, $urgency_id, $report_date);
        $stmt->execute();
        
        $report_id = $next_id;
        
        // Handle file uploads
        if (isset($_FILES["media"]) && $_FILES["media"]["error"] == 0) {
            // Create year/month based directory structure
            $date_folder = date('Y/m/');
            $upload_dir = UPLOAD_PATH . $date_folder;
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/quicktime'];
            $max_file_size = 10 * 1024 * 1024; // 10MB
            
            $file = $_FILES["media"];
            $file_type = $file["type"];
            $file_size = $file["size"];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Invalid file type");
            }
            
            if ($file_size > $max_file_size) {
                throw new Exception("File too large");
            }
            
            // Generate unique filename with original extension
            $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
            $file_name = uniqid('report_') . '_' . date('Ymd_His') . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            $db_file_path = $date_folder . $file_name;
            
            if (move_uploaded_file($file["tmp_name"], $file_path)) {
                // Insert media record
                $sql = "INSERT INTO MEDIA (file_type, file_path, upload_time, Report_ID, uploaded_by) 
                        VALUES (?, ?, NOW(), ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $file_type, $db_file_path, $report_id, $user_id);
                $stmt->execute();
            }
        }
        
        // Insert initial status log - Status_ID will auto-increment
        $sql = "INSERT INTO STATUS_LOG (status, updated_by, updated_time, notes, Report_ID) 
                VALUES ('Pending', ?, NOW(), 'Initial submission', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $report_id); // user_id is integer, report_id is string
        $stmt->execute();
        
        $conn->commit();
        $_SESSION['success_message'] = "Report submitted successfully!";
        header("Location: view_reports.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Get urgency levels and categories AFTER potential redirects
$urgency_levels = $conn->query("SELECT * FROM URGENCY_LEVEL ORDER BY Urgency_ID");
$categories = $conn->query("SELECT * FROM CATEGORY ORDER BY name");

// Include header AFTER all potential redirects
include "includes/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        :root {
            --primary-bg: #bbdefb;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --header-height: 60px;
            --transition-speed: 0.5s;
            --transition-curve: cubic-bezier(0.4, 0, 0.2, 1);
        }

        .container-fluid {
            margin-left: var(--sidebar-width);
            padding: 25px;
            min-width: 800px;
            transition: all var(--transition-speed) var(--transition-curve);
            will-change: margin-left;
        }

        .container-fluid.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        .page-header {
            background: linear-gradient(to right, var(--primary-bg), #e3f2fd);
            padding: 25px 30px;
            margin: -25px -25px 25px -25px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all var(--transition-speed) var(--transition-curve);
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a237e;
            font-weight: 600;
            transition: all var(--transition-speed) var(--transition-curve);
        }

        .submit-form-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.05);
            margin-top: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a237e;
            margin: 0 0 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e3f2fd;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #1a237e;
            font-weight: 500;
            font-size: 14px;
        }

        .form-label i {
            font-size: 16px;
            color: #1976d2;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            transition: all 0.3s;
            background-color: white;
        }

        .form-control:hover, .form-select:hover {
            border-color: #bbdefb;
        }

        .form-control:focus, .form-select:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
            outline: none;
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .priority-options {
            display: grid;
            gap: 10px;
        }

        .priority-option {
            position: relative;
            cursor: pointer;
        }

        .priority-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .priority-content {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .priority-option:hover .priority-content {
            border-color: #bbdefb;
            background: white;
        }

        .priority-option input[type="radio"]:checked + .priority-content {
            border-color: #1976d2;
            background: white;
        }

        .priority-icon {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .bg-danger { background: #ef5350; }
        .bg-warning { background: #ffa726; }
        .bg-success { background: #66bb6a; }

        .priority-label {
            font-size: 14px;
            color: #333;
        }

        .media-section {
            margin-bottom: 30px;
        }

        .media-upload-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        .media-drop-zone {
            border: 2px dashed #bbdefb;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s;
            background: #f8f9fa;
            cursor: pointer;
        }

        .media-drop-zone:hover, .media-drop-zone.dragover {
            border-color: #1976d2;
            background: white;
        }

        .upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .upload-icon {
            font-size: 48px;
            color: #1976d2;
        }

        .upload-text {
            margin: 0;
            color: #666;
            font-size: 16px;
            line-height: 1.5;
        }

        .upload-text span {
            color: #1976d2;
            font-weight: 500;
        }

        .btn-browse {
            background: #1976d2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-browse:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }

        .upload-limits {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #666;
        }

        .upload-limits span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .media-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .preview-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 1;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s;
        }

        .preview-item:hover img {
            transform: scale(1.05);
        }

        .remove-media {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            opacity: 0;
            transform: scale(0.8);
        }

        .preview-item:hover .remove-media {
            opacity: 1;
            transform: scale(1);
        }

        .remove-media:hover {
            background: rgba(0,0,0,0.8);
            transform: scale(1.1);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .btn-submit, .btn-reset {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit {
            background: #1976d2;
            color: white;
            border: none;
        }

        .btn-submit:hover {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
        }

        .btn-reset {
            background: #f5f5f5;
            color: #666;
            border: none;
        }

        .btn-reset:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        @media (max-width: 1200px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .submit-form-container {
                padding: 25px;
            }

            .media-preview {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            }
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const container = document.querySelector('.container-fluid');

        // Watch for sidebar state changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.classList.contains('collapsed')) {
                    container.classList.add('expanded');
                } else {
                    container.classList.remove('expanded');
                }
            });
        });

        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

        // Initial state check
        if (sidebar.classList.contains('collapsed')) {
            container.classList.add('expanded');
        }

        // Enhanced file upload handling
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('media');
        const previewContainer = document.getElementById('mediaPreview');
        const browseBtn = document.querySelector('.btn-browse');

        // Handle drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener('drop', handleDrop, false);
        });

        function highlight(e) {
            dropZone.classList.add('dragover');
        }

        function unhighlight(e) {
            dropZone.classList.remove('dragover');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        // Handle file selection
        browseBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', function(e) {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            previewContainer.innerHTML = ''; // Clear existing previews
            
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = createPreviewItem(e.target.result);
                        previewContainer.appendChild(preview);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        function createPreviewItem(src) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = `
                <img src="${src}" alt="Preview">
                <button type="button" class="remove-media" aria-label="Remove">×</button>
            `;
            
            div.querySelector('.remove-media').addEventListener('click', function() {
                div.remove();
                // Clear file input if all previews are removed
                if (previewContainer.children.length === 0) {
                    fileInput.value = '';
                }
            });
            
            return div;
        }

        // Form validation and submission feedback
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                // Add custom validation UI if needed
            }
        });
    });
    </script>
</head>
<body>
    <div class="container-fluid">
        <div class="page-header">
            <h1><i class="bi bi-clipboard-plus"></i> Submit Maintenance Report</h1>
        </div>

        <div class="submit-form-container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="report-form">
                <div class="form-grid">
                    <div class="form-section">
                        <h2 class="section-title">Basic Information</h2>
                        
                        <div class="form-group">
                            <label for="title" class="form-label">
                                <i class="bi bi-tag"></i>
                                Report Title
                            </label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="Enter a descriptive title" required>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">
                                <i class="bi bi-card-text"></i>
                                Description
                            </label>
                            <textarea class="form-control" id="description" name="description" 
                                      placeholder="Describe the issue in detail" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="location" class="form-label">
                                <i class="bi bi-geo-alt"></i>
                                Location
                            </label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   placeholder="Specify the location" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="section-title">Classification</h2>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">
                                <i class="bi bi-folder"></i>
                                Category
                            </label>
                            <select class="form-select custom-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php while ($category = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($category['Category_ID']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-exclamation-triangle"></i>
                                Priority Level
                            </label>
                            <div class="priority-options">
                                <?php 
                                $urgency_colors = [
                                    'High' => 'danger',
                                    'Medium' => 'warning',
                                    'Low' => 'success'
                                ];
                                while ($level = $urgency_levels->fetch_assoc()): 
                                    $color = $urgency_colors[$level['label']] ?? 'primary';
                                ?>
                                    <label class="priority-option priority-<?php echo strtolower($level['label']); ?>">
                                        <input type="radio" name="urgency_id" value="<?php echo $level['Urgency_ID']; ?>" required>
                                        <span class="priority-content">
                                            <span class="priority-icon bg-<?php echo $color; ?>"></span>
                                            <span class="priority-label"><?php echo htmlspecialchars($level['label']); ?></span>
                                        </span>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="report_date" class="form-label">
                                <i class="bi bi-calendar"></i>
                                Report Date
                            </label>
                            <input type="date" class="form-control" id="report_date" name="report_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section media-section">
                    <h2 class="section-title">Media Attachment</h2>
                    
                    <div class="form-group">
                        <div class="media-upload-container">
                            <div class="media-drop-zone" id="dropZone">
                                <input type="file" class="file-input" id="media" name="media" 
                                       accept="image/*,video/*" hidden>
                                <div class="upload-content">
                                    <i class="bi bi-cloud-upload upload-icon"></i>
                                    <p class="upload-text">
                                        Drag and drop your files here<br>
                                        <span>or</span>
                                    </p>
                                    <button type="button" class="btn-browse">Browse Files</button>
                                </div>
                            </div>
                            <div class="upload-limits">
                                <span><i class="bi bi-info-circle"></i> Maximum file size: 10MB</span>
                                <span><i class="bi bi-file-earmark"></i> Allowed: JPG, PNG, GIF, MP4, MOV</span>
                            </div>
                        </div>
                        <div class="media-preview" id="mediaPreview"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-send"></i>
                        Submit Report
                    </button>
                    <button type="reset" class="btn-reset">
                        <i class="bi bi-x-circle"></i>
                        Clear Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 