// checkout.js - Checkout Process for HomewareOnTap

class CheckoutProcess {
    constructor() {
        this.cart = window.homewareCart ? window.homewareCart.getCart() : { items: [], total: 0, count: 0 };
        this.currentStep = 1;
        this.shippingMethods = [
            { id: 'standard', name: 'Standard Shipping', cost: 4.99, delivery: '5-7 business days' },
            { id: 'express', name: 'Express Shipping', cost: 9.99, delivery: '2-3 business days' },
            { id: 'priority', name: 'Priority Shipping', cost: 14.99, delivery: '1-2 business days' }
        ];
        this.selectedShipping = this.shippingMethods[0];
        this.paymentMethods = [
            { id: 'credit_card', name: 'Credit Card', icon: 'fas fa-credit-card' },
            { id: 'paypal', name: 'PayPal', icon: 'fab fa-paypal' },
            { id: 'bank_transfer', name: 'Bank Transfer', icon: 'fas fa-university' }
        ];
        this.userData = this.loadUserData();
        this.init();
    }

    // Initialize checkout functionality
    init() {
        if (!this.validateCart()) {
            window.location.href = '/cart.php';
            return;
        }

        this.renderOrderSummary();
        this.setupEventListeners();
        this.showStep(this.currentStep);
        
        // Load saved form data if available
        this.loadFormData();
    }

    // Validate that cart has items
    validateCart() {
        return this.cart.items && this.cart.items.length > 0;
    }

    // Load user data from localStorage if available
    loadUserData() {
        const savedData = localStorage.getItem('homewareontap_userdata');
        return savedData ? JSON.parse(savedData) : {
            shipping: {
                firstName: '',
                lastName: '',
                email: '',
                phone: '',
                address: '',
                city: '',
                state: '',
                zipCode: '',
                country: 'United States'
            },
            billing: {
                sameAsShipping: true,
                firstName: '',
                lastName: '',
                address: '',
                city: '',
                state: '',
                zipCode: '',
                country: 'United States'
            },
            payment: {
                method: 'credit_card',
                cardNumber: '',
                cardName: '',
                expiry: '',
                cvv: ''
            }
        };
    }

    // Save user data to localStorage
    saveUserData() {
        localStorage.setItem('homewareontap_userdata', JSON.stringify(this.userData));
    }

    // Render order summary
    renderOrderSummary() {
        const summaryContainer = document.getElementById('orderSummary');
        if (!summaryContainer) return;

        let html = '';
        
        this.cart.items.forEach(item => {
            const itemTotal = (item.price * item.quantity).toFixed(2);
            html += `
                <div class="order-item d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <img src="${item.image}" alt="${item.name}" width="60" class="me-3">
                        <div>
                            <h6 class="mb-1">${item.name}</h6>
                            <small class="text-muted">Qty: ${item.quantity}</small>
                            ${this.formatVariants(item.variants)}
                        </div>
                    </div>
                    <span class="price">$${itemTotal}</span>
                </div>
            `;
        });

        const subtotal = this.cart.total;
        const tax = Math.round(subtotal * 0.08 * 100) / 100; // 8% tax
        const shippingCost = this.selectedShipping ? this.selectedShipping.cost : 0;
        const total = subtotal + tax + shippingCost;

        html += `
            <div class="order-totals mt-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>$${subtotal.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax (8%):</span>
                    <span>$${tax.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping:</span>
                    <span>$${shippingCost.toFixed(2)}</span>
                </div>
                <div class="d-flex justify-content-between mt-3 pt-3 border-top">
                    <strong>Total:</strong>
                    <strong class="price">$${total.toFixed(2)}</strong>
                </div>
            </div>
        `;

        summaryContainer.innerHTML = html;
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

    // Show a specific step in the checkout process
    showStep(step) {
        // Hide all steps
        document.querySelectorAll('.checkout-step').forEach(el => {
            el.classList.remove('active');
        });
        
        // Show the requested step
        const stepElement = document.getElementById(`step${step}`);
        if (stepElement) {
            stepElement.classList.add('active');
        }
        
        // Update step indicators
        document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
            if (index + 1 < step) {
                indicator.classList.add('completed');
                indicator.classList.remove('active');
            } else if (index + 1 === step) {
                indicator.classList.add('active');
                indicator.classList.remove('completed');
            } else {
                indicator.classList.remove('active', 'completed');
            }
        });
        
        this.currentStep = step;
        
        // Scroll to top of checkout form
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Validate current step before proceeding
    validateStep(step) {
        switch(step) {
            case 1: // Contact information
                return this.validateContactInfo();
            case 2: // Shipping
                return this.validateShipping();
            case 3: // Payment
                return this.validatePayment();
            default:
                return true;
        }
    }

    // Validate contact information
    validateContactInfo() {
        const email = document.getElementById('email');
        const phone = document.getElementById('phone');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!email.value || !emailRegex.test(email.value)) {
            this.showError('Please enter a valid email address');
            email.focus();
            return false;
        }
        
        if (phone.value && !/^[\+]?[1-9][\d]{0,15}$/.test(phone.value.replace(/[\s\-\(\)]/g, ''))) {
            this.showError('Please enter a valid phone number');
            phone.focus();
            return false;
        }
        
        // Save contact info
        this.userData.shipping.email = email.value;
        this.userData.shipping.phone = phone.value;
        this.saveUserData();
        
        return true;
    }

    // Validate shipping information
    validateShipping() {
        const requiredFields = [
            'firstName', 'lastName', 'address', 'city', 'state', 'zipCode'
        ];
        
        for (const field of requiredFields) {
            const element = document.getElementById(field);
            if (!element || !element.value.trim()) {
                this.showError(`Please fill in the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()}`);
                if (element) element.focus();
                return false;
            }
            
            // Save to user data
            this.userData.shipping[field] = element.value.trim();
        }
        
        this.saveUserData();
        return true;
    }

    // Validate payment information
    validatePayment() {
        const method = document.querySelector('input[name="paymentMethod"]:checked');
        if (!method) {
            this.showError('Please select a payment method');
            return false;
        }
        
        this.userData.payment.method = method.value;
        
        // Validate credit card details if selected
        if (method.value === 'credit_card') {
            const cardNumber = document.getElementById('cardNumber');
            const cardName = document.getElementById('cardName');
            const expiry = document.getElementById('expiry');
            const cvv = document.getElementById('cvv');
            
            if (!cardNumber.value || !this.validateCardNumber(cardNumber.value)) {
                this.showError('Please enter a valid card number');
                cardNumber.focus();
                return false;
            }
            
            if (!cardName.value) {
                this.showError('Please enter the name on your card');
                cardName.focus();
                return false;
            }
            
            if (!expiry.value || !this.validateExpiry(expiry.value)) {
                this.showError('Please enter a valid expiration date (MM/YY)');
                expiry.focus();
                return false;
            }
            
            if (!cvv.value || !/^\d{3,4}$/.test(cvv.value)) {
                this.showError('Please enter a valid CVV');
                cvv.focus();
                return false;
            }
            
            // Save payment details
            this.userData.payment.cardNumber = cardNumber.value;
            this.userData.payment.cardName = cardName.value;
            this.userData.payment.expiry = expiry.value;
            this.userData.payment.cvv = cvv.value;
        }
        
        this.saveUserData();
        return true;
    }

    // Validate credit card number using Luhn algorithm
    validateCardNumber(number) {
        // Remove any non-digit characters
        number = number.replace(/\D/g, '');
        
        // Check if the number is empty or not all digits
        if (!number || !/^\d+$/.test(number)) {
            return false;
        }
        
        // Luhn algorithm
        let sum = 0;
        let shouldDouble = false;
        
        for (let i = number.length - 1; i >= 0; i--) {
            let digit = parseInt(number.charAt(i));
            
            if (shouldDouble) {
                if ((digit *= 2) > 9) digit -= 9;
            }
            
            sum += digit;
            shouldDouble = !shouldDouble;
        }
        
        return (sum % 10) === 0;
    }

    // Validate card expiry date
    validateExpiry(expiry) {
        const parts = expiry.split('/');
        if (parts.length !== 2) return false;
        
        const month = parseInt(parts[0]);
        const year = parseInt(parts[1]);
        
        if (isNaN(month) || isNaN(year) || month < 1 || month > 12) {
            return false;
        }
        
        // Check if card is expired
        const now = new Date();
        const currentYear = now.getFullYear() % 100;
        const currentMonth = now.getMonth() + 1;
        
        if (year < currentYear || (year === currentYear && month < currentMonth)) {
            return false;
        }
        
        return true;
    }

    // Process the order
    async processOrder() {
        try {
            // Show loading state
            const submitBtn = document.getElementById('submitOrder');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            submitBtn.disabled = true;
            
            // Prepare order data
            const orderData = {
                items: this.cart.items,
                subtotal: this.cart.total,
                tax: Math.round(this.cart.total * 0.08 * 100) / 100,
                shipping: this.selectedShipping.cost,
                total: this.cart.total + Math.round(this.cart.total * 0.08 * 100) / 100 + this.selectedShipping.cost,
                customer: this.userData.shipping,
                billing: this.userData.billing.sameAsShipping ? this.userData.shipping : this.userData.billing,
                payment: this.userData.payment,
                shippingMethod: this.selectedShipping
            };
            
            // In a real application, you would send this to your server
            // For demo purposes, we'll simulate an API call
            const response = await this.submitOrderToServer(orderData);
            
            if (response.success) {
                // Clear cart
                if (window.homewareCart) {
                    window.homewareCart.clearCart();
                }
                
                // Clear saved user data
                localStorage.removeItem('homewareontap_userdata');
                
                // Redirect to confirmation page
                window.location.href = `/order-confirmation.php?order_id=${response.orderId}`;
            } else {
                this.showError(response.message || 'There was an error processing your order. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        } catch (error) {
            console.error('Order processing error:', error);
            this.showError('There was an error processing your order. Please try again.');
            
            const submitBtn = document.getElementById('submitOrder');
            submitBtn.innerHTML = 'Place Order';
            submitBtn.disabled = false;
        }
    }

    // Simulate server submission
    async submitOrderToServer(orderData) {
        // This would be a real API call in production
        return new Promise(resolve => {
            setTimeout(() => {
                // Simulate successful order 90% of the time
                if (Math.random() > 0.1) {
                    resolve({
                        success: true,
                        orderId: 'ORD' + Date.now()
                    });
                } else {
                    resolve({
                        success: false,
                        message: 'Payment declined. Please try another payment method.'
                    });
                }
            }, 2000);
        });
    }

    // Show error message
    showError(message) {
        // Remove any existing error alerts
        const existingAlert = document.getElementById('checkoutError');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Create error alert
        const alert = document.createElement('div');
        alert.id = 'checkoutError';
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert at the top of the checkout form
        const checkoutForm = document.getElementById('checkoutForm');
        if (checkoutForm) {
            checkoutForm.insertBefore(alert, checkoutForm.firstChild);
        }
        
        // Scroll to error
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Load saved form data
    loadFormData() {
        // Load contact info
        if (this.userData.shipping.email) {
            const emailField = document.getElementById('email');
            if (emailField) emailField.value = this.userData.shipping.email;
        }
        
        if (this.userData.shipping.phone) {
            const phoneField = document.getElementById('phone');
            if (phoneField) phoneField.value = this.userData.shipping.phone;
        }
        
        // Load shipping info
        for (const field in this.userData.shipping) {
            if (field !== 'email' && field !== 'phone') {
                const fieldElement = document.getElementById(field);
                if (fieldElement && this.userData.shipping[field]) {
                    fieldElement.value = this.userData.shipping[field];
                }
            }
        }
        
        // Load billing info
        const sameAsShipping = document.getElementById('sameAsShipping');
        if (sameAsShipping) {
            sameAsShipping.checked = this.userData.billing.sameAsShipping;
            this.toggleBillingAddress(!this.userData.billing.sameAsShipping);
        }
        
        if (!this.userData.billing.sameAsShipping) {
            for (const field in this.userData.billing) {
                if (field !== 'sameAsShipping') {
                    const fieldElement = document.getElementById(`billing${field.charAt(0).toUpperCase() + field.slice(1)}`);
                    if (fieldElement && this.userData.billing[field]) {
                        fieldElement.value = this.userData.billing[field];
                    }
                }
            }
        }
        
        // Load payment method
        const paymentMethod = document.querySelector(`input[name="paymentMethod"][value="${this.userData.payment.method}"]`);
        if (paymentMethod) {
            paymentMethod.checked = true;
            this.togglePaymentDetails(this.userData.payment.method);
        }
        
        // Load card details if available
        if (this.userData.payment.cardNumber) {
            const cardNumber = document.getElementById('cardNumber');
            if (cardNumber) cardNumber.value = this.userData.payment.cardNumber;
        }
        
        if (this.userData.payment.cardName) {
            const cardName = document.getElementById('cardName');
            if (cardName) cardName.value = this.userData.payment.cardName;
        }
        
        if (this.userData.payment.expiry) {
            const expiry = document.getElementById('expiry');
            if (expiry) expiry.value = this.userData.payment.expiry;
        }
        
        if (this.userData.payment.cvv) {
            const cvv = document.getElementById('cvv');
            if (cvv) cvv.value = this.userData.payment.cvv;
        }
    }

    // Toggle billing address fields
    toggleBillingAddress(show) {
        const billingFields = document.getElementById('billingFields');
        if (billingFields) {
            billingFields.style.display = show ? 'block' : 'none';
            
            // Toggle required attribute
            const inputs = billingFields.querySelectorAll('input');
            inputs.forEach(input => {
                input.required = show;
            });
        }
    }

    // Toggle payment details based on selected method
    togglePaymentDetails(method) {
        const creditCardFields = document.getElementById('creditCardFields');
        const otherMethodMessage = document.getElementById('otherMethodMessage');
        
        if (method === 'credit_card') {
            if (creditCardFields) creditCardFields.style.display = 'block';
            if (otherMethodMessage) otherMethodMessage.style.display = 'none';
        } else {
            if (creditCardFields) creditCardFields.style.display = 'none';
            if (otherMethodMessage) otherMethodMessage.style.display = 'block';
            
            if (otherMethodMessage) {
                otherMethodMessage.textContent = `You will be redirected to complete payment with ${this.paymentMethods.find(m => m.id === method).name}`;
            }
        }
    }

    // Setup event listeners
    setupEventListeners() {
        // Next step buttons
        document.querySelectorAll('.btn-next').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const nextStep = parseInt(button.getAttribute('data-next'));
                
                if (this.validateStep(this.currentStep)) {
                    this.showStep(nextStep);
                }
            });
        });
        
        // Previous step buttons
        document.querySelectorAll('.btn-prev').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const prevStep = parseInt(button.getAttribute('data-prev'));
                this.showStep(prevStep);
            });
        });
        
        // Shipping method selection
        document.querySelectorAll('input[name="shippingMethod"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.selectedShipping = this.shippingMethods.find(m => m.id === e.target.value);
                this.renderOrderSummary();
            });
        });
        
        // Same as shipping address toggle
        const sameAsShipping = document.getElementById('sameAsShipping');
        if (sameAsShipping) {
            sameAsShipping.addEventListener('change', (e) => {
                this.userData.billing.sameAsShipping = e.target.checked;
                this.toggleBillingAddress(!e.target.checked);
                this.saveUserData();
            });
        }
        
        // Payment method selection
        document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.togglePaymentDetails(e.target.value);
                this.userData.payment.method = e.target.value;
                this.saveUserData();
            });
        });
        
        // Place order button
        const submitOrder = document.getElementById('submitOrder');
        if (submitOrder) {
            submitOrder.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.validateStep(3)) {
                    this.processOrder();
                }
            });
        }
        
        // Auto-format credit card number
        const cardNumber = document.getElementById('cardNumber');
        if (cardNumber) {
            cardNumber.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                
                // Add spaces for better readability
                if (value.length > 0) {
                    value = value.match(/.{1,4}/g).join(' ');
                }
                
                e.target.value = value;
            });
        }
        
        // Auto-format expiry date
        const expiry = document.getElementById('expiry');
        if (expiry) {
            expiry.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                
                e.target.value = value;
            });
        }
        
        // Save form data on input change
        document.querySelectorAll('#checkoutForm input').forEach(input => {
            input.addEventListener('blur', () => {
                this.saveUserData();
            });
        });
    }
}

// Initialize checkout when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on checkout page
    if (document.getElementById('checkoutForm')) {
        window.checkoutProcess = new CheckoutProcess();
    }
});