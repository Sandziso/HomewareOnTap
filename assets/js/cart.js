class ShoppingCart {
    constructor() {
        this.cart = this.loadCart();
        this.init();
    }

    // Initialize cart functionality
    init() {
        this.updateCartUI();
        this.setupEventListeners();
    }

    // Load cart from localStorage
    loadCart() {
        const savedCart = localStorage.getItem('homewareontap_cart');
        return savedCart ? JSON.parse(savedCart) : {
            items: [],
            total: 0,
            count: 0
        };
    }

    // Save cart to localStorage
    saveCart() {
        localStorage.setItem('homewareontap_cart', JSON.stringify(this.cart));
    }

    // Add item to cart
    addItem(product) {
        // Validate quantity before adding
        if (!this.validateQuantity(product.id, product.quantity, product.variants)) {
            return false;
        }

        // Check if product already exists in cart
        const existingItemIndex = this.cart.items.findIndex(item =>
            item.id === product.id && this.areProductVariantsEqual(item.variants, product.variants)
        );

        if (existingItemIndex > -1) {
            // Update quantity if product exists
            this.cart.items[existingItemIndex].quantity += product.quantity;
        } else {
            // Add new product to cart
            this.cart.items.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                image: product.image,
                quantity: product.quantity,
                variants: product.variants || {}
            });
        }

        // Update cart totals
        this.updateCartTotals();
        this.saveCart();
        this.updateCartUI();

        this.syncWithServer(); // Add this line
        return true;
    }

     // Compare product variants
    areProductVariantsEqual(variants1, variants2) {
        if (!variants1 && !variants2) return true;
        if (!variants1 || !variants2) return false;

        const keys1 = Object.keys(variants1);
        const keys2 = Object.keys(variants2);

        if (keys1.length !== keys2.length) return false;

        return keys1.every(key => variants1[key] === variants2[key]);
    }

    // Validate quantity against limits
    validateQuantity(productId, quantity, variants = {}) {
        const item = this.cart.items.find(item =>
            item.id === productId && this.areProductVariantsEqual(item.variants, variants)
        );

        const currentQuantity = item ? item.quantity : 0;
        const newTotalQuantity = currentQuantity + quantity;

        // Check stock limits (you'd need to fetch this from server)
        if (newTotalQuantity > 10) { // Example limit
            this.showNotification('Maximum quantity per product is 10', 'error');
            return false;
        }

        return true;
    }

    // Remove item from cart
    removeItem(productId, variants = {}) {
        this.cart.items = this.cart.items.filter(item =>
            !(item.id === productId && this.areProductVariantsEqual(item.variants, variants))
        );

        this.updateCartTotals();
        this.saveCart();
        this.updateCartUI();

        this.syncWithServer(); // Add this line
    }

    // Update item quantity
    updateQuantity(productId, newQuantity, variants = {}) {
        const item = this.cart.items.find(item =>
            item.id === productId && this.areProductVariantsEqual(item.variants, variants)
        );

        if (item) {
            item.quantity = Math.max(0, parseInt(newQuantity));

            // Remove item if quantity is 0
            if (item.quantity === 0) {
                this.removeItem(productId, variants);
            } else {
                this.updateCartTotals();
                this.saveCart();
                this.updateCartUI();
                this.syncWithServer(); // Add this line
            }
        }
    }

    // Update cart totals
    updateCartTotals() {
        this.cart.count = 0;
        this.cart.total = 0;

        this.cart.items.forEach(item => {
            this.cart.count += item.quantity;
            this.cart.total += item.price * item.quantity;
        });

        // Round to 2 decimal places
        this.cart.total = Math.round(this.cart.total * 100) / 100;
    }

    // Clear entire cart
    clearCart() {
        this.cart = {
            items: [],
            total: 0,
            count: 0
        };

        this.saveCart();
        this.updateCartUI();
        this.syncWithServer(); // Add this line
    }

    // Get cart contents
    getCart() {
        return this.cart;
    }

    // Update cart UI elements across the site
    updateCartUI() {
        // Update cart count in header
        const cartCountElements = document.querySelectorAll('.cart-count');
        cartCountElements.forEach(el => {
            el.textContent = this.cart.count;
        });

        // Update cart preview if it exists
        this.updateCartPreview();

        // Update cart page if we're on the cart page
        if (document.querySelector('.cart-page')) {
            this.renderCartPage();
        }
    }

    // Update cart preview dropdown
    updateCartPreview() {
        const cartPreview = document.getElementById('cartPreview');
        if (!cartPreview) return;

        const cartItemsContainer = cartPreview.querySelector('.cart-items');
        const cartTotalElement = cartPreview.querySelector('.cart-total');
        const cartCountElement = cartPreview.querySelector('h5');

        // Update item count in preview title
        if (cartCountElement) {
            cartCountElement.textContent = `Your Cart (${this.cart.count})`;
        }

        // Clear existing items
        cartItemsContainer.innerHTML = '';

        // Add items to preview
        if (this.cart.items.length === 0) {
            cartItemsContainer.innerHTML = '<p class="text-center py-3">Your cart is empty</p>';
        } else {
            this.cart.items.forEach(item => {
                const cartItemElement = document.createElement('div');
                cartItemElement.className = 'cart-item';
                cartItemElement.innerHTML = `
                    <img src="${item.image}" alt="${item.name}" class="cart-item-img">
                    <div>
                        <h6>${item.name}</h6>
                        <p>${item.quantity} x $${item.price.toFixed(2)}</p>
                        <button class="btn btn-sm btn-outline-danger remove-from-preview"
                                data-id="${item.id}"
                                data-variants='${JSON.stringify(item.variants)}'>
                            Remove
                        </button>
                    </div>
                `;
                cartItemsContainer.appendChild(cartItemElement);
            });
        }

        // Update total
        if (cartTotalElement) {
            cartTotalElement.textContent = `Total: $${this.cart.total.toFixed(2)}`;
        }

        // Add event listeners to remove buttons
        setTimeout(() => {
            const removeButtons = cartPreview.querySelectorAll('.remove-from-preview');
            removeButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = button.getAttribute('data-id');
                    const variants = JSON.parse(button.getAttribute('data-variants') || '{}');
                    this.removeItem(productId, variants);
                });
            });
        }, 100);
    }

    // Render the cart page
    renderCartPage() {
        const cartContainer = document.querySelector('.cart-items-container');
        const cartTotalElement = document.querySelector('.cart-total-value');
        const emptyCartMessage = document.querySelector('.empty-cart-message');
        const cartTable = document.querySelector('.cart-table');

        if (!cartContainer) return;

        if (this.cart.items.length === 0) {
            if (emptyCartMessage) emptyCartMessage.style.display = 'block';
            if (cartTable) cartTable.style.display = 'none';
            return;
        }

        if (emptyCartMessage) emptyCartMessage.style.display = 'none';
        if (cartTable) cartTable.style.display = 'table';

        // Clear existing items except header
        const tbody = cartContainer.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = '';
        } else {
            cartContainer.innerHTML = '';
        }

        // Add items to cart page
        this.cart.items.forEach(item => {
            const itemTotal = (item.price * item.quantity).toFixed(2);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <img src="${item.image}" alt="${item.name}" width="60" class="me-3">
                    ${item.name}
                    ${this.formatVariants(item.variants)}
                </td>
                <td>$${item.price.toFixed(2)}</td>
                <td>
                    <div class="input-group quantity-selector" style="max-width: 120px">
                        <button class="btn btn-outline-secondary decrease-quantity" type="button">-</button>
                        <input type="number" class="form-control text-center quantity-input"
                               value="${item.quantity}" min="1"
                               data-id="${item.id}"
                               data-variants='${JSON.stringify(item.variants)}'>
                        <button class="btn btn-outline-secondary increase-quantity" type="button">+</button>
                    </div>
                </td>
                <td>$${itemTotal}</td>
                <td>
                    <button class="btn btn-sm btn-danger remove-item"
                            data-id="${item.id}"
                            data-variants='${JSON.stringify(item.variants)}'>
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;

            if (tbody) {
                tbody.appendChild(row);
            } else {
                cartContainer.appendChild(row);
            }
        });

        // Update total
        if (cartTotalElement) {
            cartTotalElement.textContent = `$${this.cart.total.toFixed(2)}`;
        }

        // Add event listeners to quantity controls and remove buttons
        setTimeout(() => {
            // Decrease quantity buttons
            document.querySelectorAll('.decrease-quantity').forEach(button => {
                button.addEventListener('click', (e) => {
                    const input = e.target.closest('.quantity-selector').querySelector('.quantity-input');
                    const productId = input.getAttribute('data-id');
                    const variants = JSON.parse(input.getAttribute('data-variants') || '{}');
                    const newQuantity = parseInt(input.value) - 1;
                    this.updateQuantity(productId, newQuantity, variants);
                });
            });

            // Increase quantity buttons
            document.querySelectorAll('.increase-quantity').forEach(button => {
                button.addEventListener('click', (e) => {
                    const input = e.target.closest('.quantity-selector').querySelector('.quantity-input');
                    const productId = input.getAttribute('data-id');
                    const variants = JSON.parse(input.getAttribute('data-variants') || '{}');
                    const newQuantity = parseInt(input.value) + 1;
                    this.updateQuantity(productId, newQuantity, variants);
                });
            });

            // Direct input changes
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', (e) => {
                    const productId = e.target.getAttribute('data-id');
                    const variants = JSON.parse(e.target.getAttribute('data-variants') || '{}');
                    const newQuantity = parseInt(e.target.value);
                    this.updateQuantity(productId, newQuantity, variants);
                });
            });

            // Remove item buttons
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productId = button.getAttribute('data-id');
                    const variants = JSON.parse(button.getAttribute('data-variants') || '{}');
                    this.removeItem(productId, variants);
                });
            });
        }, 100);
    }

    // Format product variants for display
    formatVariants(variants) {
        if (!variants || Object.keys(variants).length === 0) return '';

        let html = '<div class="product-variants small text-muted">';
        for (const [key, value] of Object.entries(variants)) {
            html += `<div>${key}: ${value}</div>`;
        }
        html += '</div>';

        return html;
    }

    // Setup event listeners for cart interactions
    setupEventListeners() {
        // Delegate events for dynamic elements
        document.addEventListener('click', (e) => {
            // Add to cart buttons
            if (e.target.closest('.add-to-cart')) {
                const button = e.target.closest('.add-to-cart');
                e.preventDefault();

                const product = {
                    id: button.getAttribute('data-id') || Math.random().toString(36).substr(2, 9),
                    name: button.getAttribute('data-name') || 'Product',
                    price: button.getAttribute('data-price') || 0,
                    image: button.getAttribute('data-image') || '',
                    quantity: parseInt(button.getAttribute('data-quantity') || 1),
                    variants: this.getVariantsFromButton(button)
                };

                this.addItem(product);

                // Show notification
                this.showNotification(`${product.name} added to cart!`);
            }

            // Clear cart button
            if (e.target.closest('.clear-cart')) {
                e.preventDefault();
                if (confirm('Are you sure you want to clear your cart?')) {
                    this.clearCart();
                    this.showNotification('Cart cleared');
                }
            }

            // Continue shopping button
            if (e.target.closest('.continue-shopping')) {
                // This would typically close the cart preview or redirect
                const cartPreview = document.getElementById('cartPreview');
                if (cartPreview) cartPreview.classList.remove('active');
            }
        });

        // Keyboard events for quantity inputs
        document.addEventListener('keydown', (e) => {
            if (e.target.classList.contains('quantity-input')) {
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    e.target.stepUp();
                    e.target.dispatchEvent(new Event('change'));
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    e.target.stepDown();
                    e.target.dispatchEvent(new Event('change'));
                }
            }
        });
    }

    // Get variants data from button attributes
    getVariantsFromButton(button) {
        const variants = {};
        const variantAttributes = ['color', 'size', 'material', 'style'];

        variantAttributes.forEach(attr => {
            const value = button.getAttribute(`data-${attr}`);
            if (value) {
                variants[attr] = value;
            }
        });

        return variants;
    }

    // Show notification
    showNotification(message) {
        // Use existing notification element if available
        let notification = document.getElementById('cartNotification');

        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'cartNotification';
            notification.className = 'notification';
            document.body.appendChild(notification);
        }

        notification.textContent = message;
        notification.classList.add('show');

        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // In ShoppingCart class - add these methods
    async syncWithServer() {
        try {
            const response = await fetch('/system/controllers/CartController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sync_cart',
                    cart_data: JSON.stringify(this.cart)
                })
            });

            const result = await response.json();
            if (result.success) {
                console.log('Cart synced with server');
            }
        } catch (error) {
            console.error('Failed to sync cart:', error);
        }
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.homewareCart = new ShoppingCart();
});

// Export for use in other modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ShoppingCart;
}


// =========================================================
// New CartManager Class for Server-Side Cart Synchronization
// =========================================================

class CartManager {
    constructor() {
        this.baseUrl = '/system/controllers/CartController.php';
        this.csrfToken = this.getCSRFToken();
    }

    // Placeholder: Gets CSRF token from a hidden field or meta tag
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || 'dummy-csrf-token';
    }

    // Placeholder: Handles AJAX requests to the backend controller
    async makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', this.csrfToken);

        for (const key in data) {
            formData.append(key, data[key]);
        }

        const response = await fetch(this.baseUrl, {
            method: 'POST',
            body: formData
        });

        return response.json();
    }

    // Placeholder: Updates UI elements like cart count or mini-cart
    updateUI(data) {
        // Implementation logic for updating UI elements goes here
        console.log('UI update triggered with data:', data);
    }

    // Placeholder: Displays a toast notification to the user
    showToast(message, type) {
        // Implementation logic for displaying a toast/notification goes here
        console.log(`[${type.toUpperCase()}] ${message}`);
    }

    // Placeholder: Removes item entirely from the cart
    async removeFromCart(cartItemId) {
        // Implementation logic for removing item via API call goes here
        this.showToast(`Removing item ${cartItemId}...`, 'info');
    }

    // Placeholder: Sets a loading state on the cart item UI element
    setLoadingState(cartItemId, isLoading) {
        // Implementation logic for showing/hiding a spinner on the cart item goes here
        console.log(`Cart item ${cartItemId} loading state: ${isLoading}`);
    }

    // Placeholder: Updates the cart summary totals (subtotal, tax, shipping)
    updateCartSummary(data) {
        // Implementation logic for updating cart totals on the cart page goes here
        console.log('Cart summary updated with data:', data);
    }

    async addToCart(productId, quantity = 1) {
        try {
            const response = await this.makeRequest('add_to_cart', {
                product_id: productId,
                quantity: quantity
            });

            if (response.success) {
                this.updateUI(response.data);
                this.showToast('Product added to cart', 'success');
            } else {
                this.showToast(response.message, 'error');
            }
        } catch (error) {
            this.showToast('Network error. Please try again.', 'error');
        }
    }

    async updateQuantity(cartItemId, quantity) {
        if (quantity < 1) {
            await this.removeFromCart(cartItemId);
            return;
        }

        // Add loading state
        this.setLoadingState(cartItemId, true);

        try {
            const response = await this.makeRequest('update_cart_quantity', {
                cart_item_id: cartItemId,
                quantity: quantity
            });

            if (response.success) {
                this.updateCartSummary(response.data);
            }
        } finally {
            this.setLoadingState(cartItemId, false);
        }
    }
}