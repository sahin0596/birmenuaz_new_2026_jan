// Admin Panel JavaScript - Restaurant Management

let editingRestaurantId = null;

// Load restaurants
function loadRestaurants() {
    const restaurantsList = document.getElementById('restaurantsList');
    if (!restaurantsList) return;
    
    // Show loading state
    restaurantsList.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px;">
            <div style="display: inline-block; width: 50px; height: 50px; border: 3px solid rgba(102, 126, 234, 0.2); border-top-color: #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 20px; color: rgba(255, 255, 255, 0.7);">Yüklənir...</p>
        </div>
        <style>
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        </style>
    `;
    
    fetch('../api/api.php?action=get_restaurants', { cache: 'no-store' })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Restaurants data received:', data); // Debug log
            if (data.success && data.restaurants && data.restaurants.length > 0) {
                restaurantsList.innerHTML = data.restaurants.map((restaurant, index) => {
                    // Fix image paths for admin panel (add ../ prefix if needed)
                    const coverPath = restaurant.cover_path ? (restaurant.cover_path.startsWith('../') ? restaurant.cover_path : '../' + restaurant.cover_path) : '';
                    const logoPath = restaurant.logo_path ? (restaurant.logo_path.startsWith('../') ? restaurant.logo_path : '../' + restaurant.logo_path) : '';
                    
                    return `
                    <div class="restaurant-card admin-card" style="animation-delay: ${index * 0.1}s;">
                        <div class="restaurant-card-cover admin-card-cover">
                            ${coverPath ? `<img src="${coverPath}" alt="${restaurant.name}">` : ''}
                        </div>
                        <div class="restaurant-card-body admin-card-body">
                            ${logoPath ? 
                                `<img src="${logoPath}" alt="${restaurant.name}" class="restaurant-logo">` : 
                                `<div class="restaurant-logo" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 26px;">${restaurant.name.charAt(0).toUpperCase()}</div>`
                            }
                            <div class="restaurant-name">${escapeHtml(restaurant.name)}</div>
                            <div class="restaurant-meta">
                                <div class="meta-item">
                                    <i class="bi bi-calendar3"></i>
                                    <span>${formatDate(restaurant.created_at)}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-basket"></i>
                                    <span>${restaurant.product_count || 0} məhsul</span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-eye"></i>
                                    <span>${restaurant.view_count || 0} baxış</span>
                                </div>
                            </div>
                            <div class="restaurant-info">
                                ${restaurant.address ? `<div><i class="bi bi-geo-alt-fill"></i> <span>${escapeHtml(restaurant.address)}</span></div>` : ''}
                                ${restaurant.phone ? `<div><i class="bi bi-telephone-fill"></i> <span>${escapeHtml(restaurant.phone)}</span></div>` : ''}
                                ${restaurant.wifi_name ? `<div><i class="bi bi-wifi"></i> <span>WiFi: ${escapeHtml(restaurant.wifi_name)}</span></div>` : ''}
                                <div><i class="bi bi-link-45deg"></i> <span>/${escapeHtml(restaurant.slug)}/</span></div>
                            </div>
                            <div class="restaurant-actions">
                                <button class="btn-view admin-btn admin-btn-icon" onclick="window.open('../${restaurant.slug}/', '_blank')" title="Restoran səhifəsini aç">
                                    <i class="bi bi-eye-fill"></i> <span>Bax</span>
                                </button>
                                <button class="btn-menu admin-btn admin-btn-primary admin-btn-icon" onclick="window.location.href='menu_manager.php?restaurant_id=${restaurant.id}'">
                                    <i class="bi bi-list-ul"></i> <span>Menyu</span>
                                </button>
                                <button class="btn-qr admin-btn admin-btn-icon" onclick="showQrCode('${restaurant.slug}', '${escapeHtml(restaurant.name)}')">
                                    <i class="bi bi-qr-code"></i> <span>QR</span>
                                </button>
                                <button class="btn-export admin-btn admin-btn-success admin-btn-icon" onclick="exportRestaurant(${restaurant.id}, '${escapeHtml(restaurant.name)}')" title="İxrac et">
                                    <i class="bi bi-download"></i> <span>İxrac</span>
                                </button>
                                <button class="btn-edit admin-btn admin-btn-icon" onclick="editRestaurant(${restaurant.id})">
                                    <i class="bi bi-pencil-fill"></i> <span>Redaktə</span>
                                </button>
                                <button class="btn-delete-rest admin-btn admin-btn-danger admin-btn-icon" onclick="deleteRestaurant(${restaurant.id}, event)">
                                    <i class="bi bi-trash-fill"></i> <span>Sil</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                }).join('');
            } else {
                restaurantsList.innerHTML = `
                    <div class="no-restaurants admin-empty">
                        <i class="bi bi-shop admin-empty-icon"></i>
                        <h3 class="admin-empty-title">Restoran yoxdur</h3>
                        <p class="admin-empty-text">Yeni restoran əlavə etmək üçün "Yeni Restoran" düyməsini klikləyin</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading restaurants:', error);
            restaurantsList.innerHTML = `
                <div class="no-restaurants">
                    <i class="bi bi-exclamation-triangle"></i>
                    <h3>Xəta baş verdi</h3>
                    <p>Restoranlar yüklənə bilmədi: ${error.message}</p>
                    <p style="font-size: 12px; color: #9ca3af; margin-top: 10px;">Xahiş edirik, brauzerin konsolunu yoxlayın və yenidən cəhd edin.</p>
                </div>
            `;
        });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
}

// Open add modal
function openAddModal() {
    editingRestaurantId = null;
    const modalTitle = document.getElementById('modalTitle');
    const restaurantForm = document.getElementById('restaurantForm');
    const restaurantId = document.getElementById('restaurantId');
    const logoPreview = document.getElementById('logoPreview');
    const coverPreview = document.getElementById('coverPreview');
    const restaurantModal = document.getElementById('restaurantModal');
    
    if (modalTitle) modalTitle.textContent = 'Yeni Restoran';
    if (restaurantForm) restaurantForm.reset();
    if (restaurantId) restaurantId.value = '';
    if (logoPreview) logoPreview.innerHTML = '';
    if (coverPreview) coverPreview.innerHTML = '';
    if (restaurantModal) restaurantModal.classList.add('active');
}

// Edit restaurant
function editRestaurant(id) {
    editingRestaurantId = id;
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) modalTitle.textContent = 'Restoranı Redaktə Et';
    
    fetch(`../api/api.php?action=get_restaurant&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const restaurant = data.restaurant;
                
                // Set values with null checks
                const setValueIfExists = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) element.value = value || '';
                };
                const setCheckedIfExists = (id, checked) => {
                    const element = document.getElementById(id);
                    if (element) element.checked = checked;
                };
                
                setValueIfExists('restaurantId', restaurant.id);
                setValueIfExists('restaurantName', restaurant.name);
                setValueIfExists('restaurantSlug', restaurant.slug);
                setValueIfExists('restaurantDescription', restaurant.description);
                setValueIfExists('restaurantAddress', restaurant.address);
                setValueIfExists('restaurantPhone', restaurant.phone);
                setValueIfExists('restaurantPhone2', restaurant.phone2);
                setValueIfExists('restaurantPhone3', restaurant.phone3);
                setValueIfExists('restaurantPhone4', restaurant.phone4);
                setValueIfExists('restaurantWifiName', restaurant.wifi_name);
                setValueIfExists('restaurantWifi', restaurant.wifi_password);
                setValueIfExists('restaurantLoginUsername', restaurant.login_username);
                setValueIfExists('restaurantLoginPassword', ''); // Don't show password, leave empty
                setValueIfExists('restaurantInstagram', restaurant.instagram_url);
                setValueIfExists('restaurantFacebook', restaurant.facebook_url);
                setValueIfExists('restaurantWhatsApp', restaurant.whatsapp_url);
                setValueIfExists('restaurantTikTok', restaurant.tiktok_url);
                setCheckedIfExists('restaurantActive', restaurant.is_active == 1);
                
                // Show existing images
                const logoPreview = document.getElementById('logoPreview');
                const coverPreview = document.getElementById('coverPreview');
                const restaurantModal = document.getElementById('restaurantModal');
                
                if (restaurant.logo_path && logoPreview) {
                    const logoSrc = restaurant.logo_path.startsWith('../') ? restaurant.logo_path : '../' + restaurant.logo_path;
                    logoPreview.innerHTML = `<img src="${logoSrc}" alt="Logo">`;
                }
                if (restaurant.cover_path && coverPreview) {
                    const coverSrc = restaurant.cover_path.startsWith('../') ? restaurant.cover_path : '../' + restaurant.cover_path;
                    coverPreview.innerHTML = `<img src="${coverSrc}" alt="Cover">`;
                }
                
                if (restaurantModal) restaurantModal.classList.add('active');
            } else {
                alert('Xəta: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Xəta baş verdi');
        });
}

// Open modal by id (umumi modallar üçün)
function openModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.classList.add('active');
}

// Close modal – arqument yoxdursa restoran modalını bağla
function closeModal(modalId) {
    if (modalId) {
        const el = document.getElementById(modalId);
        if (el) el.classList.remove('active');
        return;
    }
    const restaurantModal = document.getElementById('restaurantModal');
    if (restaurantModal) {
        restaurantModal.classList.remove('active');
    }
    editingRestaurantId = null;
}

// ——— Restorana məhsul əlavə et (seçim + əl ilə / bazadan) ———
let productTemplatesCache = [];
let selectedTemplateId = null;

function openAddProductChoiceModal() {
    openModal('productAddChoiceModal');
}

function addProductManualGeneral() {
    closeModal('productAddChoiceModal');
    const form = document.getElementById('productManualForm');
    const restSel = document.getElementById('productManualRestaurant');
    const catSel = document.getElementById('productManualCategory');
    if (!form || !restSel) return;
    form.reset();
    catSel.innerHTML = '<option value="">Əvvəlcə restoran seçin...</option>';
    restSel.innerHTML = '<option value="">Restoran seçin...</option>';
    fetch('../api/api.php?action=get_restaurants')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.restaurants && data.restaurants.length > 0) {
                data.restaurants.forEach(r => {
                    const o = document.createElement('option');
                    o.value = r.id;
                    o.textContent = r.name;
                    restSel.appendChild(o);
                });
            }
            restSel.onchange = function() {
                const rid = this.value;
                if (!rid) {
                    catSel.innerHTML = '<option value="">Əvvəlcə restoran seçin...</option>';
                    return;
                }
                catSel.innerHTML = '<option value="">Yüklənir...</option>';
                fetch('../api/api.php?action=get_categories&restaurant_id=' + encodeURIComponent(rid))
                    .then(rr => rr.json())
                    .then(catData => {
                        catSel.innerHTML = '<option value="">Kateqoriya seçin...</option>';
                        if (catData.success && catData.categories) {
                            catData.categories.forEach(c => {
                                const opt = document.createElement('option');
                                opt.value = c.id;
                                opt.textContent = c.name;
                                catSel.appendChild(opt);
                            });
                        }
                    });
            };
        });
    openModal('productManualModal');
}

function saveProductGeneral(event) {
    event.preventDefault();
    const form = document.getElementById('productManualForm');
    if (!form) return;
    const formData = new FormData(form);
    formData.append('action', 'add');
    fetch('../api/api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('productManualModal');
                showNotification('Məhsul əlavə edildi.', 'success');
                loadRestaurants();
            } else {
                showNotification('Xəta: ' + (data.message || ''), 'error');
            }
        })
        .catch(() => showNotification('Şəbəkə xətası', 'error'));
}

function openProductFromLibraryFromChoice() {
    closeModal('productAddChoiceModal');
    openProductFromLibrary();
}

function openProductFromLibrary() {
    const modal = document.getElementById('productLibraryModal');
    const addBar = document.getElementById('productLibraryAddBar');
    const listTbody = document.getElementById('productLibraryList');
    const restSel = document.getElementById('productLibraryRestaurant');
    if (!modal || !listTbody || !restSel) return;

    addBar.style.display = 'none';
    const searchInput = document.getElementById('productLibrarySearch');
    if (searchInput) searchInput.value = '';
    selectedTemplateId = null;
    listTbody.innerHTML = '<tr><td colspan="5" class="text-center">Yüklənir...</td></tr>';
    restSel.innerHTML = '<option value="">Restoran seçin...</option>';

    openModal('productLibraryModal');

    Promise.all([
        fetch('../api/api.php?action=get_product_templates').then(r => r.json()),
        fetch('../api/api.php?action=get_restaurants').then(r => r.json())
    ]).then(([tplData, restData]) => {
        if (restData.success && restData.restaurants && restData.restaurants.length > 0) {
            restData.restaurants.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.textContent = r.name;
                restSel.appendChild(opt);
            });
        }
        restSel.onchange = function() {};

        if (!tplData.success || !tplData.templates || tplData.templates.length === 0) {
            listTbody.innerHTML = '<tr><td colspan="5" class="text-center">Bazada hələ məhsul yoxdur. Restoran idxal edin və ya məhsul əlavə edin (avtomatik bazaya əlavə olunacaq).</td></tr>';
            productTemplatesCache = [];
            return;
        }
        productTemplatesCache = tplData.templates;
        const baseUrl = '../';
        listTbody.innerHTML = tplData.templates.map((t, idx) => {
            const imgSrc = t.image_path ? (t.image_path.startsWith('http') || t.image_path.startsWith('/') ? t.image_path : baseUrl + t.image_path) : '';
            const searchText = ((t.name || '') + ' ' + (t.category_name || '')).toLowerCase().replace(/"/g, '&quot;');
            return `<tr data-search="${searchText}">
                <td style="width:70px;">${imgSrc ? `<img src="${imgSrc}" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:6px;">` : '<div style="width:56px;height:56px;background:#eee;border-radius:6px;"></div>'}</td>
                <td><strong>${escapeHtml(t.name || '-')}</strong></td>
                <td>${escapeHtml(t.category_name || '-')}</td>
                <td>${parseFloat(t.price || 0).toFixed(2)} ₼</td>
                <td><button type="button" class="admin-btn admin-btn-primary" style="padding:6px 12px;" onclick="selectTemplate(${t.id}, ${idx})"><i class="bi bi-plus-circle"></i> Seç</button></td>
            </tr>`;
        }).join('');
    }).catch(() => {
        listTbody.innerHTML = '<tr><td colspan="5" class="text-center">Yükləmə xətası</td></tr>';
        productTemplatesCache = [];
    });
}

function filterProductLibraryList(query) {
    const q = (query || '').trim().toLowerCase();
    const rows = document.querySelectorAll('#productLibraryList tr[data-search]');
    if (rows) rows.forEach(tr => {
        tr.style.display = !q || tr.getAttribute('data-search').indexOf(q) >= 0 ? '' : 'none';
    });
}

function selectTemplate(id, idx) {
    selectedTemplateId = id;
    const tpl = productTemplatesCache[idx];
    const name = (tpl && tpl.name) ? tpl.name : '(seçildi)';
    const categoryName = (tpl && tpl.category_name) ? tpl.category_name : '';
    const el = document.getElementById('productLibrarySelectedName');
    const hintEl = document.getElementById('productLibraryCategoryHint');
    const addBar = document.getElementById('productLibraryAddBar');
    if (el) el.textContent = name;
    if (hintEl) hintEl.textContent = categoryName ? `(${categoryName})` : '';
    if (addBar) addBar.style.display = 'block';
}

function addProductFromTemplateSubmit() {
    const restSel = document.getElementById('productLibraryRestaurant');
    const restaurantId = restSel ? restSel.value : '';
    if (!selectedTemplateId || !restaurantId) {
        alert('Restoran seçin.');
        return;
    }
    const formData = new FormData();
    formData.append('action', 'add_product_from_template');
    formData.append('template_id', selectedTemplateId);
    formData.append('restaurant_id', restaurantId);
    fetch('../api/api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal('productLibraryModal');
                showNotification('Məhsul restorana əlavə edildi.', 'success');
                loadRestaurants();
            } else {
                showNotification('Xəta: ' + (data.message || ''), 'error');
            }
        })
        .catch(() => showNotification('Şəbəkə xətası', 'error'));
}

// Delete restaurant
function deleteRestaurant(id, event) {
    // Create custom confirm dialog
    const confirmDelete = confirm('⚠️ DİQQƏT!\n\nBu restoranı silmək istədiyinizə əminsiniz?\n\n• Restoran məlumatları\n• Logo və kapak şəkilləri\n• Bütün məhsullar\n\nBU ƏMƏLİYYAT GERİ QAYTARILA BİLMƏZ!');
    
    if (confirmDelete) {
        // Show loading
        let btn = null;
        let originalContent = '';
        
        if (event && event.target) {
            btn = event.target.closest('.btn-delete-rest');
            if (btn) {
                originalContent = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Silinir...</span>';
                btn.disabled = true;
            }
        }
        
        fetch(`../api/api.php?action=delete_restaurant&id=${id}`, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success notification
                showNotification('Restoran uğurla silindi', 'success');
                loadStatistics();
                loadRestaurants();
            } else {
                showNotification('Xəta: ' + data.message, 'error');
                if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Xəta baş verdi', 'error');
            if (btn) {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        });
    }
}

// Show credentials after restoran yaradıldı (admin panel girişi)
function showCredentialsNotification(credentials) {
    showNotification('Restoran əlavə edildi. Admin giriş məlumatları aşağıdadır.', 'success');
    
    const user = credentials.login_username;
    const pass = credentials.login_password;
    
    const overlay = document.createElement('div');
    overlay.style.cssText = `
        position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 10001;
        display: flex; align-items: center; justify-content: center; padding: 20px;
    `;
    const box = document.createElement('div');
    box.style.cssText = 'background: white; border-radius: 16px; padding: 28px; max-width: 420px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);';
    box.innerHTML = `
        <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; color: #0f172a;">
            <i class="bi bi-key-fill" style="color: #10b981; margin-right: 8px;"></i>
            Restoran Admin Girişi
        </h3>
        <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">Bu məlumatları saxlayın – restoran admin panelinə giriş üçün lazımdır.</p>
        <div style="background: #f8fafc; border-radius: 10px; padding: 16px; margin-bottom: 16px;">
            <div style="margin-bottom: 12px;">
                <label style="font-size: 11px; color: #64748b; display: block; margin-bottom: 4px;">İstifadəçi adı</label>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <code id="credUsername" style="flex:1; padding: 10px 12px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">${escapeHtml(user)}</code>
                    <button type="button" class="cred-copy-btn" data-copy="${user.replace(/"/g, '&quot;')}" style="padding: 8px 14px; background: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 12px;">Kopyala</button>
                </div>
            </div>
            <div>
                <label style="font-size: 11px; color: #64748b; display: block; margin-bottom: 4px;">Şifrə</label>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <code id="credPassword" style="flex:1; padding: 10px 12px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">${escapeHtml(pass)}</code>
                    <button type="button" class="cred-copy-btn" data-copy="${pass.replace(/"/g, '&quot;')}" style="padding: 8px 14px; background: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 12px;">Kopyala</button>
                </div>
            </div>
        </div>
        <button type="button" class="cred-close-btn" style="width: 100%; padding: 12px; background: #10b981; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px;">Bağla</button>
    `;
    overlay.appendChild(box);
    
    box.querySelectorAll('.cred-copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-copy');
            navigator.clipboard.writeText(text).then(() => {
                const orig = this.textContent;
                this.textContent = 'Kopyalandı!';
                setTimeout(() => { this.textContent = orig; }, 1500);
            });
        });
    });
    box.querySelector('.cred-close-btn').addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    
    document.body.appendChild(overlay);
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 30px;
        right: 30px;
        padding: 18px 24px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' : 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'};
        color: white;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        z-index: 10000;
        font-weight: 600;
        font-size: 15px;
        animation: slideInRight 0.4s ease, fadeOut 0.4s ease 2.6s;
        display: flex;
        align-items: center;
        gap: 12px;
    `;
    
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Add animation styles
if (!document.getElementById('notificationStyles')) {
    const style = document.createElement('style');
    style.id = 'notificationStyles';
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
    `;
    document.head.appendChild(style);
}

// Image preview handlers
const restaurantLogoInput = document.getElementById('restaurantLogo');
if (restaurantLogoInput) {
    restaurantLogoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('logoPreview');
        
        if (file && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview">`;
            };
            reader.readAsDataURL(file);
        } else if (preview) {
            preview.innerHTML = '';
        }
    });
}

const restaurantCoverInput = document.getElementById('restaurantCover');
if (restaurantCoverInput) {
    restaurantCoverInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('coverPreview');
        
        if (file && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Cover Preview">`;
            };
            reader.readAsDataURL(file);
        } else if (preview) {
            preview.innerHTML = '';
        }
    });
}

// Auto-generate slug from name
const restaurantNameInput = document.getElementById('restaurantName');
if (restaurantNameInput) {
    restaurantNameInput.addEventListener('input', function(e) {
        if (!editingRestaurantId) {
            const slug = e.target.value
                .toLowerCase()
                .replace(/ə/g, 'e')
                .replace(/ı/g, 'i')
                .replace(/ö/g, 'o')
                .replace(/ü/g, 'u')
                .replace(/ğ/g, 'g')
                .replace(/ş/g, 's')
                .replace(/ç/g, 'c')
                .replace(/[^a-z0-9]/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            const restaurantSlugInput = document.getElementById('restaurantSlug');
            if (restaurantSlugInput) restaurantSlugInput.value = slug;
        }
    });
}

// Form submit
const restaurantForm = document.getElementById('restaurantForm');
if (restaurantForm) {
    restaurantForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const action = editingRestaurantId ? 'update_restaurant' : 'add_restaurant';
        formData.append('action', action);
        
        const restaurantActiveCheckbox = document.getElementById('restaurantActive');
        if (restaurantActiveCheckbox && restaurantActiveCheckbox.checked) {
            formData.set('is_active', '1');
        } else {
            formData.set('is_active', '0');
        }
        
        // Show loading on submit button
        const submitBtn = this.querySelector('.btn-submit');
        if (submitBtn) {
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Yadda saxlanılır...';
            submitBtn.disabled = true;
            
            fetch('../api/api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (!editingRestaurantId && data.generated_credentials) {
                        showCredentialsNotification(data.generated_credentials);
                    } else {
                        showNotification(
                            editingRestaurantId ? 'Restoran uğurla yeniləndi' : 'Restoran uğurla əlavə edildi', 
                            'success'
                        );
                    }
                    closeModal();
                    loadStatistics();
                    loadRestaurants();
                } else {
                    showNotification('Xəta: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Xəta baş verdi', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }
    });
}

// Close modal on overlay click
const restaurantModal = document.getElementById('restaurantModal');
if (restaurantModal) {
    restaurantModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
}

// QR Code Functions
let currentQrSlug = '';
let qrSettings = { logo_option: 'default', logo_path: '', corner_radius: 'sharp', qr_color: '000000', qr_bgcolor: 'ffffff' };
let qrSettingsForm = { style: 'default', logo_option: 'default', corner_radius: 'sharp', qr_color: '000000', qr_bgcolor: 'ffffff' };

function loadQrSettings() {
    return fetch('api_qr_settings.php?action=get')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.settings) {
                qrSettings = data.settings;
            }
        })
        .catch(() => {});
}

function openQrSettingsModal() {
    const modal = document.getElementById('qrSettingsModal');
    if (!modal) return;
    loadQrSettings().then(function() {
        var lo = qrSettings.logo_option;
        if (!lo && qrSettings.logo_path) {
            if (qrSettings.logo_path.indexOf('logo_dark') >= 0) lo = 'logo_dark';
            else if ((qrSettings.logo_path || '').indexOf('logo.png') >= 0) lo = 'logo.png';
            else lo = 'custom';
        }
        qrSettingsForm = {
            logo_option: lo || 'default',
            corner_radius: qrSettings.corner_radius || 'sharp',
            qr_color: qrSettings.qr_color || '000000',
            qr_bgcolor: qrSettings.qr_bgcolor || 'ffffff'
        };
        document.querySelectorAll('.qr-logo-opt').forEach(el => {
            el.classList.toggle('selected', el.dataset.logo === qrSettingsForm.logo_option);
        });
        document.querySelectorAll('.qr-corner-opt').forEach(el => {
            el.classList.toggle('selected', el.dataset.corner === qrSettingsForm.corner_radius);
        });
        var fgHex = (qrSettingsForm.qr_color || '000000').replace(/^#?/, '');
        var bgHex = (qrSettingsForm.qr_bgcolor || 'ffffff').replace(/^#?/, '');
        var fgPicker = document.getElementById('qrColorPicker');
        var bgPicker = document.getElementById('qrBgColorPicker');
        if (fgPicker) { fgPicker.value = '#' + fgHex; }
        if (document.getElementById('qrColorHex')) document.getElementById('qrColorHex').value = '#' + fgHex;
        if (bgPicker) { bgPicker.value = '#' + bgHex; }
        if (document.getElementById('qrBgColorHex')) document.getElementById('qrBgColorHex').value = '#' + bgHex;
        document.getElementById('qrCustomUploadWrap').style.display = (qrSettingsForm.logo_option === 'custom') ? 'block' : 'none';
        modal.classList.add('active');
    });
}

function closeQrSettingsModal() {
    const modal = document.getElementById('qrSettingsModal');
    if (modal) modal.classList.remove('active');
}

function updateQrCustomColor() {
    var fg = document.getElementById('qrColorPicker').value;
    var bg = document.getElementById('qrBgColorPicker').value;
    document.getElementById('qrColorHex').value = fg;
    document.getElementById('qrBgColorHex').value = bg;
    qrSettingsForm.qr_color = fg.replace('#', '');
    qrSettingsForm.qr_bgcolor = bg.replace('#', '');
}

function updateQrColorFromHex() {
    var fg = document.getElementById('qrColorHex').value;
    var bg = document.getElementById('qrBgColorHex').value;
    if (/^#[0-9a-fA-F]{6}$/.test(fg)) {
        document.getElementById('qrColorPicker').value = fg;
        qrSettingsForm.qr_color = fg.replace('#', '');
    }
    if (/^#[0-9a-fA-F]{6}$/.test(bg)) {
        document.getElementById('qrBgColorPicker').value = bg;
        qrSettingsForm.qr_bgcolor = bg.replace('#', '');
    }
}

function selectQrLogo(opt) {
    qrSettingsForm.logo_option = opt;
    document.querySelectorAll('.qr-logo-opt').forEach(el => el.classList.toggle('selected', el.dataset.logo === opt));
    const wrap = document.getElementById('qrCustomUploadWrap');
    if (wrap) wrap.style.display = (opt === 'custom') ? 'block' : 'none';
}

function selectQrCorner(corner) {
    qrSettingsForm.corner_radius = corner;
    document.querySelectorAll('.qr-corner-opt').forEach(el => el.classList.toggle('selected', el.dataset.corner === corner));
}

function handleQrLogoUpload(input) {
    if (input.files && input.files[0]) {
        qrSettingsForm.logo_option = 'custom';
        document.querySelectorAll('.qr-logo-opt').forEach(el => el.classList.toggle('selected', el.dataset.logo === 'custom'));
        saveQrSettings(input.files[0]);
    }
}

function saveQrSettings(fileToUpload) {
    const formData = new FormData();
    formData.append('action', 'save');
    formData.append('logo_option', qrSettingsForm.logo_option);
    formData.append('corner_radius', qrSettingsForm.corner_radius || 'sharp');
    formData.append('qr_color', (qrSettingsForm.qr_color || '000000').replace('#', ''));
    formData.append('qr_bgcolor', (qrSettingsForm.qr_bgcolor || 'ffffff').replace('#', ''));
    if (fileToUpload) formData.append('logo', fileToUpload);
    fetch('api_qr_settings.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                qrSettings = data.settings;
                closeQrSettingsModal();
            }
        });
}

// User clicks "Saxla" without file - save style and logo_option only
document.addEventListener('DOMContentLoaded', function() {
    loadQrSettings();
});
document.addEventListener('click', function(e) {
    const m = document.getElementById('qrSettingsModal');
    if (m && e.target === m) closeQrSettingsModal();
});

function showQrCode(slug, restaurantName) {
    currentQrSlug = slug;
    
    // Get the base URL - project root (restaurants at /slug/, not /admin/slug/)
    const pathname = window.location.pathname;
    const basePath = pathname.replace(/\/admin\/[^/]*$/, '');
    const baseUrl = window.location.origin + (basePath || '/');
    const restaurantUrl = `${baseUrl.replace(/\/$/, '')}/${slug}/`;
    
    // Update URL display
    const qrUrlEl = document.getElementById('qrUrl');
    if (qrUrlEl) qrUrlEl.textContent = restaurantUrl;
    
    // Generate QR code with logo
    generateQrWithLogo(restaurantUrl);
    
    // Show modal
    const qrModal = document.getElementById('qrModal');
    if (qrModal) qrModal.classList.add('active');
}

var qrCornerRadiusMap = { sharp: 0, small: 36, medium: 72, large: 108 };

function roundRectPath(ctx, x, y, w, h, r) {
    if (r <= 0) { ctx.rect(x, y, w, h); return; }
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
}

function generateQrWithLogo(url) {
    const canvas = document.getElementById('qrCodeCanvas');
    if (!canvas) return;

    const DISPLAY_SIZE = 300;
    const EXPORT_SIZE = 900;
    var sc = {
        color: (qrSettings.qr_color || '000000').replace('#', ''),
        bgcolor: (qrSettings.qr_bgcolor || 'ffffff').replace('#', '')
    };
    const cornerRadius = qrCornerRadiusMap[qrSettings.corner_radius] || 0;

    canvas.width = EXPORT_SIZE;
    canvas.height = EXPORT_SIZE;
    canvas.style.width = DISPLAY_SIZE + 'px';
    canvas.style.height = DISPLAY_SIZE + 'px';

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${EXPORT_SIZE}x${EXPORT_SIZE}&data=${encodeURIComponent(url)}&margin=10&ecc=H&color=${sc.color}&bgcolor=${sc.bgcolor}`;

    const qrImage = new Image();
    qrImage.crossOrigin = "Anonymous";
    qrImage.onload = function() {
        ctx.save();
        if (cornerRadius > 0) {
            ctx.beginPath();
            roundRectPath(ctx, 0, 0, EXPORT_SIZE, EXPORT_SIZE, cornerRadius);
            ctx.closePath();
            ctx.clip();
        }
        ctx.drawImage(qrImage, 0, 0, EXPORT_SIZE, EXPORT_SIZE);

        const centerX = EXPORT_SIZE / 2;
        const centerY = EXPORT_SIZE / 2;
        const logoOpt = qrSettings.logo_option || 'default';
        const logoPath = qrSettings.logo_path || '';

        if (logoOpt === 'default' || (!logoPath && logoOpt !== 'custom')) {
            var bgWidth = 360;
            var bgHeight = 108;
            var radius = 18;
            ctx.fillStyle = '#ffffff';
            var rectX = centerX - bgWidth / 2;
            var rectY = centerY - bgHeight / 2;
            ctx.beginPath();
            ctx.moveTo(rectX + radius, rectY);
            ctx.lineTo(rectX + bgWidth - radius, rectY);
            ctx.quadraticCurveTo(rectX + bgWidth, rectY, rectX + bgWidth, rectY + radius);
            ctx.lineTo(rectX + bgWidth, rectY + bgHeight - radius);
            ctx.quadraticCurveTo(rectX + bgWidth, rectY + bgHeight, rectX + bgWidth - radius, rectY + bgHeight);
            ctx.lineTo(rectX + radius, rectY + bgHeight);
            ctx.quadraticCurveTo(rectX, rectY + bgHeight, rectX, rectY + bgHeight - radius);
            ctx.lineTo(rectX, rectY + radius);
            ctx.quadraticCurveTo(rectX, rectY, rectX + radius, rectY);
            ctx.closePath();
            ctx.fill();
            ctx.font = 'bold 78px "Francois One", sans-serif';
            ctx.fillStyle = '#000000';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('BIRMENU', centerX, centerY);
            ctx.restore();
        } else {
            var logoUrl = (logoPath.indexOf('../') === 0 || logoPath.indexOf('/') === 0) ? logoPath : '../' + logoPath;
            var logoImg = new Image();
            logoImg.crossOrigin = "Anonymous";
            logoImg.onload = function() {
                var sz = 200;
                var lx = centerX - sz / 2;
                var ly = centerY - sz / 2;
                var padding = 16;
                var bgSize = sz + padding;
                var bgX = centerX - bgSize / 2;
                var bgY = centerY - bgSize / 2;
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(bgX, bgY, bgSize, bgSize);
                ctx.drawImage(logoImg, lx + 12, ly + 12, sz - 24, sz - 24);
                ctx.restore();
            };
            logoImg.onerror = function() {
                ctx.font = 'bold 78px "Francois One", sans-serif';
                ctx.fillStyle = '#000000';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('BIRMENU', centerX, centerY);
                ctx.restore();
            };
            logoImg.src = logoUrl;
        }
    };
    qrImage.src = qrCodeUrl;
}

function downloadQrCode() {
    const canvas = document.getElementById('qrCodeCanvas');
    if (!canvas) return;
    
    const link = document.createElement('a');
    link.download = `qr-${currentQrSlug}.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function closeQrModal() {
    const qrModal = document.getElementById('qrModal');
    if (qrModal) qrModal.classList.remove('active');
}

// Close QR modal on overlay click
document.addEventListener('click', function(e) {
    const qrModal = document.getElementById('qrModal');
    if (e.target === qrModal) {
        closeQrModal();
    }
});

// Load Statistics
function loadStatistics() {
    fetch('../api/api.php?action=get_statistics')
        .then(response => response.json())
        .then(data => {
            console.log('Statistics data:', data); // Debug
            if (data.success) {
                const stats = data.statistics;
                const totalRestaurants = document.getElementById('totalRestaurants');
                const activeRestaurants = document.getElementById('activeRestaurants');
                const inactiveRestaurants = document.getElementById('inactiveRestaurants');
                
                if (totalRestaurants) totalRestaurants.textContent = stats.total;
                if (activeRestaurants) activeRestaurants.textContent = stats.active;
                if (inactiveRestaurants) inactiveRestaurants.textContent = stats.inactive;
            } else {
                console.error('Statistics failed:', data.message);
            }
        })
        .catch(error => {
            console.error('Statistics error:', error);
        });
}

// Export Restaurant (ZIP: data + images)
function exportRestaurant(restaurantId, restaurantName) {
    showNotification('İxrac edilir (ZIP, şəkillər daxil)...', 'info');
    
    const link = document.createElement('a');
    link.href = `../api/api.php?action=export_restaurant&restaurant_id=${restaurantId}`;
    link.download = `restaurant_${restaurantName.replace(/[^a-z0-9]/gi, '_')}_${new Date().toISOString().split('T')[0]}.zip`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        showNotification('Restoran uğurla ixrac edildi (ZIP)', 'success');
    }, 500);
}

// Import Restaurant with Progress Indicator
function importRestaurant() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.zip,.json';
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Check file size (warn if > 10MB)
        const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
        if (fileSizeMB > 10) {
            const proceed = confirm(`Fayl böyükdür (${fileSizeMB}MB). İdxal 5-10 dəqiqə çəkə bilər. Davam edək?`);
            if (!proceed) return;
        }
        
        const formData = new FormData();
        formData.append('action', 'import_restaurant');
        formData.append('import_file', file);
        
        // Create progress overlay with animation
        const overlay = document.createElement('div');
        overlay.id = 'import-progress-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            animation: fadeIn 0.3s ease;
        `;
        
        const progressBox = document.createElement('div');
        progressBox.style.cssText = `
            background: white;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            max-width: 520px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.4s ease;
        `;
        
        // Add animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from { 
                    transform: translateY(30px); 
                    opacity: 0; 
                }
                to { 
                    transform: translateY(0); 
                    opacity: 1; 
                }
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        `;
        document.head.appendChild(style);
        
        progressBox.innerHTML = `
            <div style="text-align: center; margin-bottom: 24px;">
                <div class="spinner-border" role="status" style="width: 3.5rem; height: 3.5rem; color: #2563eb;">
                    <span class="visually-hidden">Yüklənir...</span>
                </div>
            </div>
            
            <h3 style="margin-bottom: 16px; color: #0f172a; font-size: 1.4rem; font-weight: 600;">
                <i class="bi bi-upload" style="margin-right: 8px;"></i>
                Restoran İdxal Edilir
            </h3>
            
            <div style="background: #f1f5f9; padding: 16px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="bi bi-file-earmark-text" style="font-size: 1.5rem; color: #3b82f6;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #0f172a; font-size: 0.95rem;" id="import-filename">${file.name}</div>
                        <div style="color: #64748b; font-size: 0.85rem;">${fileSizeMB} MB</div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 12px;">
                    <div id="import-progress-bar" style="background: linear-gradient(90deg, #3b82f6, #2563eb); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #475569; font-size: 0.85rem;" id="import-status">Hazırlanır...</span>
                    <span style="color: #64748b; font-size: 0.85rem;" id="import-percent">0%</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 8px; padding: 12px; background: #fef3c7; border-radius: 8px; border-left: 3px solid #f59e0b;">
                <i class="bi bi-exclamation-triangle" style="color: #f59e0b; font-size: 1.2rem;"></i>
                <p style="color: #92400e; margin: 0; font-size: 0.85rem; line-height: 1.4;">
                    <strong>Diqqət:</strong> Prosеs 1-10 dəqiqə çəkə bilər. Səhifəni bağlamayın!
                </p>
            </div>
            
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 0.8rem;">
                    <i class="bi bi-clock"></i>
                    <span id="import-elapsed-time">Başlandı...</span>
                </div>
            </div>
        `;
        
        overlay.appendChild(progressBox);
        document.body.appendChild(overlay);
        
        // Progress simulation
        let progress = 0;
        let startTime = Date.now();
        const progressBar = document.getElementById('import-progress-bar');
        const progressPercent = document.getElementById('import-percent');
        const progressStatus = document.getElementById('import-status');
        const elapsedTimeEl = document.getElementById('import-elapsed-time');
        
        const statusMessages = [
            'Fayl oxunur...',
            'Məlumatlar emal edilir...',
            'Restoran yaradılır...',
            'Kateqoriyalar əlavə edilir...',
            'Məhsullar yüklənir...',
            'Şəkillər işlənir...',
            'Tamamlanır...'
        ];
        
        let statusIndex = 0;
        
        // Simulate progress
        const progressInterval = setInterval(() => {
            // Progress that slows down but never reaches 100%
            if (progress < 99) {
                // Asymptotic progress: fast at start, very slow near end
                let increment;
                if (progress < 30) {
                    increment = 2; // Fast start
                } else if (progress < 60) {
                    increment = 1; // Medium speed
                } else if (progress < 80) {
                    increment = 0.5; // Slower
                } else if (progress < 90) {
                    increment = 0.2; // Much slower
                } else if (progress < 95) {
                    increment = 0.1; // Very slow
                } else {
                    increment = 0.05; // Extremely slow but still moving
                }
                
                progress += increment;
                progressBar.style.width = progress + '%';
                progressPercent.textContent = Math.floor(progress) + '%';
                
                // Update status message every 15%
                if (progress > (statusIndex + 1) * 15 && statusIndex < statusMessages.length - 1) {
                    statusIndex++;
                    progressStatus.textContent = statusMessages[statusIndex];
                }
            }
            
            // Update elapsed time
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            elapsedTimeEl.textContent = minutes > 0 
                ? `${minutes} dəq ${seconds} san keçdi`
                : `${seconds} saniyə keçdi`;
        }, 200);
        
        // Start import with extended timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 600000); // 10 minutes timeout
        
        fetch('../api/api.php', {
            method: 'POST',
            body: formData,
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            clearInterval(progressInterval);
            return response.text().then(text => {
                const ct = (response.headers.get('Content-Type') || '').toLowerCase();
                if (!ct.includes('application/json') || (text && text.trim().startsWith('<'))) {
                    let msg = 'Server HTML səhifə qaytardı (HTTP ' + response.status + '). ';
                    if (response.status === 413) msg = 'Fayl çox böyükdür (413). php.ini-də upload_max_filesize və post_max_size artırın (90MB üçün 128M).';
                    else if (response.status === 504 || response.status === 502) msg = 'Zaman aşımı (' + response.status + '). max_execution_time artırın.';
                    else if (response.status >= 500) msg = 'Server xətası (' + response.status + '). PHP loglarını yoxlayın.';
                    else msg += '90MB fayl üçün php.ini-də upload_max_filesize=128M, post_max_size=128M təyin edin.';
                    throw new Error(msg);
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Server etibarsız cavab qaytardı.');
                }
            });
        })
        .then(data => {
            clearInterval(progressInterval);
            
            if (data.success) {
                // Complete the progress bar
                progressBar.style.width = '100%';
                progressPercent.textContent = '100%';
                progressStatus.textContent = 'Tamamlandı!';
                
                // Show success animation
                progressBox.innerHTML = `
                    <div style="text-align: center;">
                        <div style="width: 80px; height: 80px; margin: 0 auto 24px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; animation: successPop 0.5s ease;">
                            <i class="bi bi-check-lg" style="font-size: 3rem; color: white;"></i>
                        </div>
                        <h3 style="color: #059669; margin-bottom: 12px; font-size: 1.4rem;">
                            <i class="bi bi-check-circle-fill"></i>
                            Uğurla Tamamlandı!
                        </h3>
                        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 8px;">
                            Restoran idxal edildi: <strong>${data.slug}</strong>
                        </p>
                        <p style="color: #64748b; font-size: 0.85rem;">
                            Səhifə yenilənir...
                        </p>
                    </div>
                    <style>
                        @keyframes successPop {
                            0% { transform: scale(0); opacity: 0; }
                            50% { transform: scale(1.1); }
                            100% { transform: scale(1); opacity: 1; }
                        }
                    </style>
                `;
                
                // Wait 2 seconds then close
                setTimeout(() => {
                    document.body.removeChild(overlay);
                    showNotification(`Restoran uğurla idxal edildi! (${data.slug})`, 'success');
                    loadStatistics();
                    loadRestaurants();
                }, 2000);
            } else {
                // Show error
                document.body.removeChild(overlay);
                showNotification('Xəta: ' + data.message, 'error');
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            clearInterval(progressInterval);
            
            console.error('Import Error:', error);
            
            // Show error in overlay
            progressBox.innerHTML = `
                <div style="text-align: center;">
                    <div style="width: 80px; height: 80px; margin: 0 auto 24px; background: linear-gradient(135deg, #ef4444, #dc2626); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-x-lg" style="font-size: 3rem; color: white;"></i>
                    </div>
                    <h3 style="color: #dc2626; margin-bottom: 12px; font-size: 1.4rem;">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        Xəta Baş Verdi
                    </h3>
                    <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 12px; text-align: left; line-height: 1.5;">
                        ${error.name === 'AbortError' 
                            ? 'İdxal çox uzun çəkdi (10 dəqiqədən çox). Hosting limitlərini yoxlayın.' 
                            : error.message}
                    </p>
                    <p style="color: #94a3b8; font-size: 0.8rem; margin-bottom: 16px; text-align: left;">
                        Böyük fayl (90MB) üçün: php.ini → upload_max_filesize=128M, post_max_size=128M, max_execution_time=600
                    </p>
                    <button onclick="var el=document.getElementById('import-progress-overlay');if(el)el.remove();" style="background: #dc2626; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        Bağla
                    </button>
                </div>
            `;
            
            if (error.name === 'AbortError') {
                showNotification('Xəta: İdxal çox uzun çəkdi (10 dəqiqədən çox). Hosting limitlərini yoxlayın.', 'error');
            } else {
                showNotification('Xəta: ' + error.message, 'error');
            }
        });
    };
    input.click();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin panel initialized');
    // Small delay to ensure DOM is fully ready
    setTimeout(function() {
        loadStatistics();
        loadRestaurants();
    }, 100);
});
