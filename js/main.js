document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }

    // Seat selection functionality
    const seatContainer = document.querySelector('.seat-container');
    if (seatContainer) {
        seatContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('seat') && !e.target.classList.contains('occupied')) {
                e.target.classList.toggle('selected');
                updateSelectedCount();
            }
        });
    }

    // Update total price based on selected seats
    function updateSelectedCount() {
        const selectedSeats = document.querySelectorAll('.seat.selected');
        const seatsCount = selectedSeats.length;
        const ticketPrice = document.querySelector('#ticket-price')?.value || 0;
        const total = seatsCount * ticketPrice;

        document.querySelector('.selected-count').textContent = seatsCount;
        document.querySelector('.total-price').textContent = total;

        // Update hidden form fields
        const selectedSeatsInput = document.querySelector('#selected-seats');
        if (selectedSeatsInput) {
            const seatsArray = [...selectedSeats].map(seat => seat.dataset.seatId);
            selectedSeatsInput.value = JSON.stringify(seatsArray);
        }
    }

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'error');
            }
        });
    });

    // Alert message function
    function showAlert(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;

        const container = document.querySelector('main');
        container.insertBefore(alertDiv, container.firstChild);

        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Image lazy loading
    const images = document.querySelectorAll('img[data-src]');
    const imageOptions = {
        threshold: 0,
        rootMargin: '0px 0px 50px 0px'
    };

    function loadImage(image) {
        image.src = image.dataset.src;
        image.removeAttribute('data-src');
    }

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadImage(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, imageOptions);

        images.forEach(img => imageObserver.observe(img));
    } else {
        images.forEach(img => loadImage(img));
    }
});
