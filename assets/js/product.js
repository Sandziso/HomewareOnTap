// product.js - Product Page Interactions for HomewareOnTap

class ProductPage {
    constructor() {
        this.init();
    }

    // Initialize product page functionality
    init() {
        this.setupImageGallery();
        this.setupQuantityControls();
        this.setupVariantSelection();
        this.setupTabs();
        this.setupZoom();
        this.setupReviews();
        this.setupRelatedProducts();
        this.setupEventListeners();
    }

    // Setup image gallery functionality
    setupImageGallery() {
        const mainImage = document.getElementById('productMainImage');
        const thumbnails = document.querySelectorAll('.product-thumbnail');
        
        if (!mainImage || thumbnails.length === 0) return;
        
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Update main image
                const newSrc = thumb.getAttribute('data-image') || thumb.src;
                mainImage.src = newSrc;
                
                // Update active thumbnail
                thumbnails.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
    }

    // Setup quantity controls
    setupQuantityControls() {
        const quantityInput = document.getElementById('productQuantity');
        const decreaseBtn = document.querySelector('.decrease-quantity');
        const increaseBtn = document.querySelector('.increase-quantity');
        
        if (!quantityInput) return;
        
        if (decreaseBtn) {
            decreaseBtn.addEventListener('click', () => {
                let value = parseInt(quantityInput.value) || 1;
                if (value > 1) {
                    quantityInput.value = value - 1;
                }
            });
        }
        
        if (increaseBtn) {
            increaseBtn.addEventListener('click', () => {
                let value = parseInt(quantityInput.value) || 1;
                quantityInput.value = value + 1;
            });
        }
        
        // Validate input
        quantityInput.addEventListener('change', () => {
            let value = parseInt(quantityInput.value) || 1;
            if (value < 1) quantityInput.value = 1;
        });
    }

    // Handle product variant selection
    setupVariantSelection() {
        const variantSelects = document.querySelectorAll('.variant-select');
        
        variantSelects.forEach(select => {
            select.addEventListener('change', () => {
                this.updateVariantData();
            });
        });
    }

    // Update product data based on selected variants
    updateVariantData() {
        const variantSelects = document.querySelectorAll('.variant-select');
        const productIdElement = document.getElementById('productId');
        const priceElement = document.getElementById('productPrice');
        const stockElement = document.getElementById('productStock');
        const mainImage = document.getElementById('productMainImage');
        
        if (!productIdElement) return;
        
        // Get selected variants
        const selectedVariants = {};
        variantSelects.forEach(select => {
            if (select.value) {
                selectedVariants[select.name] = select.value;
            }
        });
        
        // In a real application, this would fetch data from the server
        // For demo purposes, we'll simulate the behavior
        
        // Find matching variant
        const variantData = this.getVariantData();
        const matchedVariant = this.findMatchingVariant(variantData, selectedVariants);
        
        if (matchedVariant) {
            // Update product details
            if (priceElement && matchedVariant.price) {
                priceElement.textContent = `$${matchedVariant.price.toFixed(2)}`;
            }
            
            if (stockElement) {
                stockElement.textContent = matchedVariant.in_stock ? 'In Stock' : 'Out of Stock';
                stockElement.className = matchedVariant.in_stock ? 'in-stock text-success' : 'out-of-stock text-danger';
                
                // Disable add to cart if out of stock
                const addToCartBtn = document.querySelector('.add-to-cart');
                if (addToCartBtn) {
                    addToCartBtn.disabled = !matchedVariant.in_stock;
                }
            }
            
            if (mainImage && matchedVariant.image) {
                mainImage.src = matchedVariant.image;
            }
            
            // Update product ID for the variant
            productIdElement.value = matchedVariant.id;
        }
    }

    // Simulate variant data (in a real app, this would come from the server)
    getVariantData() {
        // This is sample data - in reality, this would be fetched from the server
        // based on the main product ID
        const productId = document.getElementById('productId')?.value;
        
        // Return sample variant data
        return [
            {
                id: `${productId}-blue-queen`,
                options: { color: 'blue', size: 'queen' },
                price: 299.99,
                in_stock: true,
                image: 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80'
            },
            {
                id: `${productId}-blue-king`,
                options: { color: 'blue', size: 'king' },
                price: 349.99,
                in_stock: true,
                image: 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80'
            },
            {
                id: `${productId}-gray-queen`,
                options: { color: 'gray', size: 'queen' },
                price: 299.99,
                in_stock: false,
                image: 'https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=387&q=80'
            },
            {
                id: `${productId}-gray-king`,
                options: { color: 'gray', size: 'king' },
                price: 349.99,
                in_stock: true,
                image: 'https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=387&q=80'
            }
        ];
    }

    // Find variant that matches selected options
    findMatchingVariant(variants, selectedOptions) {
        return variants.find(variant => {
            return Object.keys(selectedOptions).every(key => {
                return variant.options[key] === selectedOptions[key];
            });
        });
    }

    // Setup description/reviews tabs
    setupTabs() {
        const tabButtons = document.querySelectorAll('.product-tab');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                
                const targetTab = button.getAttribute('data-tab');
                
                // Update active tab button
                tabButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Show target tab pane
                tabPanes.forEach(pane => {
                    if (pane.id === targetTab) {
                        pane.classList.add('show', 'active');
                    } else {
                        pane.classList.remove('show', 'active');
                    }
                });
            });
        });
    }

    // Setup image zoom functionality
    setupZoom() {
        const mainImage = document.getElementById('productMainImage');
        if (!mainImage) return;
        
        // Only enable zoom on desktop
        if (window.innerWidth < 992) return;
        
        mainImage.addEventListener('mousemove', (e) => {
            if (!mainImage.classList.contains('zoomable')) return;
            
            const rect = mainImage.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const xPercent = Math.round((x / rect.width) * 100);
            const yPercent = Math.round((y / rect.height) * 100);
            
            mainImage.style.transformOrigin = `${xPercent}% ${yPercent}%`;
            mainImage.classList.add('zoomed');
        });
        
        mainImage.addEventListener('mouseleave', () => {
            mainImage.classList.remove('zoomed');
        });
        
        // Add zoom toggle button
        const zoomToggle = document.createElement('button');
        zoomToggle.className = 'btn btn-sm btn-outline-secondary zoom-toggle';
        zoomToggle.innerHTML = '<i class="fas fa-search-plus"></i>';
        zoomToggle.addEventListener('click', () => {
            mainImage.classList.toggle('zoomable');
            zoomToggle.innerHTML = mainImage.classList.contains('zoomable') ? 
                '<i class="fas fa-search-minus"></i>' : '<i class="fas fa-search-plus"></i>';
        });
        
        const imageContainer = mainImage.parentElement;
        if (imageContainer) {
            imageContainer.style.position = 'relative';
            zoomToggle.style.position = 'absolute';
            zoomToggle.style.bottom = '10px';
            zoomToggle.style.right = '10px';
            imageContainer.appendChild(zoomToggle);
        }
    }

    // Setup review functionality
    setupReviews() {
        const reviewForm = document.getElementById('reviewForm');
        const reviewRating = document.getElementById('reviewRating');
        const ratingStars = document.querySelectorAll('.rating-star');
        
        if (ratingStars.length > 0) {
            ratingStars.forEach(star => {
                star.addEventListener('click', () => {
                    const rating = star.getAttribute('data-rating');
                    
                    // Update selected rating
                    ratingStars.forEach(s => {
                        if (parseInt(s.getAttribute('data-rating')) <= rating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                    
                    if (reviewRating) {
                        reviewRating.value = rating;
                    }
                });
            });
        }
        
        if (reviewForm) {
            reviewForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                // In a real application, this would submit to the server
                this.submitReview(reviewForm);
            });
        }
    }

    // Submit review (simulated)
    submitReview(form) {
        const formData = new FormData(form);
        const reviewData = {
            rating: formData.get('rating'),
            title: formData.get('title'),
            comment: formData.get('comment'),
            name: formData.get('name'),
            email: formData.get('email')
        };
        
        // Simulate API call
        setTimeout(() => {
            // Show success message
            this.showNotification('Thank you for your review!');
            
            // Reset form
            form.reset();
            
            // Reset stars
            document.querySelectorAll('.rating-star').forEach(star => {
                star.classList.remove('active');
            });
        }, 1000);
    }

    // Setup related products carousel
    setupRelatedProducts() {
        const carousel = document.getElementById('relatedProductsCarousel');
        if (!carousel) return;
        
        // Initialize Bootstrap carousel if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
            new bootstrap.Carousel(carousel, {
                interval: 5000,
                wrap: true
            });
        }
    }

    // Setup event listeners
    setupEventListeners() {
        // Add to cart button
        const addToCartBtn = document.querySelector('.add-to-cart');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.addToCart();
            });
        }
        
        // Wishlist button
        const wishlistBtn = document.querySelector('.add-to-wishlist');
        if (wishlistBtn) {
            wishlistBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleWishlist();
            });
        }
        
        // Share buttons
        const shareButtons = document.querySelectorAll('.share-btn');
        shareButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const platform = button.getAttribute('data-platform');
                this.shareProduct(platform);
            });
        });
    }

    // Add product to cart
    addToCart() {
        // Get product data
        const productId = document.getElementById('productId').value;
        const productName = document.getElementById('productName').textContent;
        const productPrice = parseFloat(document.getElementById('productPrice').textContent.replace('$', ''));
        const productImage = document.getElementById('productMainImage').src;
        const quantity = parseInt(document.getElementById('productQuantity').value) || 1;
        
        // Get selected variants
        const variants = {};
        const variantSelects = document.querySelectorAll('.variant-select');
        variantSelects.forEach(select => {
            if (select.value) {
                variants[select.name] = select.value;
            }
        });
        
        // Check if all required variants are selected
        const requiredVariants = document.querySelectorAll('.variant-select[required]');
        let allVariantsSelected = true;
        
        requiredVariants.forEach(select => {
            if (!select.value) {
                allVariantsSelected = false;
                select.classList.add('is-invalid');
            } else {
                select.classList.remove('is-invalid');
            }
        });
        
        if (!allVariantsSelected) {
            this.showNotification('Please select all required options', 'error');
            return;
        }
        
        // Prepare product data
        const product = {
            id: productId,
            name: productName,
            price: productPrice,
            image: productImage,
            quantity: quantity,
            variants: variants
        };
        
        // Add to cart using the global cart object
        if (window.homewareCart && typeof window.homewareCart.addItem === 'function') {
            window.homewareCart.addItem(product);
        } else {
            // Fallback if cart is not available
            console.log('Product added to cart:', product);
            this.showNotification(`${productName} added to cart!`);
        }
    }

    // Toggle product in wishlist
    toggleWishlist() {
        const productId = document.getElementById('productId').value;
        const wishlistBtn = document.querySelector('.add-to-wishlist');
        const icon = wishlistBtn.querySelector('i');
        
        // Toggle wishlist state
        const isInWishlist = wishlistBtn.classList.contains('in-wishlist');
        
        if (isInWishlist) {
            // Remove from wishlist
            wishlistBtn.classList.remove('in-wishlist');
            icon.classList.remove('fas');
            icon.classList.add('far');
            this.showNotification('Removed from wishlist');
        } else {
            // Add to wishlist
            wishlistBtn.classList.add('in-wishlist');
            icon.classList.remove('far');
            icon.classList.add('fas');
            this.showNotification('Added to wishlist');
        }
        
        // In a real application, this would update the wishlist on the server
        // For now, we'll just use localStorage
        this.updateWishlistStorage(productId, !isInWishlist);
    }

    // Update wishlist in localStorage
    updateWishlistStorage(productId, add) {
        let wishlist = JSON.parse(localStorage.getItem('homewareontap_wishlist')) || [];
        
        if (add) {
            // Add to wishlist if not already there
            if (!wishlist.includes(productId)) {
                wishlist.push(productId);
            }
        } else {
            // Remove from wishlist
            wishlist = wishlist.filter(id => id !== productId);
        }
        
        localStorage.setItem('homewareontap_wishlist', JSON.stringify(wishlist));
    }

    // Share product on social media
    shareProduct(platform) {
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(document.getElementById('productName').textContent);
        const image = encodeURIComponent(document.getElementById('productMainImage').src);
        
        let shareUrl;
        
        switch(platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                break;
            case 'twitter':
                shareUrl = `https://twitter.com/intent/tweet?text=${title}&url=${url}`;
                break;
            case 'pinterest':
                shareUrl = `https://pinterest.com/pin/create/button/?url=${url}&media=${image}&description=${title}`;
                break;
            case 'email':
                shareUrl = `mailto:?subject=${title}&body=Check out this product: ${url}`;
                break;
            default:
                return;
        }
        
        window.open(shareUrl, '_blank');
    }

    // Show notification
    showNotification(message, type = 'success') {
        // Use existing notification element if available
        let notification = document.getElementById('productNotification');
        
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'productNotification';
            notification.className = 'notification';
            document.body.appendChild(notification);
        }
        
        notification.textContent = message;
        notification.className = `notification ${type} show`;
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
}

// Initialize product page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on product pages
    if (document.getElementById('productPage')) {
        window.productPage = new ProductPage();
    }
});

// Add CSS for product page interactions
const productStyles = `
    .product-thumbnail {
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.3s;
        border: 2px solid transparent;
    }
    
    .product-thumbnail:hover,
    .product-thumbnail.active {
        opacity: 1;
        border-color: #B78D65;
    }
    
    .quantity-selector {
        max-width: 140px;
    }
    
    .variant-select {
        margin-bottom: 10px;
    }
    
    .zoomable {
        cursor: zoom-in;
    }
    
    .zoomable.zoomed {
        transform: scale(1.5);
        cursor: zoom-out;
    }
    
    .zoom-toggle {
        z-index: 10;
    }
    
    .rating-star {
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }
    
    .rating-star:hover,
    .rating-star.active {
        color: #ffc107;
    }
    
    .product-tab {
        cursor: pointer;
    }
    
    .notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 15px 25px;
        background: #252525;
        color: white;
        border-radius: 5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transform: translateY(100px);
        opacity: 0;
        transition: transform 0.3s, opacity 0.3s;
        z-index: 1000;
    }
    
    .notification.success {
        background: #28a745;
    }
    
    .notification.error {
        background: #dc3545;
    }
    
    .notification.show {
        transform: translateY(0);
        opacity: 1;
    }
    
    .in-wishlist {
        color: #dc3545;
    }
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = productStyles;
document.head.appendChild(styleSheet);