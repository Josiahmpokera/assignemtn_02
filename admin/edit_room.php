<?php
require_once '../include/functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

include '../templates/header.php';

$conn = getDBConnection();

// Get room ID from URL
$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get room details
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    redirect('manage_rooms.php');
}

// Get room amenities
$stmt = $conn->prepare("SELECT amenity_name FROM room_amenities WHERE room_id = ?");
$stmt->execute([$room_id]);
$room_amenities = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get all available amenities
$stmt = $conn->query("SELECT * FROM amenities ORDER BY name");
$amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $errors = [];
    if (empty($room_number)) $errors[] = "Room number is required.";
    if (empty($room_type)) $errors[] = "Room type is required.";
    if ($capacity <= 0) $errors[] = "Capacity must be greater than 0.";
    if (empty($bed_type)) $errors[] = "Bed type is required.";
    if ($price_per_night <= 0) $errors[] = "Price per night must be greater than 0.";
    
    // Check if room number already exists (excluding current room)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ? AND id != ?");
    $stmt->execute([$room_number, $room_id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Room number already exists.";
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Update room
            $stmt = $conn->prepare("
                UPDATE rooms 
                SET room_number = ?, room_type = ?, capacity = ?, bed_type = ?, 
                    price_per_night = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $room_number, $room_type, $capacity, $bed_type, 
                $price_per_night, $description, $room_id
            ]);
            
            // Update room amenities
            $stmt = $conn->prepare("DELETE FROM room_amenities WHERE room_id = ?");
            $stmt->execute([$room_id]);
            
            if (!empty($selected_amenities)) {
                $stmt = $conn->prepare("INSERT INTO room_amenities (room_id, amenity_name) VALUES (?, ?)");
                foreach ($selected_amenities as $amenity) {
                    $stmt->execute([$room_id, $amenity]);
                }
            }
            
            $conn->commit();
            $success_message = "Room updated successfully.";
            
            // Refresh room data
            $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Refresh room amenities
            $stmt = $conn->prepare("SELECT amenity_name FROM room_amenities WHERE room_id = ?");
            $stmt->execute([$room_id]);
            $room_amenities = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "An error occurred while updating the room. Please try again.";
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Room</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_number" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="room_number" name="room_number" 
                                       value="<?php echo htmlspecialchars($room['room_number']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="room_type" class="form-label">Room Type</label>
                                <select class="form-select" id="room_type" name="room_type" required>
                                    <option value="">Select Room Type</option>
                                    <option value="standard" <?php echo $room['room_type'] === 'standard' ? 'selected' : ''; ?>>
                                        Standard
                                    </option>
                                    <option value="deluxe" <?php echo $room['room_type'] === 'deluxe' ? 'selected' : ''; ?>>
                                        Deluxe
                                    </option>
                                    <option value="suite" <?php echo $room['room_type'] === 'suite' ? 'selected' : ''; ?>>
                                        Suite
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="capacity" class="form-label">Capacity (Persons)</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" 
                                       value="<?php echo $room['capacity']; ?>" min="1" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="bed_type" class="form-label">Bed Type</label>
                                <select class="form-select" id="bed_type" name="bed_type" required>
                                    <option value="">Select Bed Type</option>
                                    <option value="single" <?php echo $room['bed_type'] === 'single' ? 'selected' : ''; ?>>
                                        Single
                                    </option>
                                    <option value="double" <?php echo $room['bed_type'] === 'double' ? 'selected' : ''; ?>>
                                        Double
                                    </option>
                                    <option value="queen" <?php echo $room['bed_type'] === 'queen' ? 'selected' : ''; ?>>
                                        Queen
                                    </option>
                                    <option value="king" <?php echo $room['bed_type'] === 'king' ? 'selected' : ''; ?>>
                                        King
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price_per_night" class="form-label">Price per Night (TZS)</label>
                            <input type="number" class="form-control" id="price_per_night" name="price_per_night" 
                                   value="<?php echo $room['price_per_night']; ?>" min="0" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                echo htmlspecialchars($room['description']); 
                            ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Amenities</label>
                            <div class="row">
                                <?php foreach ($amenities as $amenity): ?>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="amenities[]" 
                                                   value="<?php echo $amenity['name']; ?>" 
                                                   id="amenity<?php echo $amenity['id']; ?>"
                                                   <?php echo in_array($amenity['name'], $room_amenities) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="amenity<?php echo $amenity['id']; ?>">
                                                <?php echo ucfirst($amenity['name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="manage_rooms.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Room</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?> 