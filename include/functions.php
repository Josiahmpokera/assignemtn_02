<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStaff() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function displayError($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

function displaySuccess($message) {
    return "<div class='alert alert-success'>$message</div>";
}

function formatDate($date) {
    return date('Y-m-d', strtotime($date));
}

function calculateTotalPrice($price_per_night, $check_in, $check_out) {
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $interval = $check_in_date->diff($check_out_date);
    $nights = $interval->days;
    return $price_per_night * $nights;
}

function getRoomTypes() {
    return [
        'standard' => 'Standard Room',
        'deluxe' => 'Deluxe Room',
        'suite' => 'Suite',
        'presidential' => 'Presidential Suite'
    ];
}

function getPaymentMethods() {
    return [
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer'
    ];
}
?>
