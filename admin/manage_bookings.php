<?php
require_once '../include/functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

include '../templates/header.php';

$conn = getDBConnection();

// Handle status updates
if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = sanitizeInput($_POST['new_status']);
    $new_payment_status = sanitizeInput($_POST['new_payment_status']);
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ?, payment_status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $new_payment_status, $booking_id])) {
        $success_message = "Booking status updated successfully.";
    } else {
        $error_message = "Failed to update booking status.";
    }
}

// Get all bookings with user and room details
$stmt = $conn->query("
    SELECT b.*, u.full_name, u.email, r.room_type, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Bookings</h2>
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
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Total Price</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td>
                                    <?php echo $booking['full_name']; ?><br>
                                    <small class="text-muted"><?php echo $booking['email']; ?></small>
                                </td>
                                <td>
                                    <?php echo ucfirst($booking['room_type']); ?><br>
                                    <small class="text-muted">Room <?php echo $booking['room_number']; ?></small>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></td>
                                <td>TZS <?php echo number_format($booking['total_price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="new_status" class="form-select form-select-sm" 
                                                onchange="this.form.submit()" 
                                                style="width: 120px;">
                                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>
                                                Pending
                                            </option>
                                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                Confirmed
                                            </option>
                                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                Cancelled
                                            </option>
                                            <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>
                                                Completed
                                            </option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="new_payment_status" class="form-select form-select-sm" 
                                                onchange="this.form.submit()" 
                                                style="width: 120px;">
                                            <option value="pending" <?php echo $booking['payment_status'] === 'pending' ? 'selected' : ''; ?>>
                                                Pending
                                            </option>
                                            <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>
                                                Paid
                                            </option>
                                            <option value="refunded" <?php echo $booking['payment_status'] === 'refunded' ? 'selected' : ''; ?>>
                                                Refunded
                                            </option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewModal<?php echo $booking['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <!-- View Booking Modal -->
                                    <div class="modal fade" id="viewModal<?php echo $booking['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Booking Details #<?php echo $booking['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <dl class="row">
                                                        <dt class="col-sm-4">Customer Name</dt>
                                                        <dd class="col-sm-8"><?php echo $booking['full_name']; ?></dd>
                                                        
                                                        <dt class="col-sm-4">Email</dt>
                                                        <dd class="col-sm-8"><?php echo $booking['email']; ?></dd>
                                                        
                                                        <dt class="col-sm-4">Room</dt>
                                                        <dd class="col-sm-8">
                                                            <?php echo ucfirst($booking['room_type']); ?> 
                                                            (Room <?php echo $booking['room_number']; ?>)
                                                        </dd>
                                                        
                                                        <dt class="col-sm-4">Check-in</dt>
                                                        <dd class="col-sm-8"><?php echo date('F j, Y', strtotime($booking['check_in_date'])); ?></dd>
                                                        
                                                        <dt class="col-sm-4">Check-out</dt>
                                                        <dd class="col-sm-8"><?php echo date('F j, Y', strtotime($booking['check_out_date'])); ?></dd>
                                                        
                                                        <dt class="col-sm-4">Total Price</dt>
                                                        <dd class="col-sm-8">TZS <?php echo number_format($booking['total_price'], 2); ?></dd>
                                                        
                                                        <dt class="col-sm-4">Status</dt>
                                                        <dd class="col-sm-8">
                                                            <span class="badge bg-<?php 
                                                                echo $booking['status'] === 'confirmed' ? 'success' : 
                                                                    ($booking['status'] === 'pending' ? 'warning' : 
                                                                    ($booking['status'] === 'cancelled' ? 'danger' : 'info')); 
                                                            ?>">
                                                                <?php echo ucfirst($booking['status']); ?>
                                                            </span>
                                                        </dd>
                                                        
                                                        <dt class="col-sm-4">Payment Status</dt>
                                                        <dd class="col-sm-8">
                                                            <span class="badge bg-<?php 
                                                                echo $booking['payment_status'] === 'paid' ? 'success' : 
                                                                    ($booking['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                                                            ?>">
                                                                <?php echo ucfirst($booking['payment_status']); ?>
                                                            </span>
                                                        </dd>
                                                        
                                                        <dt class="col-sm-4">Booking Date</dt>
                                                        <dd class="col-sm-8"><?php echo date('F j, Y H:i', strtotime($booking['created_at'])); ?></dd>
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