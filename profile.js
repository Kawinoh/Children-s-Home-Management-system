document.getElementById('profileForm').addEventListener('submit', function(event) {
    // Prevent form submission if there are validation errors
    if (!validateForm()) {
        event.preventDefault();
    }
});

function validateForm() {
    let isValid = true;
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const dateOfBirth = document.getElementById('date_of_birth').value;
    const admissionDate = document.getElementById('admission_date').value;
    const guardianContact = document.getElementById('guardian_contact').value;
    const profilePicture = document.getElementById('profile_picture').files[0];
    const attendanceRate = document.getElementById('attendance_rate').value;

    // Example basic validation checks
    if (!firstName || !lastName || !dateOfBirth || !admissionDate || !guardianContact || !profilePicture) {
        alert("Please fill in all required fields.");
        isValid = false;
    }

    // Ensure attendance rate is a valid percentage
    if (attendanceRate < 0 || attendanceRate > 100) {
        alert("Please enter a valid attendance rate between 0 and 100.");
        isValid = false;
    }

    // Add more validation checks as needed

    return isValid;
}

// Image preview function (optional)
document.getElementById('profile_picture').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // You can add an img element somewhere in your HTML to preview the image
            const imgPreview = document.getElementById('profileImgPreview');
            imgPreview.src = e.target.result;
            imgPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
