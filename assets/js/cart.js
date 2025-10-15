// cart.js - UPDATED VERSION

class ShoppingCart {
    constructor() {
        this.cart = this.loadCart();
        // Initialize CartManager for server communication
        this.manager = new CartManager();
        this.init();
    }

    // Initialize cart functionality
    init() {
        this.updateCartUI();
        this.setupEventListeners();
        this.syncWithServer(); // Sync on load
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
    async addItem(product) {
        // Validate quantity before adding (local check)
        if (!this.validateQuantity(product.id, product.quantity, product.variants)) {
            return false;
        }

        try {
            // Use CartManager to perform server-side add
            const response = await this.manager.addToCart(product.id, product.quantity);
            
            if (response.success) {
                // Update local cart only if server request succeeded
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

                // Update local storage and UI
                this.updateCartTotals();
                this.saveCart();
                this.updateCartUI();

                // Show notification for success
                this.showNotification(`${product.name} added to cart!`);
                return true;
            } else {
                // CartManager already displays an error toast
                return false;
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.manager.showToast('Failed to add item to cart. Please try again.', 'error');
            return false;
        }
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
    async removeItem(productId, variants = {}) {
        // NOTE: The server-side removal logic in CartManager.removeFromCart typically
        // requires a cartItemId, not productId+variants. Since we don't have a cartItemId
        // locally, we'll keep the local logic and use syncWithServer() to reconcile.
        
        this.cart.items = this.cart.items.filter(item =>
            !(item.id === productId && this.areProductVariantsEqual(item.variants, variants))
        );

        this.updateCartTotals();
        this.saveCart();
        this.updateCartUI();

        await this.syncWithServer(); // Sync after local change
    }

    // Update item quantity
    async updateQuantity(productId, newQuantity, variants = {}) {
        const item = this.cart.items.find(item =>
            item.id === productId && this.areProductVariantsEqual(item.variants, variants)
        );

        if (item) {
            const finalQuantity = Math.max(0, parseInt(newQuantity));

            if (finalQuantity === 0) {
                this.removeItem(productId, variants);
            } else {
                // NOTE: The CartManager.updateQuantity method expects a cartItemId,
                // which is missing from the local cart item. We'll proceed with
                // local update and use syncWithServer() to reconcile.
                item.quantity = finalQuantity;

                this.updateCartTotals();
                this.saveCart();
                this.updateCartUI();
                
                await this.syncWithServer(); // Sync after local change
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
    async clearCart() {
        this.cart = {
            items: [],
            total: 0,
            count: 0
        };

        this.saveCart();
        this.updateCartUI();
        await this.syncWithServer(); // Sync after local clear
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

                // addItem now handles calling the server
                this.addItem(product);

                // Notification is now handled inside addItem after successful server call
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
    showNotification(message, type = 'success') {
        // Use CartManager's toast function for consistency
        this.manager.showToast(message, type);
    }

    // UPDATED METHOD: Sync local cart data with the server
    async syncWithServer() {
        try {
            // Send local cart data to server
            const response = await this.manager.makeRequest('sync_cart', {
                cart_data: JSON.stringify(this.cart)
            });

            if (response.success) {
                console.log('Cart synced with server');
                // Get the definitive cart state from the server
                const summaryResponse = await this.manager.makeRequest('get_cart_summary');
                if (summaryResponse.success) {
                    this.updateFromServer(summaryResponse.summary);
                }
            }
        } catch (error) {
            console.error('Failed to sync cart:', error);
            // Error handling is mostly done within makeRequest, but log here too
        }
    }

    // NEW METHOD: Update local cart from server data
    updateFromServer(serverSummary) {
        if (serverSummary && serverSummary.items) {
            // Map server items to local cart structure
            this.cart.items = serverSummary.items.map(item => ({
                // NOTE: This assumes item.id is the product_id from the server,
                // and it doesn't fully handle variants reconciliation.
                id: item.product_id,
                name: item.name,
                price: parseFloat(item.price),
                image: item.image,
                quantity: parseInt(item.quantity),
                variants: item.variants || {} // Use server variants if available
            }));
            
            // Re-calculate local totals
            this.updateCartTotals();
            // Persist to local storage
            this.saveCart();
            // Re-render UI
            this.updateCartUI();
            
            // Also call CartManager's updateUI to handle server-calculated totals/counts
            this.manager.updateUI(serverSummary);
        }
    }
}

// =========================================================
// CartManager Class for Server-Side Cart Synchronization
// =========================================================

class CartManager {
    constructor() {
        this.baseUrl = '/system/controllers/CartController.php';
        this.csrfToken = this.getCSRFToken();
    }

    // Get CSRF token from meta tag or form
    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
               document.querySelector('input[name="csrf_token"]')?.value ||
               'dummy-csrf-token';
    }

    // Make AJAX request to backend
    async makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', this.csrfToken);

        for (const key in data) {
            formData.append(key, data[key]);
        }

        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Request failed:', error);
            return { success: false, message: 'Network error. Please try again.' };
        }
    }

    // Fix the updateUI method to handle different response structures
    updateUI(data) {
        // Update cart count from various possible response structures
        const cartCountElements = document.querySelectorAll('.cart-count');
        let count = 0;
        
        if (data.cart_count !== undefined) {
            count = data.cart_count;
        } else if (data.data && data.data.cart_count !== undefined) {
            count = data.data.cart_count;
        } else if (data.summary && data.summary.cart_count !== undefined) {
            count = data.summary.cart_count;
        }
        
        cartCountElements.forEach(el => {
            el.textContent = count;
        });

        // Update cart summary if available
        if (data.summary) {
            this.updateCartSummary(data.summary);
        }
        
        console.log('UI update triggered with data:', data);
    }

    // Show toast notification
    showToast(message, type = 'success') {
        // Use existing toast function if available
        if (typeof showToast === 'function') {
            showToast(message, type);
            return;
        }

        // Fallback toast implementation
        let notification = document.getElementById('cartNotification');

        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'cartNotification';
            notification.className = 'notification';
            document.body.appendChild(notification);
        }
        
        notification.textContent = message;
        // Simple class toggling for visibility/styling
        notification.className = `notification show ${type}`;

        setTimeout(() => {
            notification.classList.remove('show');
        }, 4000);

        console.log(`[${type.toUpperCase()}] ${message}`);
    }

    // Remove item from cart (API call)
    async removeFromCart(cartItemId) {
        this.setLoadingState(cartItemId, true);
        
        try {
            const response = await this.makeRequest('remove_from_cart', {
                cart_item_id: cartItemId
            });

            if (response.success) {
                this.updateUI(response);
                this.showToast('Item removed from cart', 'success');
                return { success: true };
            } else {
                this.showToast(response.message, 'error');
                return { success: false, message: response.message };
            }
        } catch (error) {
            this.showToast('Network error. Please try again.', 'error');
            return { success: false, message: 'Network error' };
        } finally {
            this.setLoadingState(cartItemId, false);
        }
    }

    // Set loading state
    setLoadingState(cartItemId, isLoading) {
        const element = document.querySelector(`[data-product-id="${cartItemId}"]`) || 
                         document.querySelector(`[data-cart-item-id="${cartItemId}"]`);
        
        if (element) {
            if (isLoading) {
                element.classList.add('loading');
                element.disabled = true;
            } else {
                element.classList.remove('loading');
                element.disabled = false;
            }
        }
    }

    // Update cart summary
    updateCartSummary(data) {
        // Call global function if defined
        if (typeof updateCartSummary === 'function') {
            updateCartSummary(
                data.cart_total,
                data.shipping_cost,
                data.tax_amount,
                data.grand_total
            );
        }
        // Log for debugging
        console.log('Cart summary updated with server data:', data);
    }

    // Add to cart (API call)
    async addToCart(productId, quantity = 1) {
        try {
            const response = await this.makeRequest('add_to_cart', {
                product_id: productId,
                quantity: quantity
            });

            if (response.success) {
                this.updateUI(response);
                return { success: true };
            } else {
                this.showToast(response.message, 'error');
                return { success: false, message: response.message };
            }
        } catch (error) {
            this.showToast('Network error. Please try again.', 'error');
            return { success: false, message: 'Network error' };
        }
    }

    // Update quantity (API call) - FIXED
    async updateQuantity(cartItemId, quantity) {
        if (quantity < 1) {
            const removeResult = await this.removeFromCart(cartItemId);
            return removeResult;
        }

        this.setLoadingState(cartItemId, true);

        try {
            // Fix: Use correct action name 'update_cart_quantity' instead of 'update_cart_item'
            const response = await this.makeRequest('update_cart_quantity', {
                cart_item_id: cartItemId,
                quantity: quantity
            });

            if (response.success) {
                // Fix: Update UI with the response data
                this.updateUI(response);
                return { success: true };
            } else {
                this.showToast(response.message, 'error');
                return { success: false, message: response.message };
            }
        } catch (error) {
            this.showToast('Network error. Please try again.', 'error');
            return { success: false, message: 'Network error' };
        } finally {
            this.setLoadingState(cartItemId, false);
        }
    }

    // Apply coupon (API call)
    async applyCoupon(couponCode) {
        try {
            const response = await this.makeRequest('apply_coupon', {
                coupon_code: couponCode
            });

            if (response.success) {
                this.updateCartSummary(response);
                this.showToast(response.message, 'success');
                return { success: true };
            } else {
                this.showToast(response.message, 'error');
                return { success: false, message: response.message };
            }
        } catch (error) {
            this.showToast('Network error. Please try again.', 'error');
            return { success: false, message: 'Network error' };
        }
    }

    // NEW: Get cart summary from server
    async getCartSummary() {
        try {
            const response = await this.makeRequest('get_cart_summary');
            return response;
        } catch (error) {
            console.error('Failed to get cart summary:', error);
            return { success: false, message: 'Network error' };
        }
    }
}

// Initialize cart when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.homewareCart = new ShoppingCart();
    window.cartManager = new CartManager();
});

// Export for use in other modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ShoppingCart, CartManager };
}