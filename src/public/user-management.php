<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$userEmail = $_SESSION['user_email'] ?? 'Unknown';

// Get user's theme preference
$themeMode = 'auto';
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap/database.php';
    $config = new \CiInbox\Modules\Config\ConfigService(__DIR__ . '/../../');
    initDatabase($config);
    $user = \CiInbox\App\Models\User::find($_SESSION['user_id']);
    if ($user && isset($user->theme_mode)) {
        $themeMode = $user->theme_mode;
    }
} catch (Exception $e) {
    // Fallback
}
?>
<!DOCTYPE html>
<html lang="de" data-user-theme="<?= htmlspecialchars($themeMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - C-IMAP</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .page-header {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .user-table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .btn-primary {
            background: #2196F3;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background: #1976D2;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        }
        
        .user-table {
            margin: 0;
        }
        
        .user-table thead {
            background: #f8f9fa;
        }
        
        .user-table th {
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
            border: none;
        }
        
        .user-table td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
        }
        
        .user-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-danger {
            background: #ffebee;
            color: #c62828;
        }
        
        .badge-primary {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .badge-secondary {
            background: #f5f5f5;
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 6px;
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #dc3545;
            border: none;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
        }
        
        .modal-header {
            border-bottom: 1px solid #e0e0e0;
            padding: 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 0.625rem 0.875rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #2196F3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }
        
        .form-check-input:checked {
            background-color: #2196F3;
            border-color: #2196F3;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            padding: 0.75rem 0;
        }
        
        .nav-link {
            color: #666 !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background: #f5f5f5;
            color: #333 !important;
        }
        
        .nav-link.active {
            background: #e3f2fd;
            color: #1976D2 !important;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #999;
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="/inbox.php">
                <strong>C-IMAP</strong>
            </a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/inbox.php">Inbox</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/settings.php">Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/admin-settings.php">Admin Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/user-management.php">Users</a>
                </li>
                <li class="nav-item dropdown ms-3">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar"><?= strtoupper(substr($userEmail, 0, 2)) ?></div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/settings.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container-fluid">
            <h1 class="page-title">User Management</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Alert Container -->
                <div id="alert-container"></div>
                
                <!-- User Table Card -->
                <div class="user-table-card">
                    <div class="table-header">
                        <h2>Users</h2>
                        <button id="btn-add-user" class="btn btn-primary">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.5rem;">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                            </svg>
                            Add User
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table user-table" id="users-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal (Create/Edit) -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="user-form">
                        <input type="hidden" id="user-id" name="user_id">
                        
                        <div class="mb-3">
                            <label for="user-name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="user-name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="user-email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="user-email" name="email" required>
                        </div>
                        
                        <div class="mb-3" id="password-group">
                            <label for="user-password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="user-password" name="password" minlength="8">
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="user-role" class="form-label">Role *</label>
                            <select class="form-select" id="user-role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="user-active" name="is_active" checked>
                            <label class="form-check-label" for="user-active">
                                Active Account
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btn-save-user">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user?</p>
                    <p class="text-danger"><strong>This action cannot be undone.</strong></p>
                    <p id="delete-user-info" class="mb-0"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btn-confirm-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/user-management.js"></script>
</body>
</html>
