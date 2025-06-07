<?php
require_once 'include/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

include 'templates/header.php';

$conn = getDBConnection();

// Get user's bookings
$stmt = $conn->prepare("
    SELECT b.*, r.room_type, r.room_number, r.price_per_night
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $comment = sanitizeInput($_POST['comment']);
    
    if ($rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("
            INSERT INTO reviews (booking_id, user_id, rating, comment)
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$booking_id, $_SESSION['user_id'], $rating, $comment])) {
            $success = 'Review submitted successfully!';
        } else {
            $error = 'Failed to submit review. Please try again.';
        }
    } else {
        $error = 'Please select a valid rating.';
    }
}
?>

<div class="container py-5">
    <h2 class="mb-4">My Bookings</h2>
    
    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">
            You haven't made any bookings yet. <a href="rooms.php">Browse our rooms</a> to make a booking.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($bookings as $booking): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo ucfirst($booking['room_type']); ?> Room</h5>
                            <h6 class="card-subtitle mb-2 text-muted">Room <?php echo $booking['room_number']; ?></h6>
                            
                            <div class="mb-3">
                                <p class="mb-1">
                                    <strong>Check-in:</strong> <?php echo date('F j, Y', strtotime($booking['check_in_date'])); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Check-out:</strong> <?php echo date('F j, Y', strtotime($booking['check_out_date'])); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Total Price:</strong> TZS <?php echo number_format($booking['total_price'], 2); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] === 'confirmed' ? 'success' : 
                                            ($booking['status'] === 'pending' ? 'warning' : 
                                            ($booking['status'] === 'cancelled' ? 'danger' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </p>
                                <p class="mb-1">
                                    <strong>Payment Status:</strong>
                                    <span class="badge bg-<?php 
                                        echo $booking['payment_status'] === 'paid' ? 'success' : 
                                            ($booking['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </p>
                            </div>
                            
                            <?php if ($booking['status'] === 'completed' && $booking['payment_status'] === 'paid'): ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $booking['id']; ?>">
                                    Write a Review
                                </button>
                                
                                <!-- Review Modal -->
                                <div class="modal fade" id="reviewModal<?php echo $booking['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Write a Review</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="">
                                                <div class="modal-body">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label">Rating</label>
                                                        <div class="rating">
                                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?><?php echo $booking['id']; ?>" required>
                                                                <label for="star<?php echo $i; ?><?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="comment<?php echo $booking['id']; ?>" class="form-label">Comment</label>
                                                        <textarea class="form-control" id="comment<?php echo $booking['id']; ?>" name="comment" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Submit Review</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['status'] === 'pending' && $booking['payment_status'] === 'pending'): ?>
                                <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success">
                                    Make Payment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating input {
    display: none;
}

.rating label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
    padding: 0 0.1em;
}

.rating input:checked ~ label,
.rating label:hover,
.rating label:hover ~ label {
    color: #ffd700;
}
</style>

<?php include 'templates/footer.php'; ?> 