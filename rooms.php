<?php
require_once 'include/functions.php';
include 'templates/header.php';

// Get room types for filter
$room_types = getRoomTypes();

// Get filter parameters
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$price_filter = isset($_GET['price']) ? (int)$_GET['price'] : 0;

// Build query
$query = "SELECT * FROM rooms WHERE status = 'available'";
$params = [];

if ($type_filter) {
    $query .= " AND room_type = ?";
    $params[] = $type_filter;
}

if ($price_filter > 0) {
    $query .= " AND price_per_night <= ?";
    $params[] = $price_filter;
}

$query .= " ORDER BY price_per_night ASC";

// Get rooms
$conn = getDBConnection();
$stmt = $conn->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Room Filters -->
<section class="py-4 bg-light">
    <div class="container">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label">Room Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <?php foreach ($room_types as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $type_filter === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="price" class="form-label">Maximum Price per Night</label>
                <input type="number" class="form-control" id="price" name="price" min="0" step="10" value="<?php echo $price_filter; ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </form>
    </div>
</section>

<!-- Rooms List -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">Available Rooms</h2>
        
        <?php if (empty($rooms)): ?>
            <div class="alert alert-info">
                No rooms available matching your criteria.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($rooms as $room): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card room-card" data-type="<?php echo $room['room_type']; ?>" data-price="<?php echo $room['price_per_night']; ?>">
                            <img src="assets/images/rooms/<?php echo $room['room_type']; ?>.jpg" class="card-img-top" alt="<?php echo $room['room_type']; ?> Room">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo ucfirst($room['room_type']); ?> Room</h5>
                                <p class="card-text"><?php echo $room['description']; ?></p>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-user-friends"></i> Capacity: <?php echo $room['capacity']; ?> persons</li>
                                    <li><i class="fas fa-bed"></i> <?php echo ucfirst($room['room_type']); ?> bed</li>
                                </ul>
                                <p class="room-price">TZS <?php echo number_format($room['price_per_night'], 2); ?> per night</p>
                                <a href="room_details.php?id=<?php echo $room['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'templates/footer.php'; ?> 