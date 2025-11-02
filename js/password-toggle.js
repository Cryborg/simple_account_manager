/**
 * Toggle password visibility
 * @param {string} inputId - The ID of the password input field
 */
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.toggle-password');
    const icon = button.querySelector('.eye-icon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '👁️‍🗨️';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}
