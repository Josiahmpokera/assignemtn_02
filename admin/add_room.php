<?php
require_once '../include/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isAdmin()) {
    redirect('../login.php');
}

include '../templates/header.php';

try {
    $conn = getDBConnection();
    
    // Create amenities table if it doesn't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS amenities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE
        )
    ");
    
    // Add bed_type column to rooms table if it doesn't exist
    $conn->exec("
        ALTER TABLE rooms 
        ADD COLUMN IF NOT EXISTS bed_type VARCHAR(20) DEFAULT 'double' AFTER capacity
    ");
    
    // Check if amenities table is empty and insert default amenities
    $stmt = $conn->query("SELECT COUNT(*) FROM amenities");
    if ($stmt->fetchColumn() == 0) {
        $default_amenities = [
            'Wi-Fi',
            'Air Conditioning',
            'TV',
            'Mini Bar',
            'Safe',
            'Desk',
            'Balcony',
            'Ocean View',
            'Room Service',
            'Coffee Maker',
            'Hair Dryer',
            'Iron',
            'Telephone',
            'Bathrobe',
            'Slippers'
        ];
        
        $stmt = $conn->prepare("INSERT INTO amenities (name) VALUES (?)");
        foreach ($default_amenities as $amenity) {
            $stmt->execute([$amenity]);
        }
    }
    
    // Get all available amenities
    $stmt = $conn->query("SELECT * FROM amenities ORDER BY name");
    $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = sanitizeInput($_POST['room_number']);
    $room_type = sanitizeInput($_POST['room_type']);
    $capacity = (int)$_POST['capacity'];
    $bed_type = sanitizeInput($_POST['bed_type']);
    $price_per_night = (float)$_POST['price_per_night'];
    $description = sanitizeInput($_POST['description']);
    $selected_amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
    
    // Validate required fields
    if (empty($room_number) || empty($room_type) || empty($bed_type) || $price_per_night <= 0) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Check if room number already exists
        $stmt = $conn->prepare("SELECT id FROM rooms WHERE room_number = ?");
        $stmt->execute([$room_number]);
        if ($stmt->rowCount() > 0) {
            $error_message = "Room number already exists.";
        } else {
            try {
                $conn->beginTransaction();
                
                // Insert room
                $stmt = $conn->prepare("
                    INSERT INTO rooms (room_number, room_type, capacity, bed_type, price_per_night, description, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'available')
                ");
                $stmt->execute([$room_number, $room_type, $capacity, $bed_type, $price_per_night, $description]);
                $room_id = $conn->lastInsertId();
                
                // Insert room amenities
                if (!empty($selected_amenities)) {
                    $stmt = $conn->prepare("INSERT INTO room_amenities (room_id, amenity_name) VALUES (?, ?)");
                    foreach ($selected_amenities as $amenity) {
                        $stmt->execute([$room_id, $amenity]);
                    }
                }
                
                $conn->commit();
                $success_message = "Room added successfully.";
                
                // Clear form data
                $room_number = $room_type = $bed_type = $description = '';
                $capacity = 1;
                $price_per_night = 0;
                $selected_amenities = [];
                
            } catch (Exception $e) {
                $conn->rollBack();
                $error_message = "An error occurred while adding the room: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add New Room</h2>
        <a href="manage_rooms.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Rooms
        </a>
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
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Room Number *</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" 
                                   value="<?php echo isset($room_number) ? $room_number : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="room_type" class="form-label">Room Type *</label>
                            <select class="form-select" id="room_type" name="room_type" required>
                                <option value="">Select Room Type</option>
                                <option value="standard" <?php echo (isset($room_type) && $room_type === 'standard') ? 'selected' : ''; ?>>
                                    Standard
                                </option>
                                <option value="deluxe" <?php echo (isset($room_type) && $room_type === 'deluxe') ? 'selected' : ''; ?>>
                                    Deluxe
                                </option>
                                <option value="suite" <?php echo (isset($room_type) && $room_type === 'suite') ? 'selected' : ''; ?>>
                                    Suite
                                </option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity *</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" 
                                   value="<?php echo isset($capacity) ? $capacity : 1; ?>" min="1" max="10" required>
                            <div class="form-text">Maximum number of persons</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bed_type" class="form-label">Bed Type *</label>
                            <select class="form-select" id="bed_type" name="bed_type" required>
                                <option value="">Select Bed Type</option>
                                <option value="single" <?php echo (isset($bed_type) && $bed_type === 'single') ? 'selected' : ''; ?>>
                                    Single
                                </option>
                                <option value="double" <?php echo (isset($bed_type) && $bed_type === 'double') ? 'selected' : ''; ?>>
                                    Double
                                </option>
                                <option value="queen" <?php echo (isset($bed_type) && $bed_type === 'queen') ? 'selected' : ''; ?>>
                                    Queen
                                </option>
                                <option value="king" <?php echo (isset($bed_type) && $bed_type === 'king') ? 'selected' : ''; ?>>
                                    King
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="price_per_night" class="form-label">Price per Night (TZS) *</label>
                            <input type="number" class="form-control" id="price_per_night" name="price_per_night" 
                                   value="<?php echo isset($price_per_night) ? $price_per_night : ''; ?>" 
                                   min="0" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? $description : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Amenities</label>
                            <div class="row">
                                <?php if (!empty($amenities)): ?>
                                    <?php foreach ($amenities as $amenity): ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="amenities[]" 
                                                       value="<?php echo $amenity['name']; ?>" 
                                                       id="amenity_<?php echo $amenity['id']; ?>"
                                                       <?php echo (isset($selected_amenities) && in_array($amenity['name'], $selected_amenities)) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="amenity_<?php echo $amenity['id']; ?>">
                                                    <?php echo ucfirst($amenity['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <p class="text-muted">No amenities available.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Room
                    </button>
                    <a href="manage_rooms.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?> 