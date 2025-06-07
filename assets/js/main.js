// Wait for the document to be ready
$(document).ready(function () {
   // Initialize tooltips
   var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
   var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
   });

   // Date picker initialization
   if ($.fn.datepicker) {
      $('.datepicker').datepicker({
         format: 'yyyy-mm-dd',
         autoclose: true,
         todayHighlight: true,
         startDate: new Date()
      });
   }

   // Form validation
   $('form').on('submit', function (e) {
      if (!this.checkValidity()) {
         e.preventDefault();
         e.stopPropagation();
      }
      $(this).addClass('was-validated');
   });

   // Room type filter
   $('#roomTypeFilter').on('change', function () {
      var selectedType = $(this).val();
      if (selectedType) {
         $('.room-card').hide();
         $('.room-card[data-type="' + selectedType + '"]').show();
      } else {
         $('.room-card').show();
      }
   });

   // Price range filter
   $('#priceRange').on('input', function () {
      var maxPrice = $(this).val();
      $('.room-card').each(function () {
         var price = parseFloat($(this).data('price'));
         if (price <= maxPrice) {
            $(this).show();
         } else {
            $(this).hide();
         }
      });
   });

   // Booking form date validation
   $('#checkInDate, #checkOutDate').on('change', function () {
      var checkIn = new Date($('#checkInDate').val());
      var checkOut = new Date($('#checkOutDate').val());

      if (checkIn && checkOut) {
         if (checkOut <= checkIn) {
            alert('Check-out date must be after check-in date');
            $('#checkOutDate').val('');
         }
      }
   });

   // Dynamic price calculation
   function calculateTotalPrice() {
      var checkIn = new Date($('#checkInDate').val());
      var checkOut = new Date($('#checkOutDate').val());
      var pricePerNight = parseFloat($('#pricePerNight').val());

      if (checkIn && checkOut && pricePerNight) {
         var nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
         var total = nights * pricePerNight;
         $('#totalPrice').text('TZS ' + total.toFixed(2));
      }
   }

   $('#checkInDate, #checkOutDate').on('change', calculateTotalPrice);

   // Image preview for file uploads
   $('#profileImage').on('change', function () {
      var file = this.files[0];
      if (file) {
         var reader = new FileReader();
         reader.onload = function (e) {
            $('#imagePreview').attr('src', e.target.result);
         }
         reader.readAsDataURL(file);
      }
   });

   // Password strength meter
   $('#password').on('input', function () {
      var password = $(this).val();
      var strength = 0;

      if (password.length >= 8) strength++;
      if (password.match(/[a-z]+/)) strength++;
      if (password.match(/[A-Z]+/)) strength++;
      if (password.match(/[0-9]+/)) strength++;
      if (password.match(/[^a-zA-Z0-9]+/)) strength++;

      var strengthText = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'];
      var strengthClass = ['danger', 'warning', 'info', 'primary', 'success'];

      $('#passwordStrength')
         .text(strengthText[strength - 1])
         .removeClass()
         .addClass('text-' + strengthClass[strength - 1]);
   });

   // Smooth scroll for anchor links
   $('a[href^="#"]').on('click', function (e) {
      e.preventDefault();
      var target = $(this.hash);
      if (target.length) {
         $('html, body').animate({
            scrollTop: target.offset().top - 70
         }, 1000);
      }
   });

   // Mobile menu toggle
   $('.navbar-toggler').on('click', function () {
      $('.navbar-collapse').toggleClass('show');
   });

   // Close mobile menu when clicking outside
   $(document).on('click', function (e) {
      if (!$(e.target).closest('.navbar').length) {
         $('.navbar-collapse').removeClass('show');
      }
   });
}); 