import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-password-toggle]');
    if (!btn) return;

    const container = btn.closest('[data-password-field]') || btn.parentElement;
    if (!container) return;

    const input = container.querySelector('input');
    if (!input) return;

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';

    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');

    const icon = btn.querySelector('i');
    if (icon) {
        icon.classList.remove('ph-eye', 'ph-eye-slash');
        icon.classList.add(show ? 'ph-eye-slash' : 'ph-eye');
    }
});
