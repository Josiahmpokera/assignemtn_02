<?php
require_once 'include/functions.php';
include 'templates/header.php';

if (!isset($_GET['id'])) {
    redirect('rooms.php');
}

$room_id = (int)$_GET['id'];
$conn = getDBConnection();

// Get room details
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    redirect('rooms.php');
}

// Get room amenities
$stmt = $conn->prepare("SELECT * FROM room_amenities WHERE room_id = ?");
$stmt->execute([$room_id]);
$amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get room reviews
$stmt = $conn->prepare("
    SELECT r.*, u.full_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.booking_id IN (SELECT id FROM bookings WHERE room_id = ?)
    ORDER BY r.created_at DESC
");
$stmt->execute([$room_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = $total_rating / count($reviews);
}

// Handle booking form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $check_in = sanitizeInput($_POST['check_in']);
    $check_out = sanitizeInput($_POST['check_out']);
    
    if (empty($check_in) || empty($check_out)) {
        $error = 'Please select check-in and check-out dates';
    } else {
        // Check if room is available for selected dates
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM bookings 
            WHERE room_id = ? 
            AND status != 'cancelled'
            AND (
                (check_in_date <= ? AND check_out_date >= ?)
                OR (check_in_date <= ? AND check_out_date >= ?)
                OR (check_in_date >= ? AND check_out_date <= ?)
            )
        ");
        $stmt->execute([
            $room_id, 
            $check_in, $check_in,
            $check_out, $check_out,
            $check_in, $check_out
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'Room is not available for selected dates';
        } else {
            // Calculate total price
            $total_price = calculateTotalPrice($room['price_per_night'], $check_in, $check_out);
            
            // Create booking
            $stmt = $conn->prepare("
                INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $room_id, $check_in, $check_out, $total_price])) {
                $success = 'Booking successful! Please complete the payment.';
            } else {
                $error = 'Booking failed. Please try again.';
            }
        }
    }
}
?>

<!-- Room Details -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="room-details">
                    <h2><?php echo ucfirst($room['room_type']); ?> Room</h2>
                    <img src="assets/images/rooms/<?php echo $room['room_type']; ?>.jpg" class="img-fluid rounded mb-4" alt="<?php echo $room['room_type']; ?> Room">
                    
                    <div class="mb-4">
                        <h4>Description</h4>
                        <p><?php echo $room['description']; ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h4>Amenities</h4>
                        <ul class="room-amenities">
                            <?php foreach ($amenities as $amenity): ?>
                                <li><i class="fas fa-check"></i> <?php echo $amenity['amenity_name']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="mb-4">
                        <h4>Room Features</h4>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-user-friends"></i> Capacity: <?php echo $room['capacity']; ?> persons</li>
                            <li><i class="fas fa-bed"></i> <?php echo ucfirst($room['room_type']); ?> bed</li>
                            <li><i class="fas fa-wifi"></i> Free Wi-Fi</li>
                            <li><i class="fas fa-tv"></i> Smart TV</li>
                            <li><i class="fas fa-snowflake"></i> Air Conditioning</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div class="mt-5">
                    <h4>Guest Reviews</h4>
                    <div class="mb-3">
                        <div class="d-flex align-items-center">
                            <div class="h2 mb-0 me-2"><?php echo number_format($avg_rating, 1); ?></div>
                            <div>
                                <div class="text-warning">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $avg_rating ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-muted"><?php echo count($reviews); ?> reviews</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php foreach ($reviews as $review): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="card-subtitle text-muted"><?php echo $review['full_name']; ?></h6>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="card-text"><?php echo $review['comment']; ?></p>
                                <small class="text-muted"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card booking-form">
                    <div class="card-body">
                        <h4 class="card-title">Book This Room</h4>
                        <p class="room-price mb-4">$<?php echo number_format($room['price_per_night'], 2); ?> per night</p>
                        
                        <?php if ($error): ?>
                            <?php echo displayError($error); ?>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <?php echo displaySuccess($success); ?>
                        <?php endif; ?>
                        
                        <?php if (isLoggedIn()): ?>
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="check_in" class="form-label">Check-in Date</label>
                                    <input type="date" class="form-control datepicker" id="check_in" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="check_out" class="form-label">Check-out Date</label>
                                    <input type="date" class="form-control datepicker" id="check_out" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Total Price</label>
                                    <h5 id="totalPrice">$0.00</h5>
                                    <input type="hidden" id="pricePerNight" value="<?php echo $room['price_per_night']; ?>">
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">Book Now</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Please <a href="login.php">login</a> to book this room.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?> 