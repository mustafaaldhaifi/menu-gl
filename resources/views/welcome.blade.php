<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="منيو إلكتروني تفاعلي فاخر لمطعم وكافيه النخبة. تصفح أشهى المأكولات والمشروبات، وخصص وجبتك بالخيارات والإضافات المفضلة، واطلب مباشرة بكل سهولة.">
    <meta name="theme-color" content="#0a0a0c">
    
    <title>مطعم وكافيه النخبة الفاخر | المنيو الإلكتروني التفاعلي 🌟</title>
    
    <!-- أيقونات FontAwesome للأشكال والأزرار -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- استدعاء ملفات التنسيق والجافا سكريبت مباشرة بدون npm -->
    <link rel="stylesheet" href="{{ asset('css/menu.css') }}">
    <script src="{{ asset('js/menu.js') }}" defer></script>
</head>
<body>

    <div class="app-container">
        
        <!-- ==================== القسم العلوي وبانر المطعم ==================== -->
        <header class="hero-section" style="background-image: url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80');">
            <div class="hero-overlay"></div>
            
            <div class="hero-controls">
                <button class="icon-btn" id="theme-toggle-btn" title="تبديل المظهر">
                    <i class="fas fa-sun"></i>
                </button>
                <div class="status-badge open">
                    <i class="fas fa-circle" style="font-size: 0.5rem; animation: pulse 1.5s infinite;"></i>
                    مفتوح الآن
                </div>
            </div>
            
            <div class="restaurant-profile">
                <!-- صورة لوغو وهمية دائرية فاخرة للمطعم -->
                <img src="https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=300&q=80" alt="شعار النخبة" class="restaurant-logo">
                <div class="restaurant-info">
                    <h1 class="restaurant-name">
                        النخبة الفاخر
                        <i class="fas fa-certificate" style="color: var(--primary); font-size: 0.95rem;" title="حساب موثق"></i>
                    </h1>
                </div>
            </div>
        </header>

        <!-- ==================== تفاصيل المطعم والتقييم ==================== -->
        <section class="restaurant-details">
            <div class="bio-text">
                <p>مرحباً بك في النخبة! نُقدم لك تجربة طهي إيطالية وعالمية استثنائية، مُحضّرة بحُب ومكوّنات طازجة يومياً. 🍕🍔☕</p>
            </div>
            <div class="rating-box">
                <div class="rating-stars">
                    <i class="fas fa-star"></i>
                    <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">4.9</span>
                </div>
                <span class="reviews-count">1.2K+ تقييم</span>
            </div>
        </section>

        <!-- ==================== البحث والفلترة السريعة ==================== -->
        <section class="search-filter-section">
            <div class="search-bar-wrapper">
                <input type="text" id="search-input" class="search-input" placeholder="ابحث عن وجبتك المفضلة...">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <!-- أزرار الفلترة الفورية -->
            <div class="quick-tags-wrapper">
                <button class="tag-badge" data-filter="popular">
                    <i class="fas fa-fire"></i> الأكثر طلباً
                </button>
                <button class="tag-badge" data-filter="vegan">
                    <i class="fas fa-leaf"></i> خيارات نباتية
                </button>
                <button class="tag-badge" data-filter="spicy">
                    <i class="fas fa-pepper-hot"></i> حار وسبايسي
                </button>
                <button class="tag-badge" data-filter="favs">
                    <i class="fas fa-heart"></i> المفضلة لدي
                </button>
            </div>
        </section>

        <!-- ==================== شريط التصنيفات اللاصق ==================== -->
        <nav class="categories-container" id="categories-container">
            <!-- سيتم توليده ديناميكياً عبر الجافاسكريبت -->
        </nav>

        <!-- ==================== قائمة المنتجات والوجبات ==================== -->
        <main class="menu-section">
            <h2 class="section-title" id="menu-current-title">كل الوجبات والمشروبات</h2>
            
            <div class="products-grid" id="products-grid">
                <!-- سيتم ملء المنتجات هنا عبر الجافاسكريبت -->
            </div>
        </main>

        <!-- ==================== الزر العائم لسلة الطلبات (Floating Cart) ==================== -->
        <div class="floating-cart-wrapper" id="floating-cart-wrapper">
            <button class="floating-cart-btn" id="btn-open-cart-drawer">
                <div class="floating-cart-left">
                    <div class="cart-icon-badge">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-items-count" id="cart-items-count">0</span>
                    </div>
                    <span class="cart-view-text">عرض سلة الطلبات</span>
                </div>
                <div class="floating-cart-right">
                    <span class="cart-total-price" id="cart-total-price">0 ر.س</span>
                    <i class="fas fa-chevron-left" style="font-size: 0.8rem;"></i>
                </div>
            </button>
        </div>

        <!-- ==================== نافذة التخصيص المنبثقة (Detail & Options Modal) ==================== -->
        <div class="modal-overlay" id="customizer-modal-overlay">
            <div class="modal-content-container">
                <div class="modal-header-image">
                    <img id="modal-product-img" src="" alt="صورة المنتج" class="modal-img">
                    <button class="close-modal-btn" id="close-customizer-btn" title="إغلاق">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <h3 class="modal-product-title" id="modal-product-title">اسم المنتج</h3>
                    <p class="modal-product-desc" id="modal-product-desc">وصف المنتج التفصيلي والشهي.</p>
                    
                    <!-- 1. الخيارات الإلزامية (مثل الحجم أو النوع) - إجباري الاختيار -->
                    <div class="customization-section">
                        <div class="customization-section-title">
                            <span>اختر الحجم / النوع</span>
                            <span class="section-badge required">إجباري اختيار واحد</span>
                        </div>
                        <div class="options-list" id="options-required-list">
                            <!-- راديو بوتنز ديناميكية للخيارات الأساسية -->
                        </div>
                    </div>
                    
                    <!-- 2. الإضافات الاختيارية (مثل الجبن الإضافي، الصلصات) -->
                    <div class="customization-section" id="addons-section-wrapper">
                        <div class="customization-section-title">
                            <span>إضافات اختيارية</span>
                            <span class="section-badge optional">اختياري</span>
                        </div>
                        <div class="options-list" id="addons-optional-list">
                            <!-- تشكبوكسس ديناميكية للإضافات -->
                        </div>
                    </div>
                </div>
                
                <!-- فوتر المودال وحساب الإجمالي والديناميكية -->
                <div class="modal-footer">
                    <!-- محدد الكمية للمنتج المخصص -->
                    <div class="quantity-control">
                        <button class="qty-btn" id="modal-qty-minus"><i class="fas fa-minus"></i></button>
                        <span class="qty-number" id="modal-qty-num">1</span>
                        <button class="qty-btn" id="modal-qty-plus"><i class="fas fa-plus"></i></button>
                    </div>
                    
                    <!-- زر التأكيد مع السعر الإجمالي الفوري للمنتج المخصص -->
                    <button class="confirm-add-btn" id="confirm-add-to-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span>إضافة للطلب - </span>
                        <span id="modal-total-price-badge">0 ر.س</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== درج السلة المنزلق من اليسار/الأسفل (Cart Drawer) ==================== -->
        <div class="cart-drawer-overlay" id="cart-drawer-overlay">
            <div class="cart-drawer-content">
                <div class="cart-drawer-header">
                    <h3 class="cart-drawer-title">
                        <i class="fas fa-shopping-basket" style="color: var(--primary);"></i>
                        سلة طلباتك الحالية
                    </h3>
                    <button class="icon-btn" id="close-cart-btn" style="background: var(--bg-input);">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </div>
                
                <!-- قائمة أصناف الطلب داخل السلة -->
                <div class="cart-items-wrapper" id="cart-items-wrapper">
                    <!-- ستولد ديناميكياً بـ JS -->
                </div>
                
                <!-- تفاصيل الحساب وإرسال الطلب للنادل -->
                <div class="cart-drawer-footer" id="cart-summary-section">
                    <div class="summary-row">
                        <span>عدد الأصناف</span>
                        <span id="summary-items-count">0 منتج</span>
                    </div>
                    <div class="summary-row total">
                        <span>الحساب الإجمالي</span>
                        <span id="summary-subtotal">0 ر.س</span>
                    </div>
                    
                    <!-- إدخال تفاصيل العميل والطاولة لإتمام الطلب -->
                    <div class="checkout-inputs-group">
                        <h4 style="font-size: 0.85rem; font-weight: 700; margin-bottom: 4px; color: var(--primary);">📋 تفاصيل تسليم الطلب</h4>
                        <input type="text" id="customer-name" class="checkout-input" placeholder="اسمك الكريم..." required>
                        <input type="number" id="table-number" class="checkout-input" placeholder="رقم طاولتك..." required>
                        <input type="text" id="order-notes" class="checkout-input" placeholder="ملاحظة خاصة بالنضج أو المكونات...">
                    </div>
                    
                    <button class="checkout-btn" id="btn-checkout">
                        <i class="fab fa-whatsapp"></i> إرسال الطلب وتأكيده بالنادل
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== مودال نجاح العملية ومحاكاة الطلب ==================== -->
        <div class="success-modal" id="success-modal-overlay">
            <div class="success-card">
                <div class="success-icon-wrapper">
                    <i class="fas fa-check"></i>
                </div>
                <h3 class="success-title">تم إرسال طلبك بنجاح!</h3>
                <p class="success-msg">لقد قمنا بتوليد الطلب وإرساله للنادل لمباشرة تحضير وجبتك الشهية. طاولتك جاهزة لتلقي الطعام الحار فوراً.</p>
                <button class="checkout-btn" id="close-success-btn" style="background: var(--success); box-shadow: none;">
                    تصفح المنيو مجدداً
                </button>
            </div>
        </div>

    </div>

</body>
</html>
