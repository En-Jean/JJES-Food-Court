// =====================================================
// JJES Food Court - FINAL JavaScript (Production Ready)
// =====================================================

// Global Variables
let currentUser = null;
let cart = [];
let menuItems = [];
let currentOrderType = 'dine-in';
let currentPaymentMethod = 'cash';

// 🔧 API URL - Use relative path for localhost & live server
const API_URL =  'http://localhost/JJES-FOOD-COURT/backend';
// OR use absolute for testing: 'http://localhost/JJES-FOOD-COURT/backend'

// =====================================================
// INITIALIZATION
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ JJES Food Court Loaded');
    console.log('🔍 API_URL:', API_URL);
    
    checkAuthStatus();
    loadCart();
    loadMenuItems();
    showSection('landingSection');
    setupEventListeners();
});

function setupEventListeners() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLogin();
        });
    }
    
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleRegister();
        });
    }
    
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
}

// =====================================================
// AUTHENTICATION - UPDATED NAVBAR LOGIC
// =====================================================
function checkAuthStatus() {
    const user = localStorage.getItem('currentUser');
    if (user) {
        try {
            currentUser = JSON.parse(user);
        } catch (e) {
            console.error('Failed to parse user:', e);
            localStorage.removeItem('currentUser');
            currentUser = null;
        }
    }
    // Always call to set correct initial state
    updateNavbar();
}

function updateNavbar() {
    const loginNavBtn = document.getElementById('loginNavBtn');
    const logoutNavBtn = document.getElementById('logoutNavBtn');
    const userGreeting = document.getElementById('userGreeting');
    
    if (currentUser) {
        // ✅ User is logged in: Show Logout + Greeting, Hide Login
        if (loginNavBtn) loginNavBtn.style.display = 'none';
        if (logoutNavBtn) logoutNavBtn.style.display = 'flex';
        if (userGreeting) {
            userGreeting.textContent = `Hello, ${currentUser.name}!`;
            userGreeting.style.display = 'block';
        }
    } else {
        // ❌ User is NOT logged in: Show Login, Hide Logout + Greeting
        if (loginNavBtn) loginNavBtn.style.display = 'flex';
        if (logoutNavBtn) logoutNavBtn.style.display = 'none';
        if (userGreeting) userGreeting.style.display = 'none';
    }
}

function handleLogin() {
    const email = document.getElementById('loginEmail')?.value.trim();
    const password = document.getElementById('loginPassword')?.value;
    const alertDiv = document.getElementById('loginAlert');
    const loginBtn = document.getElementById('loginBtn');
    
    if (!email || !password) {
        showAlert(alertDiv, 'Please fill in all fields', 'error');
        return;
    }
    
    if (!loginBtn) return;
    
    const originalText = loginBtn.innerHTML;
    loginBtn.disabled = true;
    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
    
    fetch(`${API_URL}/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            currentUser = data.user;
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            updateNavbar();
            showToast(`Welcome back, ${currentUser.name}!`);
            showSection('menuSection');
        } else {
            showAlert(alertDiv, data.message || 'Invalid credentials', 'error');
        }
    })
    .catch(error => {
        console.error('Login error:', error);
        showAlert(alertDiv, 'Connection error. Is server running?', 'error');
    })
    .finally(() => {
        loginBtn.disabled = false;
        loginBtn.innerHTML = originalText;
    });
}

function handleRegister() {
    const name = document.getElementById('signupName')?.value.trim();
    const email = document.getElementById('signupEmail')?.value.trim();
    const password = document.getElementById('signupPassword')?.value;
    const contact = document.getElementById('signupContact')?.value.trim();
    const alertDiv = document.getElementById('signupAlert');
    const signupBtn = document.getElementById('signupBtn');
    
    if (!name || !email || !password || !contact) {
        showAlert(alertDiv, 'Please fill in all fields', 'error');
        return;
    }
    
    if (password.length < 6) {
        showAlert(alertDiv, 'Password must be 6+ characters', 'error');
        return;
    }
    
    if (!signupBtn) return;
    
    const originalText = signupBtn.innerHTML;
    signupBtn.disabled = true;
    signupBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    fetch(`${API_URL}/register.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password, contact })
    })
    .then(async response => {
        const ct = response.headers.get('content-type');
        if (!ct || !ct.includes('application/json')) throw new Error('Non-JSON');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast('Account created! Please login.');
            showSection('loginSection');
        } else {
            showAlert(alertDiv, data.message || 'Registration failed', 'error');
        }
    })
    .catch(error => {
        console.error('Register error:', error);
        showAlert(alertDiv, 'Connection error', 'error');
    })
    .finally(() => {
        signupBtn.disabled = false;
        signupBtn.innerHTML = originalText;
    });
}

function handleLogout() {
    if (confirm('Logout?')) {
        localStorage.removeItem('currentUser');
        currentUser = null;
        cart = [];
        saveCart();
        updateNavbar();
        showToast('Logged out');
        showSection('landingSection');
    }
}

// =====================================================
// MENU FUNCTIONS
// =====================================================
function loadMenuItems() {
    const loadingDiv = document.getElementById('menuLoading');
    const menuGrid = document.getElementById('menuGrid');
    
    console.log('📡 Fetching from:', `${API_URL}/get_menu.php`);
    
    if (loadingDiv) loadingDiv.style.display = 'block';
    if (menuGrid) menuGrid.innerHTML = '';
    
    fetch(`${API_URL}/get_menu.php`)
    .then(async response => {
        console.log('📥 Status:', response.status);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const ct = response.headers.get('content-type');
        if (!ct || !ct.includes('application/json')) {
            throw new Error('Server returned non-JSON');
        }
        return response.json();
    })
    .then(data => {
        console.log('📦 Menu data:', data);
        
        if (data?.success && Array.isArray(data.data) && data.data.length > 0) {
            menuItems = data.data;
            renderMenuItems(menuItems);
            renderCategoryFilters();
        } else {
            console.warn('⚠️ No menu items:', data);
            if (menuGrid) menuGrid.innerHTML = '<p class="text-center">No menu items found</p>';
        }
    })
    .catch(error => {
        console.error('❌ Menu error:', error);
        if (menuGrid) {
            menuGrid.innerHTML = `<p class="text-center" style="color:var(--error)">Error: ${error.message}</p>`;
        }
    })
    .finally(() => {
        if (loadingDiv) loadingDiv.style.display = 'none';
    });
}

function renderMenuItems(items) {
    const menuGrid = document.getElementById('menuGrid');
    if (!menuGrid) return;
    
    if (!items || items.length === 0) {
        menuGrid.innerHTML = '<p class="text-center">No menu items available</p>';
        return;
    }
    
    menuGrid.innerHTML = items.map(item => `
        <div class="menu-item-card" data-category="${item.category || 'Other'}">
            <div class="menu-item-image">
                <img src="${item.image_url || 'images/default.png'}" 
                     alt="${item.name}" 
                     onerror="this.src='images/default.png'">
            </div>
            <div class="menu-item-info">
                <h3 class="menu-item-name">${item.name}</h3>
                <p class="menu-item-description">${item.description || ''}</p>
                <div class="menu-item-footer">
                    <span class="menu-item-price">₱${parseFloat(item.price || 0).toFixed(2)}</span>
                    <button class="btn-add-to-cart" 
                            onclick="addToCart(${item.id})" 
                            ${item.is_available <= 0 ? 'disabled' : ''}>
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function renderCategoryFilters() {
    const filtersContainer = document.getElementById('categoryFilters');
    if (!filtersContainer || !menuItems?.length) return;
    
    const categories = ['All', ...new Set(menuItems.map(item => item.category).filter(Boolean))];
    
    filtersContainer.innerHTML = categories.map(cat => `
        <button class="filter-btn ${cat === 'All' ? 'active' : ''}" 
                data-category="${cat}" 
                onclick="filterMenu('${cat}')">
            ${cat}
        </button>
    `).join('');
}

function filterMenu(category) {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.category === category);
    });
    
    const filtered = category === 'All' 
        ? menuItems 
        : menuItems.filter(item => item.category === category);
    
    renderMenuItems(filtered);
}

// =====================================================
// CART FUNCTIONS
// =====================================================
function loadCart() {
    try {
        const saved = localStorage.getItem('jjes_cart');
        cart = saved ? JSON.parse(saved) : [];
    } catch (e) {
        console.error('Failed to load cart:', e);
        cart = [];
    }
    updateCartCount();
}

function saveCart() {
    try {
        localStorage.setItem('jjes_cart', JSON.stringify(cart));
    } catch (e) {
        console.error('Failed to save cart:', e);
    }
    updateCartCount();
}

function updateCartCount() {
    const badge = document.getElementById('cartBadge');
    if (badge) {
        const total = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
        badge.textContent = total;
        badge.style.display = total > 0 ? 'block' : 'none';
    }
}

function addToCart(itemId) {
    const item = menuItems.find(m => m.id == itemId);
    if (!item) {
        showToast('Item not found');
        return;
    }
    if (item.is_available <= 0) {
        showToast('Sorry, out of stock!');
        return;
    }
    
    const existing = cart.find(c => c.id == itemId);
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            id: item.id,
            name: item.name,
            price: parseFloat(item.price || 0),
            image_url: item.image_url,
            quantity: 1
        });
    }
    
    saveCart();
    showToast(`Added ${item.name} to cart!`);
}

function renderCart() {
    const container = document.getElementById('cartItemsContainer');
    const totalEl = document.getElementById('cartTotal');
    const emptyMsg = document.getElementById('emptyCartMsg');
    const cartContent = document.getElementById('cartContent');
    
    if (!container) return;
    
    // Empty cart
    if (!cart.length) {
        emptyMsg?.classList.remove('hidden');
        cartContent?.classList.remove('visible');
        container.innerHTML = '';
        if (totalEl) totalEl.textContent = '₱0.00';
        return;
    }
    
    // Has items
    emptyMsg?.classList.add('hidden');
    cartContent?.classList.add('visible');
    
    let total = 0;
    container.innerHTML = cart.map(item => {
        const itemTotal = (item.price || 0) * (item.quantity || 1);
        total += itemTotal;
        return `
            <div class="cart-item">
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <p>₱${(item.price || 0).toFixed(2)}</p>
                </div>
                <div class="cart-item-controls">
                    <button onclick="updateQuantity(${item.id}, -1)" title="Decrease">−</button>
                    <span>${item.quantity || 1}</span>
                    <button onclick="updateQuantity(${item.id}, 1)" title="Increase">+</button>
                    <button class="remove-btn" onclick="removeFromCart(${item.id})" title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>`;
    }).join('');
    
    if (totalEl) totalEl.textContent = `₱${total.toFixed(2)}`;
}

function updateQuantity(itemId, change) {
    const item = cart.find(c => c.id == itemId);
    if (item) {
        item.quantity = (item.quantity || 1) + change;
        if (item.quantity <= 0) {
            removeFromCart(itemId);
        } else {
            saveCart();
            renderCart();
        }
    }
}

function removeFromCart(itemId) {
    cart = cart.filter(c => c.id != itemId);
    saveCart();
    renderCart();
    showToast('Item removed');
}

function clearCart() {
    if (confirm('Clear all items?')) {
        cart = [];
        saveCart();
        renderCart();
        showToast('Cart cleared');
    }
}

function proceedToCheckout() {
    if (!currentUser) {
        showToast('Please login to checkout');
        showSection('loginSection');
        return;
    }
    if (!cart.length) {
        showToast('Cart is empty');
        return;
    }
    renderCheckout();
    showSection('checkoutSection');
}

// =====================================================
// CHECKOUT & PAYMENT - WITH COD SUPPORT
// =====================================================
function togglePaymentDetails(method) {
    currentPaymentMethod = method;
    
    document.querySelectorAll('.payment-detail').forEach(el => {
        el.classList.remove('active');
        el.classList.add('hidden');
    });
    
    const selected = document.getElementById(`${method}Details`);
    if (selected) {
        selected.classList.remove('hidden');
        selected.classList.add('active');
    }
}

function setOrderType(type) {
    currentOrderType = type;
    
    document.getElementById('dineInBtn')?.classList.toggle('active', type === 'dine-in');
    document.getElementById('deliveryBtn')?.classList.toggle('active', type === 'delivery');
    
    const tableField = document.getElementById('tableField');
    const addressField = document.getElementById('addressField');
    const tableInput = document.getElementById('checkoutTable');
    const addressInput = document.getElementById('checkoutAddress');
    
    if (type === 'dine-in') {
        tableField?.classList.remove('hidden');
        addressField?.classList.add('hidden');
        tableInput?.setAttribute('required', 'required');
        addressInput?.removeAttribute('required');
    } else {
        tableField?.classList.add('hidden');
        addressField?.classList.remove('hidden');
        tableInput?.removeAttribute('required');
        addressInput?.setAttribute('required', 'required');
    }
}

function renderCheckout() {
    const list = document.getElementById('checkoutItemsList');
    const totalEl = document.getElementById('checkoutTotal');
    if (!list) return;
    
    let subtotal = 0;
    list.innerHTML = cart.map(item => {
        const itemTotal = (item.price || 0) * (item.quantity || 1);
        subtotal += itemTotal;
        return `<div class="checkout-item">
            <span>${item.name} × ${item.quantity || 1}</span>
            <span>₱${itemTotal.toFixed(2)}</span>
        </div>`;
    }).join('');
    
    const total = subtotal + 10; // service fee
    if (totalEl) totalEl.textContent = `₱${total.toFixed(2)}`;
    
    togglePaymentDetails('cash');
}

function getPaymentReference(method) {
    const refs = {
        gcash: document.getElementById('gcashRef')?.value.trim(),
        maya: document.getElementById('mayaRef')?.value.trim(),
        bank_transfer: document.getElementById('bankRef')?.value.trim()
    };
    return refs[method] || null;
}

// 🔧 UPDATED: Accept 'cash_on_delivery' as valid
function validatePayment(method) {
    // Cash and COD don't need reference numbers
    if (method === 'cash' || method === 'cash_on_delivery') {
        return { valid: true };
    }
    
    const subtotal = cart.reduce((s, i) => s + (i.price || 0) * (i.quantity || 1), 0);
    const ref = getPaymentReference(method);
    
    if (subtotal > 500 && !ref) {
        return { valid: false, message: `Please enter ${method.toUpperCase()} reference for orders over ₱500` };
    }
    return { valid: true };
}

function handlePlaceOrder() {
    const name = document.getElementById('checkoutName')?.value.trim();
    const contact = document.getElementById('checkoutContact')?.value.trim();
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';
    const alertDiv = document.getElementById('checkoutAlert');
    const btn = document.getElementById('placeOrderBtn');
    
    if (!name || !contact) {
        showAlert(alertDiv, 'Please fill all required fields', 'error');
        return;
    }
    
    if (currentOrderType === 'dine-in') {
        const table = document.getElementById('checkoutTable')?.value.trim();
        if (!table) {
            showAlert(alertDiv, 'Please enter table number', 'error');
            return;
        }
    } else {
        const address = document.getElementById('checkoutAddress')?.value.trim();
        if (!address) {
            showAlert(alertDiv, 'Please enter delivery address', 'error');
            return;
        }
    }
    
    const paymentCheck = validatePayment(paymentMethod);
    if (!paymentCheck.valid) {
        showAlert(alertDiv, paymentCheck.message, 'error');
        return;
    }
    
    if (!btn) return;
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    const subtotal = cart.reduce((s, i) => s + (i.price || 0) * (i.quantity || 1), 0);
    
    const orderData = {
        user_id: currentUser?.id ?? null,
        customer_name: name,
        contact: contact,
        order_type: currentOrderType,
        table_number: currentOrderType === 'dine-in' ? document.getElementById('checkoutTable')?.value : null,
        address: currentOrderType === 'delivery' ? document.getElementById('checkoutAddress')?.value : null,
        payment_method: paymentMethod,
        payment_reference: getPaymentReference(paymentMethod),
        total_price: subtotal + 10,
        cart_items: cart
    };
    
    console.log('📤 Sending:', orderData);
    
    fetch(`${API_URL}/place_order.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    })
    .then(async response => {
        const ct = response.headers.get('content-type');
        if (!ct || !ct.includes('application/json')) throw new Error('Non-JSON');
        return response.json();
    })
    .then(data => {
        console.log('📥 Response:', data);
        
        if (data.success) {
            document.getElementById('confirmedOrderId').textContent = data.order_id || '---';
            
            // 🔧 UPDATED: Handle COD in confirmation message
            let paymentMsg = '';
            if (paymentMethod === 'cash') {
                paymentMsg = '<p class="alert alert-success"><i class="fas fa-check"></i> Pay upon pickup/delivery</p>';
            } else if (paymentMethod === 'cash_on_delivery') {
                paymentMsg = '<p class="alert alert-success"><i class="fas fa-truck"></i> Pay rider when food arrives</p>';
            } else {
                paymentMsg = `<p class="alert alert-info"><i class="fas fa-clock"></i> Complete ${paymentMethod} payment</p>`;
            }
            
            document.getElementById('confirmationDetails').innerHTML = `
                <p><strong>Payment:</strong> ${paymentMethod.replace('_', ' ').toUpperCase()}</p>
                <p><strong>Total:</strong> ₱${orderData.total_price.toFixed(2)}</p>
                ${paymentMsg}
            `;
            
            cart = [];
            saveCart();
            showToast('Order placed! 🎉');
            showSection('confirmationSection');
        } else {
            showAlert(alertDiv, data.message || 'Order failed', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Order error:', error);
        showAlert(alertDiv, 'Connection error: ' + error.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function orderAgain() {
    cart = [];
    saveCart();
    showSection('menuSection');
}

// =====================================================
// ORDERS
// =====================================================
function loadOrders() {
    const ordersLoginMsg = document.getElementById('ordersLoginMsg');
    const ordersLoading = document.getElementById('ordersLoading');
    const noOrdersMsg = document.getElementById('noOrdersMsg');
    const ordersList = document.getElementById('ordersList');
    
    if (!currentUser) {
        ordersLoginMsg?.classList.remove('hidden');
        ordersLoading?.classList.add('hidden');
        noOrdersMsg?.classList.add('hidden');
        if (ordersList) ordersList.innerHTML = '';
        return;
    }
    
    ordersLoginMsg?.classList.add('hidden');
    ordersLoading?.classList.remove('hidden');
    noOrdersMsg?.classList.add('hidden');
    
    fetch(`${API_URL}/get_orders.php?user_id=${currentUser.id}`)
    .then(async response => {
        const ct = response.headers.get('content-type');
        if (!ct || !ct.includes('application/json')) throw new Error('Non-JSON');
        return response.json();
    })
    .then(data => {
        if (data.success && data.orders?.length) {
            renderOrders(data.orders);
        } else {
            noOrdersMsg?.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Orders error:', error);
        if (ordersList) ordersList.innerHTML = '<p class="error-message">Error loading orders</p>';
    })
    .finally(() => {
        ordersLoading?.classList.add('hidden');
    });
}

function renderOrders(orders) {
    const ordersList = document.getElementById('ordersList');
    if (!ordersList || !orders?.length) return;
    
    ordersList.innerHTML = orders.map(order => {
        // Format payment method for display
        const paymentDisplay = (order.payment_method || 'cash').replace('_', ' ').toUpperCase();
        
        return `
        <div class="order-card">
            <div class="order-header">
                <span class="order-id">Order #${order.id}</span>
                <span class="order-status status-${(order.status || 'pending').toLowerCase()}">
                    ${order.status || 'Pending'}
                </span>
            </div>
            <div class="order-details">
                <p><strong>Date:</strong> ${order.created_at || 'N/A'}</p>
                <p><strong>Type:</strong> ${order.order_type === 'dine-in' ? 'Dine-In' : 'Delivery'}</p>
                <p><strong>Payment:</strong> ${paymentDisplay}</p>
                ${order.table_number ? `<p><strong>Table:</strong> ${order.table_number}</p>` : ''}
                <p><strong>Total:</strong> ₱${parseFloat(order.total || 0).toFixed(2)}</p>
            </div>
            <div class="order-items">
                ${order.items ? JSON.parse(order.items).map(item => `
                    <div class="order-item-mini">
                        <span>${item.name} × ${item.quantity}</span>
                        <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `).join('') : ''}
            </div>
        </div>
    `}).join('');
}

// =====================================================
// UTILITIES
// =====================================================
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn?.querySelector('i');
    if (input && icon) {
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }
}

function showAlert(element, message, type) {
    if (!element) return;
    element.textContent = message;
    element.className = `alert alert-${type}`;
    element.classList.remove('hidden');
    setTimeout(() => element.classList.add('hidden'), 5000);
}

function showToast(message) {
    const toast = document.getElementById('toastNotification');
    if (toast) {
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }
}

function showSection(sectionId) {
    console.log('📍 Navigating to:', sectionId);
    
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    
    const target = document.getElementById(sectionId);
    if (target) {
        target.classList.add('active');
        window.scrollTo(0, 0);
    }
    
    if (sectionId === 'menuSection') loadMenuItems();
    if (sectionId === 'cartSection') renderCart();
    if (sectionId === 'checkoutSection') renderCheckout();
    if (sectionId === 'ordersSection') loadOrders();
}

// Export for debugging
window.JJES = { cart, currentUser, menuItems };