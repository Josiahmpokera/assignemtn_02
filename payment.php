<?php
require_once 'include/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['booking_id'])) {
    redirect('bookings.php');
}

$booking_id = (int)$_GET['booking_id'];
$conn = getDBConnection();

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, r.room_type, r.room_number
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = ? AND b.user_id = ? AND b.status = 'pending' AND b.payment_status = 'pending'
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    redirect('bookings.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = sanitizeInput($_POST['payment_method']);
    $card_number = sanitizeInput($_POST['card_number']);
    $card_name = sanitizeInput($_POST['card_name']);
    $expiry_date = sanitizeInput($_POST['expiry_date']);
    $cvv = sanitizeInput($_POST['cvv']);
    
    if (empty($payment_method) || empty($card_number) || empty($card_name) || empty($expiry_date) || empty($cvv)) {
        $error = 'Please fill in all payment details';
    } else {
        // In a real application, you would integrate with a payment gateway here
        // For this example, we'll simulate a successful payment
        
        // Update booking status
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'confirmed', payment_status = 'paid'
            WHERE id = ?
        ");
        
        if ($stmt->execute([$booking_id])) {
            // Create payment record
            $stmt = $conn->prepare("
                INSERT INTO payments (booking_id, amount, payment_method, transaction_id, status)
                VALUES (?, ?, ?, ?, 'completed')
            ");
            
            $transaction_id = 'TRX' . time() . rand(1000, 9999);
            $stmt->execute([$booking_id, $booking['total_price'], $payment_method, $transaction_id]);
            
            $success = 'Payment successful! Your booking has been confirmed.';
        } else {
            $error = 'Payment failed. Please try again.';
        }
    }
}

include 'templates/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title mb-4">Payment Details</h2>
                    
                    <?php if ($error): ?>
                        <?php echo displayError($error); ?>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <?php echo displaySuccess($success); ?>
                        <div class="text-center mt-3">
                            <a href="bookings.php" class="btn btn-primary">View My Bookings</a>
                        </div>
                    <?php else: ?>
                        <div class="booking-summary mb-4">
                            <h4>Booking Summary</h4>
                            <p><strong>Room:</strong> <?php echo ucfirst($booking['room_type']); ?> Room (<?php echo $booking['room_number']; ?>)</p>
                            <p><strong>Check-in:</strong> <?php echo date('F j, Y', strtotime($booking['check_in_date'])); ?></p>
                            <p><strong>Check-out:</strong> <?php echo date('F j, Y', strtotime($booking['check_out_date'])); ?></p>
                            <p><strong>Total Amount:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
                        </div>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <?php foreach (getPaymentMethods() as $value => $label): ?>
                                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="card_number" class="form-label">Card Number</label>
                                <input type="text" class="form-control" id="card_number" name="card_number" 
                                       pattern="[0-9]{16}" maxlength="16" required>
                                <div class="invalid-feedback">
                                    Please enter a valid 16-digit card number
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="card_name" class="form-label">Name on Card</label>
                                <input type="text" class="form-control" id="card_name" name="card_name" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" id="expiry_date" name="expiry_date" 
                                           pattern="(0[1-9]|1[0-2])\/([0-9]{2})" placeholder="MM/YY" required>
                                    <div class="invalid-feedback">
                                        Please enter a valid expiry date (MM/YY)
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" name="cvv" 
                                           pattern="[0-9]{3,4}" maxlength="4" required>
                                    <div class="invalid-feedback">
                                        Please enter a valid CVV
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Pay Now</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format card number input
document.getElementById('card_number').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Format expiry date input
document.getElementById('expiry_date').addEventListener('input', function(e) {
    let value = this.value.replace(/[^0-9]/g, '');
    if (value.length >= 2) {
        value = value.slice(0,2) + '/' + value.slice(2);
    }
    this.value = value;
});

// Format CVV input
document.getElementById('cvv').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include 'templates/footer.php'; ?> 