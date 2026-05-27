function validatePhilippineNumber(value) {
    const cleaned = value.replace(/\s+/g, '');
    const regex = /^(09\d{9}|\+639\d{9})$/;
    return regex.test(cleaned);
}

function attachPhoneValidation(inputId, errorId) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (!input) return;

    input.addEventListener('keypress', function (e) {
        const char = String.fromCharCode(e.which);
        const allowedPlus = input.value.length === 0 && char === '+';
        const isDigit = /\d/.test(char);
        if (!isDigit && !allowedPlus) e.preventDefault();
    });

    input.addEventListener('paste', function (e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData)
            .getData('text')
            .replace(/\s+/g, '');
        input.value = pasted;
        triggerValidation(input, error);
    });

    input.addEventListener('input', function () {
        input.value = input.value.replace(/\s+/g, '');
        triggerValidation(input, error);
    });
}

function triggerValidation(input, error) {
    const val = input.value.trim();
    if (val === '') { resetState(input, error); return; }

    if (validatePhilippineNumber(val)) {
        input.classList.remove('input-error');
        input.classList.add('input-valid');
        error.textContent = '';
        error.style.display = 'none';
    } else {
        input.classList.remove('input-valid');
        input.classList.add('input-error');
        error.textContent = 'Contact number must be a valid Philippine mobile number. Use format: 09XXXXXXXXX or +639XXXXXXXXX.';
        error.style.display = 'block';
    }
}

function resetState(input, error) {
    input.classList.remove('input-valid', 'input-error');
    error.textContent = '';
    error.style.display = 'none';
}

function blockIfInvalid(formId, inputId) {
    const form = document.getElementById(formId);
    const input = document.getElementById(inputId);
    if (!form || !input) return;

    form.addEventListener('submit', function (e) {
        if (!validatePhilippineNumber(input.value.trim())) {
            e.preventDefault();
            input.focus();
        }
    });
}