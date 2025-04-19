document.addEventListener('DOMContentLoaded', function() {
    const registrationForm = document.getElementById('registration-form');
    
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Get form fields
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const hobbies = document.getElementById('hobbies');
            const games = document.getElementById('games');
            
            // Clear previous error messages
            clearErrors();
            
            // Validate name
            if (name.value.trim() === '') {
                showError(name, 'Name is required');
                isValid = false;
            }
            
            // Validate email
            if (email.value.trim() === '') {
                showError(email, 'Email is required');
                isValid = false;
            } else if (!isValidEmail(email.value)) {
                showError(email, 'Please enter a valid email');
                isValid = false;
            }
            
            // Validate password
            if (password.value.trim() === '') {
                showError(password, 'Password is required');
                isValid = false;
            } else if (password.value.length < 6) {
                showError(password, 'Password must be at least 6 characters');
                isValid = false;
            }
            
            // Validate hobbies
            if (hobbies.value.trim() === '') {
                showError(hobbies, 'Please enter at least one hobby');
                isValid = false;
            }
            
            // Validate games
            if (games.value.trim() === '') {
                showError(games, 'Please enter at least one game');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    // Check roommate availability
    const roommateInput = document.getElementById('preferred_roommate');
    if (roommateInput) {
        roommateInput.addEventListener('blur', function() {
            if (roommateInput.value.trim() !== '') {
                checkRoommateAvailability(roommateInput.value);
            }
        });
    }
});

function showError(input, message) {
    const formGroup = input.parentElement;
    const errorElement = document.createElement('div');
    errorElement.className = 'error';
    errorElement.innerText = message;
    formGroup.appendChild(errorElement);
    input.classList.add('error-input');
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.error');
    const errorInputs = document.querySelectorAll('.error-input');
    
    errorElements.forEach(element => element.remove());
    errorInputs.forEach(input => input.classList.remove('error-input'));
}

function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

function checkRoommateAvailability(roommateName) {
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'check_roommate.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            const roommateInput = document.getElementById('preferred_roommate');
            const formGroup = roommateInput.parentElement;
            
            // Remove any existing status message
            const existingStatus = formGroup.querySelector('.roommate-status');
            if (existingStatus) {
                existingStatus.remove();
            }
            
            // Create status element
            const statusElement = document.createElement('div');
            statusElement.className = 'roommate-status';
            
            if (response.exists) {
                statusElement.innerText = 'Roommate found! You can be matched with this person.';
                statusElement.classList.add('success');
            } else {
                statusElement.innerText = 'Roommate not found. A new profile will be created for them.';
                statusElement.classList.add('error');
            }
            
            formGroup.appendChild(statusElement);
        }
    };
    
    xhr.send(`roommate_name=${encodeURIComponent(roommateName)}`);
}