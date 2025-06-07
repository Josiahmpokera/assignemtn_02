    </div>
    <footer class="bg-dark text-light mt-5 py-3">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p>
                        <i class="fas fa-phone"></i> +1 234 567 890<br>
                        <i class="fas fa-envelope"></i> info@hotel.com<br>
                        <i class="fas fa-map-marker-alt"></i> 123 Hotel Street, City
                    </p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/hotel-management-system/about.php" class="text-light">About Us</a></li>
                        <li><a href="/hotel-management-system/rooms.php" class="text-light">Rooms</a></li>
                        <li><a href="/hotel-management-system/contact.php" class="text-light">Contact</a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li><a href="/hotel-management-system/admin/login.php" class="text-light">Admin Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Follow Us</h5>
                    <div class="social-links">
                        <a href="#" class="text-light me-2"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="mt-3">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Hotel Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="/hotel-management-system/assets/js/main.js"></script>
</body>
</html>
