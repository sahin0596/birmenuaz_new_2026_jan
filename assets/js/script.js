// Apply theme immediately on page load (before DOM is ready)
(function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

// Translations are loaded from translations.js
// Language data with flags
const languageData = {
    az: { flag: 'https://flagcdn.com/24x18/az.png', name: 'Azərbaycan', code: 'AZ' },
    ru: { flag: 'https://flagcdn.com/24x18/ru.png', name: 'Русский', code: 'RU' },
    en: { flag: 'https://flagcdn.com/24x18/gb.png', name: 'English', code: 'EN' }
};

// Language switching functionality
function initLanguageSwitcher() {
    const toggleBtn = document.getElementById('languageDropdownBtn');
    const dropdownMenu = document.getElementById('languageDropdownMenu');
    const langOptions = document.querySelectorAll('.language-option');
    const currentLangFlag = document.getElementById('currentLangFlag');
    const currentLangText = document.getElementById('currentLangText');
    
    if (!toggleBtn || !dropdownMenu) return;
    
    const currentLang = localStorage.getItem('language') || 'az';
    
    // Set initial language display
    updateLanguageDisplay(currentLang);
    
    // Set active language option
    langOptions.forEach(option => {
        if (option.dataset.lang === currentLang) {
            option.classList.add('active');
        } else {
            option.classList.redve('active');
        }
    });
    
    // Handle language option clicks
    langOptions.forEach(option => {
        option.addEventListener('click', function() {
            const lang = this.dataset.lang;
            
            // Update active state
            langOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            // Update display
            updateLanguageDisplay(lang);
            
            // Save to localStorage
            localStorage.setItem('language', lang);
            
            // Apply translations
            applyTranslations(lang);
        });
    });
    
    // Update language display helper
    function updateLanguageDisplay(lang) {
        const langInfo = languageData[lang] || languageData.az;
        if (currentLangFlag) {
            currentLangFlag.src = langInfo.flag;
            currentLangFlag.alt = `${langInfo.code} flag`;
        }
        if (currentLangText) currentLangText.textContent = langInfo.code;
    }
}

// Initialize i18next
function initI18next() {
    i18next.init({
        lng: localStorage.getItem('language') || 'az',
        debug: false,
        resources: translations
    }, function(err, t) {
        if (err) {
            console.error('i18next initialization failed:', err);
            return;
        }
        updateContent();
    });
}

// Apply translations to elements
function applyTranslations(lang) {
    i18next.changeLanguage(lang, function(err, t) {
        if (err) {
            console.error('Language change failed:', err);
            return;
        }
        updateContent();
        // Trigger language change event for components that need to update
        window.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang } }));
    });
}

// Update all translated content
function updateContent() {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        const translation = i18next.t(key);
        if (translation && translation !== key) {
            el.textContent = translation;
        }
    });
    
    // Update placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        const translation = i18next.t(key);
        if (translation && translation !== key) {
            el.placeholder = translation;
        }
    });
    
    // Update titles
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
        const key = el.getAttribute('data-i18n-title');
        const translation = i18next.t(key);
        if (translation && translation !== key) {
            el.title = translation;
        }
    });
}

// Menu Data - API-dən yüklənir (dinamik kateqoriyalar)
let menuData = {};

// Cache all items for filtering/cart
let cachedItems = [];
let fadeObserver;

// Restaurants data
let restaurantsData = [];

// Fetch restaurants with improved performance
async function fetchRestaurants() {
    const container = document.getElementById('menuItems');
    if (!container) return;
    
    // Show loading state
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border" role="status" style="width: 3rem; height: 3rem; color: var(--primary-color);">
                <span class="visually-hidden">Yüklənir...</span>
            </div>
            <p class="mt-3" style="color: var(--text-secondary);">Restoranlar yüklənir...</p>
        </div>
    `;
    
    try {
        const response = await fetch('api/api.php?action=get_restaurants');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Xəta baş verdi');
        
        restaurantsData = data.restaurants || [];
        renderRestaurants();
        // Update stats-grid restaurant count to match admin panel (same API: get_restaurants)
        const count = data.restaurants.length;
        const restaurantStat = document.querySelector('.stat-card[data-stat="restaurants"] .stat-value');
        if (restaurantStat) {
            restaurantStat.setAttribute('data-target', String(count));
            if (restaurantStat.classList.contains('animated')) {
                restaurantStat.textContent = count.toLocaleString('en-US');
            } else {
                restaurantStat.classList.add('animated');
                animateCounter(restaurantStat, count, 2000);
            }
        }
        // Update orders stat = total products across all restaurants (admin panel menu products sum)
        const totalProducts = (data.restaurants || []).reduce((sum, r) => sum + (parseInt(r.product_count, 10) || 0), 0);
        const ordersStat = document.querySelector('.stat-card[data-stat="orders"] .stat-value');
        if (ordersStat) {
            ordersStat.setAttribute('data-target', String(totalProducts));
            if (ordersStat.classList.contains('animated')) {
                ordersStat.textContent = totalProducts.toLocaleString('en-US');
            } else {
                ordersStat.classList.add('animated');
                animateCounter(ordersStat, totalProducts, 2000);
            }
        }
    } catch (error) {
        console.error('Restoranlar yüklənmədi:', error);
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-exclamation-triangle" style="font-size: 4rem; color: var(--text-muted); opacity: 0.3;"></i>
                <p class="mt-3" style="color: var(--text-secondary);">Restoranlar yüklənmədi. Xahiş edirik, daha sonra yenidən cəhd edin.</p>
                <button class="btn btn-primary mt-3" onclick="fetchRestaurants()">Yenidən yüklə</button>
            </div>
        `;
    }
}

// Render restaurants as logo slider with lazy loading
function renderRestaurants() {
    const container = document.getElementById('menuItems');
    if (!container) return;
    
    if (restaurantsData.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-shop" style="font-size: 4rem; color: var(--text-muted); opacity: 0.3;"></i>
                <p class="mt-3" style="color: var(--text-secondary);">Hələ restoran əlavə edilməyib</p>
            </div>
        `;
        return;
    }
    
    // Create logo cards for slider
    const activeRestaurants = restaurantsData.filter(r => r.is_active == 1);
    
    if (activeRestaurants.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-shop" style="font-size: 4rem; color: var(--text-muted); opacity: 0.3;"></i>
                <p class="mt-3" style="color: var(--text-secondary);">Aktiv restoran yoxdur</p>
            </div>
        `;
        return;
    }
    
    // Duplicate restaurants for infinite scroll effect (reduced from 3x to 2x for performance)
    const duplicatedRestaurants = [...activeRestaurants, ...activeRestaurants];
    
    container.innerHTML = duplicatedRestaurants.map((restaurant, index) => {
        const logoImage = restaurant.logo_path ? 
            `<img src="${restaurant.logo_path}" alt="${restaurant.name}" loading="lazy" decoding="async">` : 
            `<div class="restaurant-logo-placeholder"><i class="bi bi-shop"></i></div>`;
        
        return `
            <div class="restaurant-logo-card" title="${restaurant.name}">
                <div class="restaurant-logo-image">
                    ${logoImage}
                </div>
            </div>
        `;
    }).join('');
    
    // Initialize lazy loading observer for images
    initRestaurantImageLazyLoad();
}

// Scroll-based animasiyalar
function initScrollAnimations() {
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('.fade-in').forEach(el => el.classList.add('in-view'));
        return;
    }

    fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                fadeObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2, rootMargin: '0px 0px -10% 0px' });

    observeFadeElements(document.querySelectorAll('.fade-in'));
}

// Lazy load restaurant images
function initRestaurantImageLazyLoad() {
    if (!('IntersectionObserver' in window)) return;
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                // Image is already loaded with loading="lazy" attribute
                // Just add fade-in effect
                img.style.opacity = '1';
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.01
    });
    
    // Observe all restaurant logo images
    const restaurantImages = document.querySelectorAll('.restaurant-logo-card img');
    restaurantImages.forEach(img => {
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease-in-out';
        imageObserver.observe(img);
    });
}

function observeFadeElements(elements) {
    if (!fadeObserver || !elements) return;
    elements.forEach(el => fadeObserver.observe(el));
}

// API-dən menyu məlumatını yüklə (yalnız restoran səhifələrində)
async function fetchMenuData() {
    try {
        const response = await fetch('../api/api.php?action=get');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Xəta baş verdi');

        const grouped = {};

        (data.products || []).forEach(p => {
            const categoryId = p.category_id || 'uncategorized';
            if (!grouped[categoryId]) {
                grouped[categoryId] = [];
            }
            grouped[categoryId].push({
                id: Number(p.id),
                name: p.name,
                description: p.description || '',
                price: parseFloat(p.price).toFixed(2),
                discount_price: p.discount_price ? parseFloat(p.discount_price).toFixed(2) : null,
                image: '🍽️',
                imageUrl: p.image_path || '',
                category_id: p.category_id,
                category_name: p.category_name || 'Kateqoriyasız'
            });
        });

        menuData = grouped;
        cachedItems = Object.values(grouped).flat();
        renderMenuItems(cachedItems);
        renderAdminProducts();
    } catch (error) {
        const menuContainer = document.getElementById('menuItems');
        if (menuContainer) {
            menuContainer.innerHTML = `
                <div class="col-12 text-center py-5">
                    <p class="text-danger">Məhsullar yüklənmədi: ${error.message}</p>
                </div>
            `;
        }
        cachedItems = [];
    }
}

// Menyunu başlat
function initMenu() {
    fetchMenuData();

    // Kateqoriya düymələri
    document.querySelectorAll('.btn-category').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.btn-category').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');

            const category = this.getAttribute('data-category');
            if (category === 'all') {
                renderMenuItems(cachedItems);
            } else {
                const filteredItems = cachedItems.filter(item => item.category_id == category);
                renderMenuItems(filteredItems);
            }
        });
    });
}


// Render menu items (for restaurant menu pages, not main page)
function renderMenuItems(items) {
    const menuContainer = document.getElementById('menuItems');
    if (!menuContainer) return; // Skip if not on menu page
    
    // Check if we're on the main page (restaurants page) - if so, don't render menu items
    const isRestaurantsPage = document.getElementById('restaurants') || 
                              (menuContainer.closest('#restaurants') !== null);
    if (isRestaurantsPage && items.length > 0 && items[0].category) {
        // This is menu items, but we're on restaurants page, so skip
        return;
    }
    
    menuContainer.innerHTML = '';
    
    if (items.length === 0) {
        menuContainer.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox" style="font-size: 4rem; color: var(--text-muted);"></i>
                <p class="mt-3" style="color: var(--text-secondary);">Bu kateqoriyada yemək tapılmadı</p>
            </div>
        `;
        return;
    }

    // Qrupla: kateqoriya adı başlıq kimi görünsün (sıra ilk görünüşə görə)
    const groups = [];
    const seen = new Set();
    items.forEach(item => {
        const cid = item.category_id ?? 'uncategorized';
        if (!seen.has(cid)) {
            seen.add(cid);
            groups.push({
                category_id: cid,
                category_name: item.category_name || 'Kateqoriyasız',
                items: []
            });
        }
        const group = groups.find(g => (g.category_id == cid));
        if (group) group.items.push(item);
    });

    let globalIndex = 0;
    groups.forEach(group => {
        menuContainer.innerHTML += `<div class="col-12 menu-category-section" data-category-id="${group.category_id}"><h2 class="menu-category-heading">${group.category_name}</h2></div>`;
        group.items.forEach(item => {
            const delay = (globalIndex++ * 0.05).toFixed(2);
            const menuItemHTML = `
                <div class="col-6 col-md-6 col-lg-4">
                <div class="menu-item fade-in" style="--delay: ${delay}s" data-category="${item.category_id || ''}">
                    <div class="menu-item-image">
                        ${item.imageUrl ? `<img src="${item.imageUrl}" alt="${item.name}" class="menu-item-img-full" onerror="this.remove();">` : (item.image || '🍽️')}
                    </div>
                    <div class="menu-item-body">
                        <h3 class="menu-item-title">${item.name}</h3>
                        <p class="menu-item-description">${item.description}</p>
                        <div class="menu-item-footer">
                            ${item.discount_price ? 
                                `<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span class="menu-item-price">${item.discount_price} ₼</span>
                                    <span style="font-size: 0.9rem; color: var(--text-secondary); text-decoration: line-through;">${item.price} ₼</span>
                                </div>` : 
                                `<span class="menu-item-price">${item.price} ₼</span>`
                            }
                        </div>
                        <button class="btn-add-to-cart" onclick="addToCart(${item.id})" title="Səbətə əlavə et">
                            <span class="add-icon">+</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
            menuContainer.innerHTML += menuItemHTML;
        });
    });

    observeFadeElements(menuContainer.querySelectorAll('.fade-in'));
    initMenuScrollSpy();
}

// Scroll-spy: səhifə aşağı endikcə görünən kateqoriyaya uyğun düymə avtomatik aktiv olsun
let menuScrollSpyObserver = null;
function initMenuScrollSpy() {
    const menuContainer = document.getElementById('menuItems');
    const sections = menuContainer ? menuContainer.querySelectorAll('.menu-category-section[data-category-id]') : [];
    const buttons = document.querySelectorAll('.btn-category');
    if (!sections.length || !buttons.length) return;

    if (menuScrollSpyObserver) {
        menuScrollSpyObserver.disconnect();
        menuScrollSpyObserver = null;
    }

    const intersecting = new Set();
    const headerOffset = 120;

    function setActiveFromIntersecting() {
        let best = null;
        let bestTop = Infinity;
        intersecting.forEach(el => {
            const top = el.getBoundingClientRect().top;
            if (top < bestTop && top > -200) {
                bestTop = top;
                best = el;
            }
        });
        if (!best && intersecting.size) {
            const arr = Array.from(intersecting);
            arr.sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top);
            best = arr[0];
        }
        const cid = best ? best.getAttribute('data-category-id') : 'all';
        buttons.forEach(btn => {
            const isActive = (cid === 'all' && btn.getAttribute('data-category') === 'all') || btn.getAttribute('data-category') === String(cid);
            btn.classList.toggle('active', isActive);
        });
    }

    menuScrollSpyObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) intersecting.add(entry.target);
                else intersecting.delete(entry.target);
            });
            requestAnimationFrame(setActiveFromIntersecting);
        },
        { rootMargin: `-${headerOffset}px 0px -40% 0px`, threshold: [0, 0.01, 0.1] }
    );

    sections.forEach(section => menuScrollSpyObserver.observe(section));
}

// Helpers to keep menu and admin in sync
function getActiveCategory() {
    const active = document.querySelector('.btn-category.active');
    return active ? active.getAttribute('data-category') : 'all';
}

function refreshMenuAfterDataChange() {
    const category = getActiveCategory();
    if (category === 'all') {
        renderMenuItems(cachedItems);
    } else {
        renderMenuItems(cachedItems.filter(item => item.category_id == category));
    }
}

// Render admin product list
function renderAdminProducts() {
    const list = document.getElementById('adminProductList');
    const searchInput = document.getElementById('adminSearch');
    const categorySelect = document.getElementById('adminCategoryFilter');
    if (!list || !searchInput || !categorySelect) return;

    const searchTerm = searchInput.value.trim().toLowerCase();
    const category = categorySelect.value;

    const filtered = cachedItems.filter(item => {
        const matchesSearch = item.name.toLowerCase().includes(searchTerm) || (item.description || '').toLowerCase().includes(searchTerm);
        const matchesCategory = category === 'all' ? true : item.category_id == category;
        return matchesSearch && matchesCategory;
    });

    if (filtered.length === 0) {
        list.innerHTML = '<div class="no-products">Məhsul tapılmadı</div>';
        return;
    }

    list.innerHTML = filtered.map(item => `
        <div class="admin-product-item">
            <div class="admin-product-image">
                ${item.imageUrl ? `<img src="${item.imageUrl}" alt="${item.name}" onerror="this.remove();">` : '<span class="product-emoji">🍽️</span>'}
            </div>
            <div class="admin-product-info">
                <h4>${item.name}</h4>
                <p>${item.description || 'Açıqlama yoxdur'}</p>
                <div>
                    <span class="admin-product-price">${item.price} ₼</span>
                    <span class="admin-badge">${item.category_name || 'Kateqoriyasız'}</span>
                </div>
            </div>
            <button class="btn-delete-product" data-id="${item.id}" title="Sil">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `).join('');
}

function removeProductFromMenu(itemId) {
    Object.keys(menuData).forEach(cat => {
        menuData[cat] = menuData[cat].filter(item => item.id !== itemId);
    });
    cachedItems = cachedItems.filter(item => item.id !== itemId);
    cart = cart.filter(item => item.id !== itemId);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
    refreshMenuAfterDataChange();
    renderAdminProducts();
}

// Cart functionality
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Get all menu items
function getAllMenuItems() {
    return cachedItems;
}

// Add to cart
function addToCart(itemId) {
    const allItems = getAllMenuItems();
    const item = allItems.find(i => i.id === itemId);
    
    if (!item) return;
    
    const existingItem = cart.find(c => c.id === itemId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            ...item,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
    
    // Show notification
    showCartNotification();
}

// Remove from cart
function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartUI();
}

// Update quantity
function updateQuantity(itemId, change) {
    const item = cart.find(c => c.id === itemId);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(itemId);
            return;
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartUI();
    }
}

// Update cart UI
function updateCartUI() {
    const cartCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartTotal = cart.reduce((sum, item) => {
        const price = item.discount_price || item.price;
        return sum + (parseFloat(price) * item.quantity);
    }, 0);
    
    // Update cart count badge
    const cartCountEl = document.getElementById('cartCount');
    if (cartCountEl) {
        cartCountEl.textContent = cartCount;
    }
    
    // Update cart button visibility
    const cartButtonContainer = document.getElementById('cartButtonContainer');
    if (cartButtonContainer) {
        if (cartCount > 0) {
            cartButtonContainer.style.display = 'flex';
        } else {
            cartButtonContainer.style.display = 'none';
        }
    }
    
    // Update cart panel
    const cartItems = document.getElementById('cartItems');
    const cartTotalElement = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');
    
    if (cartTotalElement) {
        cartTotalElement.textContent = cartTotal.toFixed(2) + ' ₼';
    }
    if (checkoutBtn) {
        checkoutBtn.disabled = cartCount === 0;
    }
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="cart-empty">
                <i class="bi bi-cart-x"></i>
                <p>Səbət boşdur</p>
            </div>
        `;
    } else {
        cartItems.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <span class="cart-item-image">
                        ${item.imageUrl ? `<img src="${item.imageUrl}" alt="${item.name}" class="cart-thumb" onerror="this.remove();">` : (item.image || '🍽️')}
                    </span>
                    <div class="cart-item-details">
                        <h4 class="cart-item-name">${item.name}</h4>
                        <p class="cart-item-price">${item.discount_price || item.price} ₼ ${item.discount_price ? `<span style="text-decoration: line-through; font-size: 0.8rem; color: var(--text-muted); margin-left: 6px;">${item.price} ₼</span>` : ''}</p>
                    </div>
                </div>
                <div class="cart-item-controls">
                    <button class="cart-qty-btn" onclick="updateQuantity(${item.id}, -1)">
                        <i class="bi bi-dash"></i>
                    </button>
                    <span class="cart-item-qty">${item.quantity}</span>
                    <button class="cart-qty-btn" onclick="updateQuantity(${item.id}, 1)">
                        <i class="bi bi-plus"></i>
                    </button>
                    <button class="cart-remove-btn" onclick="removeFromCart(${item.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }
}

// Show cart notification
function showCartNotification() {
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = '<i class="bi bi-check-circle"></i> Səbətə əlavə edildi';
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 2000);
}

// ========================================
// MOBILE MENU FUNCTIONALITY
// ========================================

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing mobile menu...');
    
    // Get elements
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileNav = document.getElementById('mobileNav');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const mobileServicesToggle = document.getElementById('mobileServicesToggle');
    const mobileServicesMenu = document.getElementById('mobileServicesMenu');

    // Debug: Log elements
    console.log('Mobile Menu Elements:', {
        toggle: mobileMenuToggle ? 'Found' : 'NOT FOUND',
        nav: mobileNav ? 'Found' : 'NOT FOUND',
        overlay: mobileMenuOverlay ? 'Found' : 'NOT FOUND'
    });

    // Check if required elements exist
    if (!mobileMenuToggle || !mobileNav) {
        console.error('ERROR: Required mobile menu elements not found!');
        return;
    }

    console.log('All elements found. Setting up event listeners...');

    // ===== TOGGLE MOBILE MENU =====
    mobileMenuToggle.addEventListener('click', function(event) {
        console.log('=== MOBILE MENU BUTTON CLICKED ===');
        event.preventDefault();
        event.stopPropagation();
        
        const isCurrentlyActive = this.classList.contains('active');
        console.log('Current menu state:', isCurrentlyActive ? 'OPEN' : 'CLOSED');
        
        // Toggle button
        this.classList.toggle('active');
        // Toggle menu
        mobileNav.classList.toggle('active');
        // Toggle overlay
        if (mobileMenuOverlay) {
            mobileMenuOverlay.classList.toggle('active');
        }
        
        // Update ARIA
        this.setAttribute('aria-expanded', !isCurrentlyActive);
        
        console.log(!isCurrentlyActive ? 'Menu opened' : 'Menu closed');
    });

    console.log('✓ Main toggle event listener added');

    // ===== CLOSE MENU FUNCTION =====
    function closeMobileMenu() {
        console.log('Closing mobile menu...');
        mobileMenuToggle.classList.remove('active');
        mobileNav.classList.remove('active');
        if (mobileMenuOverlay) {
            mobileMenuOverlay.classList.remove('active');
        }
        mobileMenuToggle.setAttribute('aria-expanded', 'false');
    }

    // ===== CLOSE ON OVERLAY CLICK =====
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', function() {
            console.log('Overlay clicked - closing menu');
            closeMobileMenu();
        });
        console.log('✓ Overlay click listener added');
    }

    // ===== SUBMENU TOGGLE =====
    if (mobileServicesToggle && mobileServicesMenu) {
        mobileServicesToggle.addEventListener('click', function(event) {
            event.preventDefault();
            console.log('Services submenu toggle clicked');
            
            const parent = this.closest('.mobile-nav-item');
            if (parent) {
                parent.classList.toggle('active');
            }
            mobileServicesMenu.classList.toggle('active');
        });
        console.log('✓ Submenu toggle listener added');
    }

    // ===== CLOSE ON MENU LINK CLICK =====
    const menuLinks = document.querySelectorAll('.mobile-nav-link:not(#mobileServicesToggle), .mobile-submenu-item');
    if (menuLinks.length > 0) {
        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                console.log('Menu link clicked - closing menu');
                closeMobileMenu();
            });
        });
        console.log('✓ Link click listeners added (' + menuLinks.length + ' links)');
    }

    // ===== CLOSE ON WINDOW RESIZE =====
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && mobileNav.classList.contains('active')) {
            console.log('Window resized above 768px - closing menu');
            closeMobileMenu();
        }
    });
    console.log('✓ Resize listener added');

    // ===== CLOSE ON ESCAPE KEY =====
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && mobileNav.classList.contains('active')) {
            console.log('Escape key pressed - closing menu');
            closeMobileMenu();
        }
    });
    console.log('✓ Escape key listener added');

    console.log('=== MOBILE MENU INITIALIZATION COMPLETE ===');
});

// Update Logo and Phone Image based on theme
function updateLogo() {
    const navbarLogo = document.getElementById('navbarLogo');
    const footerLogo = document.getElementById('footerLogo');
    const heroImage = document.getElementById('heroImage');
    const currentTheme = document.documentElement.getAttribute('data-theme');
    
    // Navbar logo: həmişə logo.png (qaranlıq rejimdə rənglər CSS invert ilə tərsinə çevrilir)
    if (navbarLogo && !navbarLogo.src.includes('logo.png')) {
        const currentSrc = navbarLogo.getAttribute('src') || navbarLogo.src;
        const basePath = currentSrc.substring(0, currentSrc.lastIndexOf('/') + 1);
        navbarLogo.src = basePath + 'logo.png';
    }
    
    // Update Footer Logo
    if (footerLogo) {
        const currentSrc = footerLogo.getAttribute('src') || footerLogo.src;
        const basePath = currentSrc.substring(0, currentSrc.lastIndexOf('/') + 1);
        
        if (currentTheme === 'dark') {
            footerLogo.src = basePath + 'logo.png';
        } else {
            footerLogo.src = basePath + 'logo.png';
        }
    }
    
   if (heroImage) {
    heroImage.src = 'assets/images/qr_birmenu.png';
}
}

// Theme Toggle Functionality
function initThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    // Get saved theme or default to 'light'
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Update logo on page load
    updateLogo();
    
    // Update icon based on current theme
    if (savedTheme === 'dark') {
        if (themeIcon) {
            themeIcon.className = 'bi bi-moon-fill';
        }
    } else {
        if (themeIcon) {
            themeIcon.className = 'bi bi-sun-fill';
        }
    }
    
    // Toggle theme on button click
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Dispatch custom event for restaurant pages
            window.dispatchEvent(new CustomEvent('themechange', {
                detail: { theme: newTheme }
            }));
            
            // Update logo
            updateLogo();
            
            // Update icon
            if (themeIcon) {
                if (newTheme === 'dark') {
                    themeIcon.className = 'bi bi-moon-fill';
                } else {
                    themeIcon.className = 'bi bi-sun-fill';
                }
            }
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initI18next();
    initThemeToggle();
    initLanguageSwitcher();
    initScrollAnimations();
    fetchRestaurants();
    // initMenu(); // Only call on restaurant menu pages, not on main page
    updateCartUI();
    
    // Cart button click
    const cartButton = document.getElementById('cartButton');
    if (cartButton) {
        cartButton.addEventListener('click', function() {
            const cartPanel = document.getElementById('cartPanel');
            if (cartPanel) {
                cartPanel.classList.add('show');
            }
        });
    }
    
    // Close cart panel
    const closeCartBtn = document.getElementById('closeCart');
    if (closeCartBtn) {
        closeCartBtn.addEventListener('click', function() {
            const cartPanel = document.getElementById('cartPanel');
            if (cartPanel) {
                cartPanel.classList.remove('show');
            }
        });
    }
    
    // Checkout button
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
        if (cart.length > 0) {
            const total = cart.reduce((sum, item) => {
                const price = item.discount_price || item.price;
                return sum + (parseFloat(price) * item.quantity);
            }, 0);
            alert('Sifarişiniz qəbul edildi! Ümumi məbləğ: ' + total.toFixed(2) + ' ₼');
            cart = [];
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartUI();
            const cartPanel = document.getElementById('cartPanel');
            if (cartPanel) {
                cartPanel.classList.remove('show');
            }
        }
    });
    }
    
    // Smooth scroll for better UX
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Admin form submit
    const adminForm = document.getElementById('adminForm');
    if (adminForm) {
        adminForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('productName').value.trim();
            const description = document.getElementById('productDescription').value.trim();
            const priceValue = parseFloat(document.getElementById('productPrice').value || '0');
            const category = document.getElementById('productCategory').value;
            const imageUrl = document.getElementById('productImageUrl').value.trim();
            if (!name || Number.isNaN(priceValue) || !category) return;
            const price = priceValue.toFixed(2);

            const newItem = {
                id: Date.now(),
                name,
                description,
                price,
                image: '🍽️',
                imageUrl,
                category_id: category
            };

            const categoryKey = category || 'uncategorized';
            menuData[categoryKey] = menuData[categoryKey] || [];
            menuData[categoryKey].push(newItem);
            cachedItems.push(newItem);
            refreshMenuAfterDataChange();
            renderAdminProducts();
            adminForm.reset();
        });
    }

    // Admin filters
    const adminSearch = document.getElementById('adminSearch');
    const adminCategoryFilter = document.getElementById('adminCategoryFilter');
    if (adminSearch) {
        adminSearch.addEventListener('input', renderAdminProducts);
    }
    if (adminCategoryFilter) {
        adminCategoryFilter.addEventListener('change', renderAdminProducts);
    }

    // Admin delete buttons (event delegation)
    const adminProductList = document.getElementById('adminProductList');
    if (adminProductList) {
        adminProductList.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-delete-product');
            if (btn) {
                const id = Number(btn.dataset.id);
                removeProductFromMenu(id);
            }
        });
    }

    renderAdminProducts();
});


// Header Scroll Effect
function updateHeaderOnScroll() {
    const header = document.querySelector('.header-section');
    if (!header) return;
    
    if (window.pageYOffset > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
}

window.addEventListener('scroll', () => {
    updateHeaderOnScroll();
});

// Particles Animation
function createParticles() {
    const container = document.getElementById('particlesContainer');
    if (!container) return;
    
    const particleCount = 20;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        const size = Math.random() * 4 + 2;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        particle.style.left = `${Math.random() * 100}%`;
        particle.style.animationDelay = `${Math.random() * 15}s`;
        particle.style.animationDuration = `${Math.random() * 10 + 10}s`;
        
        // Random colors
        const colors = [
            'rgba(108, 92, 231, 0.3)',
            'rgba(253, 121, 168, 0.3)',
            'rgba(162, 155, 254, 0.3)'
        ];
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];
        
        container.appendChild(particle);
    }
}

// Tilt Effect for Cards
function initTiltEffect() {
    const tiltElements = document.querySelectorAll('[data-tilt]');
    
    tiltElements.forEach(element => {
        element.addEventListener('mousemove', handleTilt);
        element.addEventListener('mouseleave', resetTilt);
    });
}

function handleTilt(e) {
    const rect = this.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    
    const centerX = rect.width / 2;
    const centerY = rect.height / 2;
    
    const rotateX = (y - centerY) / 10;
    const rotateY = (centerX - x) / 10;
    
    this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
}

function resetTilt() {
    this.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale(1)';
}

// Parallax Scroll Effect
function initParallaxScroll() {
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.hero-orb');
        
        parallaxElements.forEach((element, index) => {
            const speed = (index + 1) * 0.5;
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    });
}

// Ripple Effect on Click
function addRippleEffect() {
    document.querySelectorAll('.ripple').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple-effect');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
}

// Counter Animation for Numbers
function animateCounter(element, target, duration = 2000) {
    let start = 0;
    const increment = target / (duration / 16);
    
    const formatNumber = (num) => {
        return Math.ceil(num).toLocaleString('en-US');
    };
    
    const timer = setInterval(() => {
        start += increment;
        if (start >= target) {
            element.textContent = formatNumber(target);
            clearInterval(timer);
        } else {
            element.textContent = formatNumber(start);
        }
    }, 16);
}

// Smooth Card Entrance - Minimal
function initCardAnimations() {
    const cards = document.querySelectorAll('.menu-item, .service-card, .contact-card, .visual-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 50); // Faster stagger
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -30px 0px'
    });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(15px)'; // Less movement
        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease'; // Faster
        observer.observe(card);
    });
}

// Magnetic Button Effect
function initMagneticButtons() {
    const buttons = document.querySelectorAll('.btn-hero, .cart-button');
    
    buttons.forEach(button => {
        button.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            this.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`;
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translate(0, 0)';
        });
    });
}

// Text Reveal Animation
function initTextReveal() {
    const textElements = document.querySelectorAll('h1, h2, h3');
    
    textElements.forEach(element => {
        const text = element.textContent;
        element.textContent = '';
        element.style.opacity = '1';
        
        let i = 0;
        const interval = setInterval(() => {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
            } else {
                clearInterval(interval);
            }
        }, 50);
    });
}

// Cursor Trail Effect
function initCursorTrail() {
    const trail = [];
    const trailLength = 10;
    
    document.addEventListener('mousemove', (e) => {
        const dot = document.createElement('div');
        dot.style.position = 'fixed';
        dot.style.width = '5px';
        dot.style.height = '5px';
        dot.style.borderRadius = '50%';
        dot.style.background = 'rgba(108, 92, 231, 0.5)';
        dot.style.pointerEvents = 'none';
        dot.style.left = e.clientX + 'px';
        dot.style.top = e.clientY + 'px';
        dot.style.zIndex = '9999';
        dot.style.transition = 'opacity 0.5s';
        
        document.body.appendChild(dot);
        trail.push(dot);
        
        if (trail.length > trailLength) {
            const oldDot = trail.shift();
            oldDot.style.opacity = '0';
            setTimeout(() => oldDot.remove(), 500);
        }
    });
}

// Initialize all animations - Minimal version
// Initialize Statistics Counter Animation
function initStatsAnimation() {
    const statCards = document.querySelectorAll('.stat-card');
    
    if (statCards.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const statValue = entry.target.querySelector('.stat-value');
                const target = parseInt(statValue.getAttribute('data-target'));
                
                if (statValue && target && !statValue.classList.contains('animated')) {
                    statValue.classList.add('animated');
                    animateCounter(statValue, target, 2000);
                }
            }
        });
    }, {
        threshold: 0.5,
        rootMargin: '0px'
    });
    
    statCards.forEach(card => {
        observer.observe(card);
    });
}

// Partners section – professional scroll-in animation
function initPartnersSectionAnimation() {
    const section = document.querySelector('.menu-section#restaurants');
    if (!section) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                section.classList.add('partners-visible');
                observer.unobserve(section);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    observer.observe(section);
}

function initAllAnimations() {
    // Minimal animations - only essential effects
    addRippleEffect();
    
    // Run card animations after a short delay
    setTimeout(() => {
        initCardAnimations();
    }, 300);
    
    // Partners section (Partnyorlarımız) scroll animation
    initPartnersSectionAnimation();
    
    // Initialize statistics counter animation
    setTimeout(() => {
        initStatsAnimation();
    }, 500);
    
    // Initialize navbar hide/show on scroll
    initNavbarScroll();
}

// Navbar Hide/Show on Scroll
function initNavbarScroll() {
    const header = document.querySelector('.header-section');
    const topbar = document.querySelector('.restaurant-page .site-topbar');
    const target = header || topbar;
    if (!target) return;

    const hiddenClass = header ? 'hidden' : 'topbar-hidden';
    const isRestaurantPage = !!topbar;
    let lastScrollTop = 0;
    let scrollThreshold = 100;

    window.addEventListener('scroll', () => {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

        // Show navbar when scrolling up or at the top
        if (scrollTop < scrollThreshold) {
            target.classList.remove(hiddenClass);
            if (isRestaurantPage) document.body.style.paddingTop = '';
        } else if (scrollTop < lastScrollTop) {
            // Scrolling up
            target.classList.remove(hiddenClass);
            if (isRestaurantPage) document.body.style.paddingTop = '';
        } else {
            // Scrolling down
            target.classList.add(hiddenClass);
            if (isRestaurantPage) document.body.style.paddingTop = '0';
        }

        lastScrollTop = scrollTop;
    });
}

// WhatsApp Chatbot Functionality
function initWhatsAppChatbot() {
    const chatbotButton = document.getElementById('chatbotButton');
    const chatbotMessage = document.getElementById('chatbotMessage');
    const chatbotClose = document.getElementById('chatbotClose');
    const chatbotInput = document.getElementById('chatbotInput');
    const chatbotSend = document.getElementById('chatbotSend');
    const chatbotMessages = document.getElementById('chatbotMessages');
    const chatbotBody = document.getElementById('chatbotBody');
    const chatbotBadge = document.getElementById('chatbotBadge');
    const chatbotPreviewBubble = document.getElementById('chatbotPreviewBubble');
    const chatbotPreviewText = document.getElementById('chatbotPreviewText');
    const whatsappChatBot = document.getElementById('whatsappChatBot');

    if (whatsappChatBot) {
        ['click', 'touchstart', 'mousedown', 'mouseup', 'pointerdown'].forEach(e => {
            whatsappChatBot.addEventListener(e, function(event) {
                event.stopPropagation();

                const parentLinks = event.target.closest('a');

                if (parentLinks && !parentLinks.classList.contains('chatbot-whatsapp-button')) {
                    e.preventDefault();
                }
            }, true);
        })
    }
    
    if (!chatbotButton || !chatbotMessage || !chatbotClose || !chatbotInput || !chatbotSend || !chatbotMessages) return;
    
    let isFirstMessage = true;
    let messageHistory = [];
    
    // Get current language
    function getCurrentLang() {
        return localStorage.getItem('language') || 'az';
    }
    
    // Add message to chat
    function addMessage(text, isUser = false, showTime = true) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-bubble ${isUser ? 'user' : 'bot'}`;
        
        const messageContent = document.createElement('p');
        messageContent.textContent = text;
        messageDiv.appendChild(messageContent);
        
        if (showTime) {
            const timeDiv = document.createElement('span');
            timeDiv.className = 'chatbot-time';
            const now = new Date();
            timeDiv.textContent = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            messageDiv.appendChild(timeDiv);
        }
        
        chatbotMessages.appendChild(messageDiv);
        scrollToBottom();
        
        // Save to history
        messageHistory.push({ text, isUser, timestamp: new Date() });
    }
    
    // Scroll to bottom
    function scrollToBottom() {
        chatbotBody.scrollTop = chatbotBody.scrollHeight;
    }
    
    // Get bot response - simple message to redirect to WhatsApp
    function getBotResponse(userMessage) {
        const lang = getCurrentLang();
        
        const responses = {
            az: 'Zəhmət olmasa WhatsApp ilə əlaqə saxlayın',
            en: 'Please contact us via WhatsApp',
            ru: 'Пожалуйста, свяжитесь с нами через WhatsApp'
        };
        
        return responses[lang] || responses.az;
    }
    
    // Send message
    function sendMessage() {
        const message = chatbotInput.value.trim();
        if (!message) return;
        
        // Add user message
        addMessage(message, true);
        chatbotInput.value = '';
        chatbotSend.disabled = true;
        chatbotBadge.classList.remove('active');
        
        // Remove existing WhatsApp button if any
        const existingWhatsAppBtn = document.getElementById('chatbotWhatsAppButton');
        if (existingWhatsAppBtn) {
            existingWhatsAppBtn.remove();
        }
        
        // Simulate bot typing delay
        setTimeout(() => {
            const botResponse = getBotResponse(message);
            addMessage(botResponse, false);
            
            // Add WhatsApp button after bot response
            setTimeout(() => {
                addWhatsAppButton();
                chatbotSend.disabled = false;
            }, 300);
        }, 800 + Math.random() * 500);
    }
    
    // Add WhatsApp button to chat
    function addWhatsAppButton() {
        // Check if button already exists
        if (document.getElementById('chatbotWhatsAppButton')) return;
        
        const lang = getCurrentLang();
        const buttonTexts = {
            az: 'WhatsApp-la Əlaqə',
            en: 'Contact via WhatsApp',
            ru: 'Связаться через WhatsApp'
        };
        
        // Create WhatsApp button container
        const whatsappButtonContainer = document.createElement('div');
        whatsappButtonContainer.id = 'chatbotWhatsAppButton';
        whatsappButtonContainer.style.cssText = 'padding: 0 16px 16px; margin-top: -4px;';
        
        const whatsappBtn = document.createElement('a');
        whatsappBtn.href = 'javascript:void(0)';
        whatsappBtn.className = 'chatbot-whatsapp-button';
        whatsappBtn.style.cssText = `
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #4338ca 100%);
            color: #ffffff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        `;
        
        whatsappBtn.innerHTML = `<i class="bi bi-whatsapp"></i> <span>${buttonTexts[lang] || buttonTexts.az}</span>`;
        
        // Add click handler
        whatsappBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = getCurrentLang();
            const messages = {
                az: 'Salam, Köməyə ehtiyacım var',
                en: 'Hello, I need help',
                ru: 'Здравствуйте, мне нужна помощь'
            };
            
            const message = messages[lang] || messages.az;
            const whatsappNumber = '994507736216';
            const encodedMessage = encodeURIComponent(message);
            const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
            
            window.open(whatsappUrl, '_blank');
        });
        
        whatsappBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 6px 20px rgba(37, 99, 235, 0.4)';
            this.style.background = 'linear-gradient(135deg, #2563eb 0%, #4338ca 100%)';
        });
        
        whatsappBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 12px rgba(37, 99, 235, 0.25)';
            this.style.background = 'linear-gradient(135deg, var(--primary-color) 0%, #4338ca 100%)';
        });
        
        whatsappButtonContainer.appendChild(whatsappBtn);
        chatbotMessages.appendChild(whatsappButtonContainer);
        scrollToBottom();
    }
    
    // Initialize with welcome message
    function initChat() {
        if (isFirstMessage) {
            const lang = getCurrentLang();
            const welcomeMessages = {
                az: 'Salam, sizə necə kömək edə bilərik?',
                en: 'Hello, how can we help you?',
                ru: 'Здравствуйте, чем мы можем вам помочь?'
            };
            const welcomeText = welcomeMessages[lang] || welcomeMessages.az;
            addMessage(welcomeText, false);
            chatbotBadge.classList.add('active');
            if (chatbotPreviewBubble && chatbotPreviewText) {
                chatbotPreviewText.textContent = welcomeText;
                chatbotPreviewBubble.classList.add('visible');
                chatbotPreviewBubble.setAttribute('aria-hidden', 'false');
            }
            isFirstMessage = false;
        }
    }
    
    // Update WhatsApp link
    function updateWhatsAppLink() {
        const lang = getCurrentLang();
        const messages = {
            az: 'Salam, Köməyə ehtiyacım var',
            en: 'Hello, I need help',
            ru: 'Здравствуйте, мне нужна помощь'
        };
        
        const message = messages[lang] || messages.az;
        const whatsappNumber = '994507736216';
        const encodedMessage = encodeURIComponent(message);
        const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
        
        // Update WhatsApp button in chat messages if exists
        const chatWhatsAppBtn = document.querySelector('#chatbotWhatsAppButton a');
        if (chatWhatsAppBtn) {
            chatWhatsAppBtn.onclick = function(e) {
                e.preventDefault();
                window.open(whatsappUrl, '_blank');
            };
        }
    }
    
    // Event listeners
    chatbotButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        chatbotMessage.classList.add('active');
        initChat();
        if (chatbotPreviewBubble) {
            chatbotPreviewBubble.classList.remove('visible');
            chatbotPreviewBubble.setAttribute('aria-hidden', 'true');
        }
        setTimeout(() => chatbotInput.focus(), 300);
    });
    
    chatbotClose.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        chatbotMessage.classList.remove('active');
    });
    
    chatbotSend.addEventListener('click', function(e) {
        e.preventDefault();
        sendMessage();
        updateWhatsAppLink();
    });
    
    chatbotInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
            updateWhatsAppLink();
        }
    });
    
    chatbotInput.addEventListener('input', function() {
        chatbotSend.disabled = !this.value.trim();
    });
    
    // Listen for language changes
    window.addEventListener('languageChanged', function() {
        // Content will be updated by updateContent() function
        setTimeout(() => {
            if (!isFirstMessage) {
                chatbotMessages.innerHTML = '';
                messageHistory = [];
                isFirstMessage = true;
                initChat();
            }
        }, 100);
    });
    
    // Close when clicking outside
    document.addEventListener('click', function(e) {
        if (!chatbotMessage.contains(e.target) && !chatbotButton.contains(e.target)) {
            chatbotMessage.classList.remove('active');
        }
    });
    
    // Prevent closing when clicking inside the message
    chatbotMessage.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Sayt açılandan 3 saniyə sonra chatbotdan salam mesajı göndər
    setTimeout(initChat, 3000);
}

// Mobile Footer Accordion
function initMobileFooterAccordion() {
    // Only apply on mobile devices
    if (window.innerWidth > 768) return;
    
    const footerColumns = document.querySelectorAll('.footer-column:not(.footer-about)');
    
    footerColumns.forEach(column => {
        const title = column.querySelector('.footer-title');
        if (!title) return;
        
        title.addEventListener('click', function() {
            // Close other columns
            footerColumns.forEach(col => {
                if (col !== column) {
                    col.classList.remove('active');
                }
            });
            
            // Toggle current column
            column.classList.toggle('active');
        });
    });
}

// Initialize on load and resize
function handleFooterAccordion() {
    initMobileFooterAccordion();
    
    // Reset accordion on desktop
    if (window.innerWidth > 768) {
        document.querySelectorAll('.footer-column').forEach(col => {
            col.classList.remove('active');
        });
    }
}

// Call init function when DOM is fully loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initAllAnimations();
        initWhatsAppChatbot();
        handleFooterAccordion();
    });
} else {
    initAllAnimations();
    initWhatsAppChatbot();
    handleFooterAccordion();
}

// Handle resize for accordion
window.addEventListener('resize', handleFooterAccordion);
