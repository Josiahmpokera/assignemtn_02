<?php
require_once '../include/functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

include '../templates/header.php';

$conn = getDBConnection();

// Get statistics
$stats = [
    'total_bookings' => $conn->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'total_revenue' => $conn->query("SELECT SUM(total_price) FROM bookings WHERE payment_status = 'paid'")->fetchColumn(),
    'total_rooms' => $conn->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn()
];

// Get recent bookings
$stmt = $conn->query("
    SELECT b.*, u.full_name, r.room_type, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    ORDER BY b.created_at DESC
    LIMIT 5
");
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get room occupancy
$stmt = $conn->query("
    SELECT room_type, COUNT(*) as total,
           SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied
    FROM rooms
    GROUP BY room_type
");
$room_occupancy = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card dashboard-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Bookings</h6>
                            <h2 class="mb-0"><?php echo $stats['total_bookings']; ?></h2>
                        </div>
                    
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card dashboard-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Revenue</h6>
                            <h2 class="mb-0">TZS <?php echo number_format($stats['total_revenue'], 2); ?></h2>
                        </div>
                       
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card dashboard-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Rooms</h6>
                            <h2 class="mb-0"><?php echo $stats['total_rooms']; ?></h2>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card dashboard-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Customers</h6>
                            <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Customer</th>
                                    <th>Room</th>
                                    <th>Check-in</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td>#<?php echo $booking['id']; ?></td>
                                        <td><?php echo $booking['full_name']; ?></td>
                                        <td><?php echo ucfirst($booking['room_type']); ?> (<?php echo $booking['room_number']; ?>)</td>
                                        <td><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] === 'confirmed' ? 'success' : 
                                                    ($booking['status'] === 'pending' ? 'warning' : 
                                                    ($booking['status'] === 'cancelled' ? 'danger' : 'info')); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td>TZS <?php echo number_format($booking['total_price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Room Occupancy -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Room Occupancy</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($room_occupancy as $room): ?>
                        <div class="mb-3">
                            <h6><?php echo ucfirst($room['room_type']); ?> Rooms</h6>
                            <div class="progress">
                                <?php 
                                $occupancy_rate = $room['total'] > 0 ? ($room['occupied'] / $room['total']) * 100 : 0;
                                ?>
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $occupancy_rate; ?>%"
                                     aria-valuenow="<?php echo $occupancy_rate; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo number_format($occupancy_rate, 1); ?>%
                                </div>
                            </div>
                            <small class="text-muted">
                                <?php echo $room['occupied']; ?> of <?php echo $room['total']; ?> rooms occupied
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="manage_rooms.php" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-bed"></i> Manage Rooms
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="manage_bookings.php" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-calendar-alt"></i> Manage Bookings
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="manage_users.php" class="btn btn-info w-100 mb-2">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="reports.php" class="btn btn-warning w-100 mb-2">
                                <i class="fas fa-chart-bar"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?> 