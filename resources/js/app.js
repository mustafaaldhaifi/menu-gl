import "./bootstrap";

// --- قاعدة بيانات المنتجات (Fetched from Database) ---
let PRODUCTS_DATA = [];
let CATEGORIES_DATA = [];
let VIEWS_DATA = [];

// --- إدارة حالة التطبيق (App State Management) ---
const state = {
    cart: [],
    favorites: JSON.parse(localStorage.getItem("menu_favs")) || [],
    currentTheme: localStorage.getItem("menu_theme") || "dark",
    activeCategory: "all",
    activeFilter: "all", // all, or view_id
    searchQuery: "",
    currentCustomizingProduct: null,
    customizationState: {
        productId: null,
        selectedOption: null,
        selectedAddons: [],
        quantity: 1,
    },
};

// --- تعريف العناصر من الـ DOM ---
document.addEventListener("DOMContentLoaded", () => {
    initApp();
});

async function initApp() {
    setupTheme();
    await fetchMenuData();
    renderCategories();
    renderProducts();
    setupCartDrawer();
    setupProductModal();
    setupCheckout();
    setupSearchAndFilters();
    updateCartUI();

    // إخفاء الـ Loading إن وجد
    console.log("Elite Menu initialized successfully!");
}

async function fetchMenuData() {
    try {
        const response = await fetch("/api/menu");
        if (!response.ok) throw new Error("Network response was not ok");
        const data = await response.json();
        PRODUCTS_DATA = data.products;
        CATEGORIES_DATA = data.categories;
        VIEWS_DATA = data.views;
        renderViews();
    } catch (error) {
        console.error("Error fetching menu data:", error);
        showToast("خطأ في تحميل البيانات، يرجى المحاولة لاحقاً ⚠️");
    }
}

// --- 1. إعداد وإدارة مظهر السمة (Light/Dark Mode) ---
function setupTheme() {
    const themeToggleBtn = document.getElementById("theme-toggle-btn");
    document.documentElement.setAttribute("data-theme", state.currentTheme);
    updateThemeIcon();

    themeToggleBtn.addEventListener("click", () => {
        state.currentTheme = state.currentTheme === "dark" ? "light" : "dark";
        document.documentElement.setAttribute("data-theme", state.currentTheme);
        localStorage.setItem("menu_theme", state.currentTheme);
        updateThemeIcon();
    });
}

function updateThemeIcon() {
    const themeIcon = document.querySelector("#theme-toggle-btn i");
    if (state.currentTheme === "dark") {
        themeIcon.className = "fas fa-sun";
    } else {
        themeIcon.className = "fas fa-moon";
    }
}

// --- 2. بناء التصنيفات وتوليدها ---
function renderCategories() {
    const container = document.getElementById("categories-container");
    if (!container) return;

    const categories = CATEGORIES_DATA;

    container.innerHTML = categories
        .map(
            (cat) => `
        <button class="category-pill ${state.activeCategory === cat.id ? "active" : ""}" data-cat-id="${cat.id}">
            ${cat.name}
        </button>
    `,
        )
        .join("");

    // إضافة مستمعات الأحداث للنقر على التصنيف
    container.querySelectorAll(".category-pill").forEach((pill) => {
        pill.addEventListener("click", (e) => {
            container
                .querySelectorAll(".category-pill")
                .forEach((btn) => btn.classList.remove("active"));
            const btn = e.currentTarget;
            btn.classList.add("active");
            state.activeCategory = btn.getAttribute("data-cat-id");
            renderProducts();

            // تمرير سلس
            btn.scrollIntoView({
                behavior: "smooth",
                inline: "center",
                block: "nearest",
            });
        });
    });
}

// --- 3. عرض وتوليد بطاقات المنتجات مع البحث والفلترة ---
function renderProducts() {
    const grid = document.getElementById("products-grid");
    if (!grid) return;

    // تصفية المنتجات
    let filtered = PRODUCTS_DATA.filter((prod) => {
        // فلتر التصنيف
        const matchesCat =
            state.activeCategory === "all" ||
            String(prod.category) === String(state.activeCategory);

        // فلتر البحث اللحظي
        const matchesSearch =
            (prod.name || "")
                .toLowerCase()
                .includes(state.searchQuery.toLowerCase()) ||
            (prod.description || "")
                .toLowerCase()
                .includes(state.searchQuery.toLowerCase());
        // فلاتر الميزات السريعة
        let matchesFilter = true;
        if (state.activeFilter === "favs") {
            matchesFilter = state.favorites.includes(prod.id);
        } else if (state.activeFilter !== "all") {
            matchesFilter = String(prod.view_id) === String(state.activeFilter);
        }

        return matchesCat && matchesSearch && matchesFilter;
    });

    if (filtered.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p>لم نجد أي منتجات تطابق خياراتك حالياً...</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = filtered
        .map((prod) => {
            // حساب أقل سعر خيار لعرض "يبدأ من"
            const startingPrice = Math.min(...prod.options.map((o) => o.price));
            const isFav = state.favorites.includes(prod.id);

            // تحديد تاغ مخصص
            let badgeHtml = "";
            if (prod.tags.includes("spicy")) {
                badgeHtml = `<span class="product-badge spicy"><i class="fas fa-pepper-hot"></i> حار جداً</span>`;
            } else if (prod.tags.includes("vegan")) {
                badgeHtml = `<span class="product-badge vegan"><i class="fas fa-leaf"></i> نباتي</span>`;
            }

            return `
            <div class="product-card" data-product-id="${prod.id}">
                <div class="product-image-wrapper">
                    <img src="${prod.image}" alt="${prod.name}" class="product-card-img" loading="lazy">
                    <button class="fav-btn ${isFav ? "active" : ""}" data-prod-id="${prod.id}">
                        <i class="${isFav ? "fas" : "far"} fa-heart"></i>
                    </button>
                    ${badgeHtml}
                </div>
                <div class="product-details-content">
                    <div class="product-title-row">
                        <h3 class="product-name">${prod.name}</h3>
                        <p class="product-description">${prod.description}</p>
                    </div>
                    <div class="product-footer">
                        <div class="price-display">
                            <span class="price-label">السعر الأساسي يبدأ من</span>
                            <span class="price-amount">${startingPrice} ر.س</span>
                        </div>
                        <button class="add-to-cart-btn btn-open-customizer" data-prod-id="${prod.id}">
                            <i class="fas fa-plus"></i> إضافة للطلب
                        </button>
                    </div>
                </div>
            </div>
        `;
        })
        .join("");

    // ربط الأحداث
    // 1. زر التفضيل
    grid.querySelectorAll(".fav-btn").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const id = parseInt(btn.getAttribute("data-prod-id"));
            toggleFavorite(id, btn);
        });
    });

    // 2. النقر على البطاقة بالكامل لفتح المودال
    grid.querySelectorAll(".product-card").forEach((card) => {
        card.addEventListener("click", (e) => {
            // تجنب النقر المكرر لزر المفضلة وزر التخصيص
            if (e.target.closest(".fav-btn")) return;
            const id = parseInt(card.getAttribute("data-product-id"));
            openCustomizerModal(id);
        });
    });

    // 3. زر الإضافة للطلب
    grid.querySelectorAll(".btn-open-customizer").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const id = parseInt(btn.getAttribute("data-prod-id"));
            openCustomizerModal(id);
        });
    });
}

// إدارة المفضلات بالـ LocalStorage
function toggleFavorite(id, btnElement) {
    const idx = state.favorites.indexOf(id);
    const heart = btnElement.querySelector("i");

    if (idx > -1) {
        state.favorites.splice(idx, 1);
        btnElement.classList.remove("active");
        heart.className = "far fa-heart";
        showToast("تمت إزالة المنتج من المفضلة");
    } else {
        state.favorites.push(id);
        btnElement.classList.add("active");
        heart.className = "fas fa-heart";
        showToast("تمت إضافة المنتج للمفضلة ❤️");
    }
    localStorage.setItem("menu_favs", JSON.stringify(state.favorites));

    // إذا كنا نعرض المفضلة حالياً، يجب إعادة التوليد فوراً لإزالة الصنف
    if (state.activeFilter === "favs") {
        renderProducts();
    }
}

// --- 4. معالجة البحث والفلترة السريعة ---
function renderViews() {
    const container = document.querySelector(".quick-tags-wrapper");
    if (!container) return;

    container.innerHTML =
        VIEWS_DATA.map(
            (view) => `
        <button class="tag-badge ${state.activeFilter == view.id ? "active" : ""}" data-filter="${view.id}">
            ${view.name}
        </button>
    `,
        ).join("") +
        `
        <button class="tag-badge ${state.activeFilter === "favs" ? "active" : ""}" data-filter="favs">
            <i class="fas fa-heart"></i> المفضلة لدي
        </button>
    `;

    setupViewsEvents();
}

function setupViewsEvents() {
    const filterTags = document.querySelectorAll(
        ".quick-tags-wrapper .tag-badge",
    );
    filterTags.forEach((tag) => {
        tag.addEventListener("click", (e) => {
            const filterType = tag.getAttribute("data-filter");

            if (state.activeFilter == filterType) {
                state.activeFilter = "all";
                tag.classList.remove("active");
            } else {
                filterTags.forEach((t) => t.classList.remove("active"));
                state.activeFilter = filterType;
                tag.classList.add("active");
            }
            renderProducts();
        });
    });
}

function setupSearchAndFilters() {
    const searchInput = document.getElementById("search-input");
    if (searchInput) {
        searchInput.addEventListener("input", (e) => {
            state.searchQuery = e.target.value.trim();
            renderProducts();
        });
    }
}

// --- 5. منطق تخصيص وتجميع المنتج وحساب الأسعار التفاعلية ---
function setupProductModal() {
    const overlay = document.getElementById("customizer-modal-overlay");
    const closeBtn = document.getElementById("close-customizer-btn");

    if (closeBtn && overlay) {
        closeBtn.addEventListener("click", () => {
            overlay.classList.remove("active");
        });
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) {
                overlay.classList.remove("active");
            }
        });
    }

    // مستمعات الكمية داخل المودال
    const modalMinus = document.getElementById("modal-qty-minus");
    const modalPlus = document.getElementById("modal-qty-plus");
    const modalQtyNum = document.getElementById("modal-qty-num");

    if (modalMinus && modalPlus && modalQtyNum) {
        modalMinus.addEventListener("click", () => {
            if (state.customizationState.quantity > 1) {
                state.customizationState.quantity--;
                modalQtyNum.innerText = state.customizationState.quantity;
                calculateModalPrice();
            }
        });

        modalPlus.addEventListener("click", () => {
            state.customizationState.quantity++;
            modalQtyNum.innerText = state.customizationState.quantity;
            calculateModalPrice();
        });
    }

    // زر التأكيد والإضافة النهائية للسلة
    const confirmBtn = document.getElementById("confirm-add-to-cart");
    if (confirmBtn) {
        confirmBtn.addEventListener("click", () => {
            confirmProductAddition();
        });
    }
}

// فتح نافذة التخصيص وملء البيانات
function openCustomizerModal(productId) {
    const prod = PRODUCTS_DATA.find((p) => p.id === productId);
    if (!prod) return;

    state.currentCustomizingProduct = prod;

    // إعادة تعيين تفاصيل حالة التخصيص
    state.customizationState = {
        productId: prod.id,
        selectedOption: prod.options[0], // نحدد أول خيار إجباري بشكل افتراضي
        selectedAddons: [],
        quantity: 1,
    };

    // تعبئة البيانات بالـ DOM
    document.getElementById("modal-product-img").src =
        prod.images && prod.images.length > 0 ? prod.images[0] : prod.image;
    document.getElementById("modal-product-title").innerText = prod.name;
    document.getElementById("modal-product-desc").innerText = prod.description;
    document.getElementById("modal-qty-num").innerText =
        state.customizationState.quantity;

    // توليد قائمة الخيارات الإجبارية (Required)
    const optionsContainer = document.getElementById("options-required-list");
    optionsContainer.innerHTML = prod.options
        .map(
            (opt, idx) => `
        <label class="custom-option-label">
            <div class="option-info-side">
                <input type="radio" name="product-required-option" value="${opt.id}" ${idx === 0 ? "checked" : ""}>
                <span class="custom-selector circle"></span>
                <span class="option-label-text">${opt.name}</span>
            </div>
            <span class="option-price-badge">${opt.price} ر.س</span>
        </label>
    `,
        )
        .join("");

    // ربط الحدث لأزرار الراديو لإعادة الحساب الفوري للسعر
    optionsContainer
        .querySelectorAll('input[type="radio"]')
        .forEach((radio) => {
            radio.addEventListener("change", (e) => {
                const selectedOptId = e.target.value;
                const selectedOpt = prod.options.find(
                    (o) => o.id == selectedOptId,
                );
                state.customizationState.selectedOption = selectedOpt;

                // تحديث الصورة إذا كان للخيار صورة خاصة
                if (selectedOpt && selectedOpt.image) {
                    document.getElementById("modal-product-img").src =
                        selectedOpt.image;
                } else {
                    document.getElementById("modal-product-img").src =
                        prod.images && prod.images.length > 0
                            ? prod.images[0]
                            : prod.image;
                }

                calculateModalPrice();
            });
        });

    // توليد قائمة الإضافات الاختيارية (Optional Add-ons)
    const addonsContainer = document.getElementById("addons-optional-list");
    if (prod.addons && prod.addons.length > 0) {
        document.getElementById("addons-section-wrapper").style.display =
            "block";
        addonsContainer.innerHTML = prod.addons
            .map(
                (addon) => `
            <label class="custom-option-label">
                <div class="option-info-side">
                    <input type="checkbox" name="product-optional-addon" value="${addon.id}">
                    <span class="custom-selector square"></span>
                    <span class="option-label-text">${addon.name}</span>
                </div>
                <span class="option-price-badge">+${addon.price} ر.س</span>
            </label>
        `,
            )
            .join("");

        // ربط الحدث لتشيكبوكس الإضافات لإعادة حساب السعر الفوري
        addonsContainer
            .querySelectorAll('input[type="checkbox"]')
            .forEach((check) => {
                check.addEventListener("change", () => {
                    const checkedElements = addonsContainer.querySelectorAll(
                        'input[type="checkbox"]:checked',
                    );
                    state.customizationState.selectedAddons = Array.from(
                        checkedElements,
                    ).map((el) => {
                        return prod.addons.find((a) => a.id === el.value);
                    });
                    calculateModalPrice();
                });
            });
    } else {
        document.getElementById("addons-section-wrapper").style.display =
            "none";
    }

    // حساب وعرض السعر الأولي
    calculateModalPrice();

    // تفعيل النافذة
    document.getElementById("customizer-modal-overlay").classList.add("active");
}

// حساب السعر التفاعلي اللحظي في المودال
function calculateModalPrice() {
    const prod = state.currentCustomizingProduct;
    if (!prod) return;

    const optionPrice = state.customizationState.selectedOption
        ? state.customizationState.selectedOption.price
        : 0;
    const addonsTotal = state.customizationState.selectedAddons.reduce(
        (sum, item) => sum + item.price,
        0,
    );
    const quantity = state.customizationState.quantity;

    const finalPrice = (optionPrice + addonsTotal) * quantity;

    // تحديث السعر المعروض في زر التأكيد السفلي
    document.getElementById("modal-total-price-badge").innerText =
        `${finalPrice} ر.س`;
}

// تأكيد إضافة المنتج وحفظ التخصيص في السلة
function confirmProductAddition() {
    const prod = state.currentCustomizingProduct;
    if (!prod) return;

    const newCartItem = {
        uniqueId: `${prod.id}-${state.customizationState.selectedOption.id}-${state.customizationState.selectedAddons
            .map((a) => a.id)
            .sort()
            .join("-")}`,
        product: prod,
        option: state.customizationState.selectedOption,
        addons: [...state.customizationState.selectedAddons],
        quantity: state.customizationState.quantity,
        singleItemPrice:
            state.customizationState.selectedOption.price +
            state.customizationState.selectedAddons.reduce(
                (sum, a) => sum + a.price,
                0,
            ),
    };

    // التحقق من تكرار نفس المنتج تماماً بنفس الخيارات والإضافات لزيادة الكمية فقط
    const existingIndex = state.cart.findIndex(
        (item) => item.uniqueId === newCartItem.uniqueId,
    );
    if (existingIndex > -1) {
        state.cart[existingIndex].quantity += newCartItem.quantity;
    } else {
        state.cart.push(newCartItem);
    }

    // إغلاق المودال وتحديث السلة
    document
        .getElementById("customizer-modal-overlay")
        .classList.remove("active");
    updateCartUI();

    // تفعيل انيميشن لطيف وإشعار نجاح
    showToast(`تمت إضافة ${prod.name} لسلة طلباتك 🛒`);

    // تفعيل اهتزاز خفيف لزر السلة العائم لتنبيه العميل
    const floatCart = document.getElementById("floating-cart-wrapper");
    floatCart.style.animation = "none";
    setTimeout(() => {
        floatCart.style.animation = "cartGlow 2.5s infinite alternate";
    }, 100);
}

// --- 6. إدارة سلة المشتريات التفاعلية والـ Drawer المنزلق ---
function setupCartDrawer() {
    const overlay = document.getElementById("cart-drawer-overlay");
    const closeBtn = document.getElementById("close-cart-btn");
    const openBtn = document.getElementById("floating-cart-wrapper");

    if (openBtn && overlay && closeBtn) {
        openBtn.addEventListener("click", () => {
            overlay.classList.add("active");
            renderCartItems();
        });

        closeBtn.addEventListener("click", () => {
            overlay.classList.remove("active");
        });

        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) {
                overlay.classList.remove("active");
            }
        });
    }
}

// تحديث الواجهة العامة للسلة والزر العائم السفلي
function updateCartUI() {
    const totalItems = state.cart.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = state.cart.reduce(
        (sum, item) => sum + item.singleItemPrice * item.quantity,
        0,
    );

    const floatingCart = document.getElementById("floating-cart-wrapper");
    const countBadge = document.getElementById("cart-items-count");
    const totalDisplay = document.getElementById("cart-total-price");

    if (totalItems > 0) {
        // إظهار السلة العائمة
        floatingCart.classList.add("active");
        countBadge.innerText = totalItems;
        totalDisplay.innerText = `${totalPrice} ر.س`;
    } else {
        // إخفاء السلة العائمة
        floatingCart.classList.remove("active");

        // إغلاق لوحة السلة المنزلقة تلقائياً إذا أصبحت فارغة
        document
            .getElementById("cart-drawer-overlay")
            .classList.remove("active");
    }
}

// توليد عناصر السلة في اللوحة المنزلقة
function renderCartItems() {
    const container = document.getElementById("cart-items-wrapper");
    const checkoutBtn = document.getElementById("btn-checkout");

    if (!container) return;

    if (state.cart.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-shopping-basket"></i>
                <p>سلة طلباتك فارغة حالياً... تصفح المنيو واختر ما تحبه!</p>
            </div>
        `;
        document.getElementById("cart-summary-section").style.display = "none";
        if (checkoutBtn) checkoutBtn.style.display = "none";
        return;
    }

    document.getElementById("cart-summary-section").style.display = "block";
    if (checkoutBtn) checkoutBtn.style.display = "block";

    container.innerHTML = state.cart
        .map((item) => {
            const addonsText =
                item.addons.length > 0
                    ? `إضافات: ${item.addons.map((a) => a.name).join("، ")}`
                    : "بدون إضافات";

            return `
            <div class="cart-item-card" data-unique-id="${item.uniqueId}">
                <div class="cart-item-info">
                    <span class="cart-item-name">${item.product.name}</span>
                    <span class="cart-item-customizations">${item.option.name} | ${addonsText}</span>
                    <span class="cart-item-price">${item.singleItemPrice * item.quantity} ر.س</span>
                </div>
                <div class="cart-item-controls">
                    <button class="remove-item-btn" data-unique-id="${item.uniqueId}">
                        <i class="far fa-trash-alt"></i>
                    </button>
                    <div class="quantity-control">
                        <span class="qty-btn btn-cart-minus" data-unique-id="${item.uniqueId}">-</span>
                        <span class="qty-number">${item.quantity}</span>
                        <span class="qty-btn btn-cart-plus" data-unique-id="${item.uniqueId}">+</span>
                    </div>
                </div>
            </div>
        `;
        })
        .join("");

    // ربط الأحداث داخل السلة
    // 1. الحذف بالكامل
    container.querySelectorAll(".remove-item-btn").forEach((btn) => {
        btn.addEventListener("click", () => {
            const uniqueId = btn.getAttribute("data-unique-id");
            removeCartItem(uniqueId);
        });
    });

    // 2. تخفيض الكمية
    container.querySelectorAll(".btn-cart-minus").forEach((btn) => {
        btn.addEventListener("click", () => {
            const uniqueId = btn.getAttribute("data-unique-id");
            changeCartItemQty(uniqueId, -1);
        });
    });

    // 3. زيادة الكمية
    container.querySelectorAll(".btn-cart-plus").forEach((btn) => {
        btn.addEventListener("click", () => {
            const uniqueId = btn.getAttribute("data-unique-id");
            changeCartItemQty(uniqueId, 1);
        });
    });

    // تحديث ملخص الحساب
    updateCartSummary();
}

function removeCartItem(uniqueId) {
    const index = state.cart.findIndex((item) => item.uniqueId === uniqueId);
    if (index > -1) {
        const item = state.cart[index];
        state.cart.splice(index, 1);
        updateCartUI();
        renderCartItems();
        showToast(`تمت إزالة ${item.product.name} من السلة`);
    }
}

function changeCartItemQty(uniqueId, delta) {
    const index = state.cart.findIndex((item) => item.uniqueId === uniqueId);
    if (index > -1) {
        state.cart[index].quantity += delta;

        // إذا أصبحت الكمية صفر أو أقل يتم الحذف
        if (state.cart[index].quantity <= 0) {
            state.cart.splice(index, 1);
            showToast("تم حذف الصنف لتصفير الكمية");
        }

        updateCartUI();
        renderCartItems();
    }
}

function updateCartSummary() {
    const totalItems = state.cart.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = state.cart.reduce(
        (sum, item) => sum + item.singleItemPrice * item.quantity,
        0,
    );

    document.getElementById("summary-items-count").innerText =
        `${totalItems} منتج`;
    document.getElementById("summary-subtotal").innerText = `${totalPrice} ر.س`;
    document.getElementById("summary-total").innerText = `${totalPrice} ر.س`;
}

// --- 7. محاكاة عملية إتمام الطلب والتحقق المزدوج وإرسال واتساب ---
function setupCheckout() {
    const checkoutBtn = document.getElementById("btn-checkout");
    const successCloseBtn = document.getElementById("close-success-btn");

    if (checkoutBtn) {
        checkoutBtn.addEventListener("click", () => {
            processCheckout();
        });
    }

    if (successCloseBtn) {
        successCloseBtn.addEventListener("click", () => {
            document
                .getElementById("success-modal-overlay")
                .classList.remove("active");
            // تصفير السلة
            state.cart = [];
            updateCartUI();
            document
                .getElementById("cart-drawer-overlay")
                .classList.remove("active");
        });
    }
}

function processCheckout() {
    const nameInput = document.getElementById("customer-name");
    const tableInput = document.getElementById("table-number");
    const noteInput = document.getElementById("order-notes");

    const name = nameInput.value.trim();
    const table = tableInput.value.trim();
    const notes = noteInput.value.trim();

    // التحقق المبدئي الفاخر
    if (!name) {
        showToast("يرجى كتابة الاسم لتسجيل طلبك ⚠️");
        nameInput.focus();
        return;
    }
    if (!table) {
        showToast("يرجى إدخال رقم الطاولة لخدمتك بسرعة ⚠️");
        tableInput.focus();
        return;
    }

    // صياغة تفاصيل الطلب بشكل متكامل وجذاب لإرساله واتساب
    let whatsappText = `*طلب جديد من منيو النخبة الفاخر* 🌟\n\n`;
    whatsappText += `👤 *العميل:* ${name}\n`;
    whatsappText += `📍 *رقم الطاولة:* ${table}\n`;
    if (notes) {
        whatsappText += `📝 *ملاحظات خاصة:* ${notes}\n`;
    }
    whatsappText += `\n-----------------------------\n`;

    state.cart.forEach((item, index) => {
        whatsappText += `*${index + 1}. ${item.product.name}*\n`;
        whatsappText += `   ▪️ النوع: ${item.option.name}\n`;
        if (item.addons.length > 0) {
            whatsappText += `   ▪️ الإضافات: ${item.addons.map((a) => a.name).join("، ")}\n`;
        }
        whatsappText += `   ▪️ الكمية: ${item.quantity} | السعر: ${item.singleItemPrice * item.quantity} ر.س\n\n`;
    });

    const totalPrice = state.cart.reduce(
        (sum, item) => sum + item.singleItemPrice * item.quantity,
        0,
    );
    whatsappText += `-----------------------------\n`;
    whatsappText += `💰 *إجمالي الطلب:* ${totalPrice} ر.س\n`;
    whatsappText += `✨ شكراً لطلبكم ونرجو لكم وجبة شهية!`;

    // ترميز النص ليتوافق مع رابط WhatsApp
    const encodedText = encodeURIComponent(whatsappText);
    const whatsappUrl = `https://api.whatsapp.com/send?phone=966500000000&text=${encodedText}`; // رقم واتساب افتراضي

    // فتح نافذة النجاح الفاخرة أولاً
    document.getElementById("success-modal-overlay").classList.add("active");

    // تفريغ المدخلات
    nameInput.value = "";
    tableInput.value = "";
    noteInput.value = "";

    // فتح رابط واتساب بعد ثانية واحدة لإجراء الطلب الفعلي
    setTimeout(() => {
        window.open(whatsappUrl, "_blank");
    }, 1200);
}

// --- 8. أدوات تحسين تجربة الزائر (Toasts) ---
function showToast(message) {
    // إنشاء كائن توست يدويًا
    const toast = document.createElement("div");
    toast.className = "toast-notification";
    toast.innerHTML = `
        <div style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(5px); border: 1px solid var(--primary); padding: 12px 24px; border-radius: var(--radius-md); box-shadow: var(--shadow-premium); font-size: 0.85rem; font-weight: 600; color: #fff; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-info-circle" style="color: var(--primary);"></i>
            <span>${message}</span>
        </div>
    `;
    toast.style.position = "fixed";
    toast.style.bottom = "100px";
    toast.style.left = "50%";
    toast.style.transform = "translateX(-50%) translateY(50px)";
    toast.style.opacity = "0";
    toast.style.zIndex = "200";
    toast.style.transition =
        "all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275)";

    document.body.appendChild(toast);

    // ظهور سريع
    setTimeout(() => {
        toast.style.transform = "translateX(-50%) translateY(0)";
        toast.style.opacity = "1";
    }, 50);

    // اختفاء
    setTimeout(() => {
        toast.style.transform = "translateX(-50%) translateY(-20px)";
        toast.style.opacity = "0";
        setTimeout(() => {
            toast.remove();
        }, 350);
    }, 2500);
}
