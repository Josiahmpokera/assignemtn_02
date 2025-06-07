<?php
require_once '../include/functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

include '../templates/header.php';

$conn = getDBConnection();

// Handle user status update
if (isset($_POST['update_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = sanitizeInput($_POST['new_status']);
    
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $user_id])) {
        $success_message = "User status updated successfully.";
    } else {
        $error_message = "Failed to update user status.";
    }
}

// Handle user role update
if (isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = sanitizeInput($_POST['new_role']);
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    if ($stmt->execute([$new_role, $user_id])) {
        $success_message = "User role updated successfully.";
    } else {
        $error_message = "Failed to update user role.";
    }
}

// Get all users
$stmt = $conn->query("
    SELECT u.*, 
           COUNT(DISTINCT b.id) as total_bookings,
           SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Users</h2>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Bookings</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td><?php echo $user['full_name']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="update_role" value="1">
                                        <select name="new_role" class="form-select form-select-sm" 
                                                onchange="this.form.submit()" 
                                                style="width: 120px;">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>
                                                User
                                            </option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                                Admin
                                            </option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="new_status" class="form-select form-select-sm" 
                                                onchange="this.form.submit()" 
                                                style="width: 120px;">
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>
                                                Active
                                            </option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>
                                                Inactive
                                            </option>
                                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>
                                                Suspended
                                            </option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <?php echo $user['total_bookings']; ?> total<br>
                                    <small class="text-muted"><?php echo $user['completed_bookings']; ?> completed</small>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <!-- View User Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">User Details #<?php echo $user['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <dl class="row">
                                                        <dt class="col-sm-4">Full Name</dt>
                                                        <dd class="col-sm-8"><?php echo $user['full_name']; ?></dd>
                                                        
                                                        <dt class="col-sm-4">Email</dt>
                                                        <dd class="col-sm-8"><?php echo $user['email']; ?></dd>
                                                        
                                                        <dt class="col-sm-4">Phone</dt>
                                                        <dd class="col-sm-8"><?php echo $user['phone'] ?: 'Not provided'; ?></dd>
                                                        
                                                        <dt class="col-sm-4">Role</dt>
                                                        <dd class="col-sm-8">
                                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                                                <?php echo ucfirst($user['role']); ?>
                                                            </span>
                                                        </dd>
                                                        
                                                        <dt class="col-sm-4">Status</dt>
                                                        <dd class="col-sm-8">
                                                            <span class="badge bg-<?php 
                                                                echo $user['status'] === 'active' ? 'success' : 
                                                                    ($user['status'] === 'inactive' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($user['status']); ?>
                                                            </span>
                                                        </dd>
                                                        
                                                        <dt class="col-sm-4">Total Bookings</dt>
                                                        <dd class="col-sm-8"><?php echo $user['total_bookings']; ?></dd>
                                                        
                                                        <dt class="col-sm-4">Completed Bookings</dt>
                                                        <dd class="col-sm-8"><?php echo $user['completed_bookings']; ?></dd>
                                                        
                                                        <dt class="col-sm-4">Joined Date</dt>
                                                        <dd class="col-sm-8"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></dd>
                                                    </dl>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?> 