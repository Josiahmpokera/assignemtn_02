<?php
require_once 'include/functions.php';
include 'templates/header.php';

// Get featured rooms
$conn = getDBConnection();
$stmt = $conn->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY price_per_night ASC LIMIT 3");
$featured_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="display-4 text-center pt-5">Welcome to Our Luxury Hotel</h1>
        <p class="lead text-center">Experience the perfect blend of comfort and elegance</p>
        <p class="text-center">
        <a href="rooms.php" class="btn btn-primary btn-lg text-center">Book Now</a>
        </p>
    </div>
</section>

<!-- Featured Rooms -->
<section class="py-5">
    <div class="container">
    <hr>
        <h2 class="text-center mb-4">Featured Rooms</h2>
        <div class="row">
            <?php foreach ($featured_rooms as $room): ?>
                <div class="col-md-4">
                    <div class="card room-card">
                        <img src="assets/images/rooms/<?php echo $room['room_type']; ?>.jpg" class="card-img-top" alt="<?php echo $room['room_type']; ?> Room">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo ucfirst($room['room_type']); ?> Room</h5>
                            <p class="card-text"><?php echo $room['description']; ?></p>
                            <p class="room-price">TZS<?php echo number_format($room['price_per_night'], 2); ?> per night</p>
                            <a href="room_details.php?id=<?php echo $room['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4">Why Choose Us</h2>
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <i class="fas fa-concierge-bell fa-3x mb-3" style="color: #8B4513"></i>
                <h4>24/7 Service</h4>
                <p>Round-the-clock concierge and room service for your convenience</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <i class="fas fa-utensils fa-3x mb-3" style="color: #8B4513"></i>
                <h4>Fine Dining</h4>
                <p>Experience exquisite cuisine at our award-winning restaurants</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <i class="fas fa-spa fa-3x mb-3 " style="color: #8B4513"></i>
                <h4>Spa & Wellness</h4>
                <p>Relax and rejuvenate at our world-class spa facilities</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">What Our Guests Say</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"Amazing experience! The staff was incredibly attentive and the rooms were immaculate."</p>
                        <footer class="blockquote-footer">John Doe</footer>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"The best hotel I've ever stayed at. The amenities are top-notch and the service is exceptional."</p>
                        <footer class="blockquote-footer">Jane Smith</footer>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"Perfect location and beautiful rooms. Will definitely be coming back!"</p>
                        <footer class="blockquote-footer">Mike Johnson</footer>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'templates/footer.php'; ?>
