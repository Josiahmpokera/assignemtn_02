<?php
require_once '../include/functions.php';

if (!isAdmin()) {
    redirect('../login.php');
}

include '../templates/header.php';

$conn = getDBConnection();

// Handle room deletion
if (isset($_POST['delete_room'])) {
    $room_id = (int)$_POST['room_id'];
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$room_id]);
    $success_message = "Room deleted successfully.";
}

// Handle room status update
if (isset($_POST['update_status'])) {
    $room_id = (int)$_POST['room_id'];
    $new_status = sanitizeInput($_POST['new_status']);
    $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $room_id]);
    $success_message = "Room status updated successfully.";
}

// Get all rooms with their amenities
$stmt = $conn->query("
    SELECT r.*, GROUP_CONCAT(ra.amenity_name) as amenities
    FROM rooms r
    LEFT JOIN room_amenities ra ON r.id = ra.room_id
    GROUP BY r.id
    ORDER BY r.room_type, r.room_number
");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Rooms</h2>
        <a href="add_room.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Room
        </a>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Room Number</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Bed Type</th>
                            <th>Price/Night</th>
                            <th>Status</th>
                            <th>Amenities</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo $room['room_number']; ?></td>
                                <td><?php echo ucfirst($room['room_type']); ?></td>
                                <td><?php echo $room['capacity']; ?> persons</td>
                                <td><?php echo ucfirst($room['bed_type']); ?></td>
                                <td>TZS <?php echo number_format($room['price_per_night'], 2); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="new_status" class="form-select form-select-sm" 
                                                onchange="this.form.submit()" 
                                                style="width: 120px;">
                                            <option value="available" <?php echo $room['status'] === 'available' ? 'selected' : ''; ?>>
                                                Available
                                            </option>
                                            <option value="occupied" <?php echo $room['status'] === 'occupied' ? 'selected' : ''; ?>>
                                                Occupied
                                            </option>
                                            <option value="maintenance" <?php echo $room['status'] === 'maintenance' ? 'selected' : ''; ?>>
                                                Maintenance
                                            </option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <?php 
                                    if ($room['amenities']) {
                                        $amenities = explode(',', $room['amenities']);
                                        foreach ($amenities as $amenity) {
                                            echo '<span class="badge bg-info me-1">' . ucfirst($amenity) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">No amenities</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_room.php?id=<?php echo $room['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal<?php echo $room['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Delete Confirmation Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo $room['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete Room <?php echo $room['room_number']; ?>?
                                                    This action cannot be undone.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                                        <button type="submit" name="delete_room" class="btn btn-danger">Delete</button>
                                                    </form>
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