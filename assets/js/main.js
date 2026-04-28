// YBT Digital - Main JavaScript

// Theme Toggle
function toggleTheme() {
    const html = document.documentElement;
    const current = html.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    document.cookie = `theme=${next};path=/;max-age=31536000`;
    updateThemeIcons(next);
}

function updateThemeIcons(theme) {
    document.querySelectorAll('.theme-toggle i, .theme-toggle-sm i').forEach(icon => {
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    });
}

// Init theme icons on load
document.addEventListener('DOMContentLoaded', () => {
    const theme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeIcons(theme);
});

// FAQ Toggle
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', () => {
            const item = q.closest('.faq-item');
            item.classList.toggle('open');
        });
    });
});

// Toast Notifications
function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `custom-toast ${type}`;
    const iconMap = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };
    toast.innerHTML = `<i class="bi ${iconMap[type] || iconMap.info}"></i><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(30px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add to Cart (AJAX)
function addToCart(productId) {
    fetch('ajax_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&product_id=${productId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Product added to cart!', 'success');
            // Update cart badges
            document.querySelectorAll('.cart-badge, .bottom-cart-badge').forEach(badge => {
                badge.textContent = data.cartCount;
                badge.style.display = data.cartCount > 0 ? 'flex' : 'none';
            });
        } else {
            showToast(data.message || 'Error adding to cart', 'error');
            if (data.redirect) {
                setTimeout(() => window.location.href = data.redirect, 1000);
            }
        }
    })
    .catch(() => showToast('Something went wrong', 'error'));
}

// Remove from Cart (AJAX)
function removeFromCart(cartId) {
    if (!confirm('Remove this item from cart?')) return;
    fetch('ajax_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove&cart_id=${cartId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast('Item removed from cart', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Error removing item', 'error');
        }
    })
    .catch(() => showToast('Something went wrong', 'error'));
}

// Screenshot Gallery
function switchScreenshot(imgEl, mainImgId) {
    const mainImg = document.getElementById(mainImgId);
    if (mainImg) {
        mainImg.src = imgEl.src;
        document.querySelectorAll('.screenshot-gallery img').forEach(i => i.classList.remove('active'));
        imgEl.classList.add('active');
    }
}

// Admin sidebar toggle (mobile)
function toggleAdminSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.querySelector('.admin-overlay');
    if (sidebar) sidebar.classList.toggle('show');
    if (overlay) overlay.classList.toggle('show');
}

// Payment method selection
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.payment-method').forEach(pm => {
        pm.addEventListener('click', () => {
            document.querySelectorAll('.payment-method').forEach(p => p.classList.remove('selected'));
            pm.classList.add('selected');
            const input = pm.querySelector('input[type="radio"]');
            if (input) input.checked = true;
        });
    });
});

// Smooth scroll animations
document.addEventListener('DOMContentLoaded', () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeInUp');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.product-card, .testimonial-card, .stat-card, .order-card').forEach(el => {
        observer.observe(el);
    });
});
