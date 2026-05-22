<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\NestedSection;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductAddon;
use App\Models\ProductImage;
use App\Models\Section;
use App\Models\StoreProductView;
use App\Models\ProductView;
use DB;
use Exception;
use Http;
use Illuminate\Http\Request;
use Log;
use PDO;
use Storage;
use Str;

class SyncController extends Controller
{
    public function index(Request $request)
    {
        // 1. إعداد الحماية باستخدام أسلوب لارافل القياسي للـ Headers
        $sentKey = $request->header('X-API-KEY') ?? '';
        if (trim($sentKey) !== "SECRET_KEY_123") {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // 2. جلب الاستراتيجية مباشرة من كائن الـ Request الآمن للارافل
        $strategy = $request->input('payload.strategy') ?? $request->input('strategy');

        // تحديد مسار المجلد المخصص للصور إذا دعت الحاجة
        $uploadBase = public_path('uploads/images/products');

        if ($strategy === 'replaceAll') {
            return $this->replaceAll($request);
        } elseif ($strategy === 'addOrUpdateOne' || $strategy === 'addOrUpdate') {

            // استدعاء دالة التحديث وتمرير الـ Request لها
            return $this->addOrUpdateOne($request);

        } elseif ($strategy === 'delete') {
            // $this->deleteStrategy($request->all());
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid Strategy',
                'strategy_received' => $strategy
            ], 400);
        }
    }
    function addOrUpdateOne(Request $request)
    {
        // جلب المصفوفات بالطريقة الصحيحة للارافل مباشرة بناءً على الـ Log والـ Payload المتوقع
        $store_categories = $request->input('store_categories') ?? $request->input('storeCategories', []);
        $store_sections = $request->input('store_sections') ?? $request->input('storeSections', []);
        $store_nested_sections = $request->input('store_nested_sections', []);
        $products = $request->input('products', []);
        $product_options = $request->input('product_options') ?? $request->input('productOptions', []);
        $products_images = $request->input('product_images') ?? $request->input('productsImages') ?? $request->input('productImages', []);
        $product_addons = $request->input('product_addons') ?? $request->input('productAddons', []);
        $store_product_views = $request->input('store_product_views') ?? $request->input('storeProductViews', []);
        $product_views = $request->input('product_views') ?? $request->input('productViews', []);

        // Log::error($request->all());
        try {
            DB::transaction(function () use (
                $store_categories,
                $store_sections,
                $store_nested_sections,
                $products,
                $product_options,
                $products_images,
                $product_addons,
                $store_product_views,
                $product_views
            ) {

                // 1. الفئات الرئيسية للمتجر (store_categories)
                foreach ($store_categories as $catWrapper) {
                    try {
                        $cat = $catWrapper['stdClass'] ?? $catWrapper;
                        if (!isset($cat['id'])) {
                            continue;
                        }

                        $coverName = $cat['cover'] ?? null;
                        if (!empty($cat['cover'])) {
                            $prefix = "cat_main_{$cat['id']}_";
                            $localImage = $this->handleLaravelImageDownload($cat['cover'], 'categories', $prefix);
                            $coverName = $localImage ?? $cat['cover'];
                        }

                        $insertData = [
                            'id' => $cat['id'],
                            'name' => $cat['name'] ?? null,
                            'cover' => $coverName,
                            'order_no' => $cat['order_no'] ?? $cat['orderNo'] ?? 0,
                            'order_at' => $cat['order_at'] ?? $cat['orderAt'] ?? null,
                            'store_branch_id' => $cat['store_branch_id'] ?? $cat['storeBranchId'] ?? null,
                            'is_hidden' => $cat['is_hidden'] ?? $cat['isHidden'] ?? 0,
                            'enabled' => $cat['enabled'] ?? 1,
                            'created_at' => $cat['created_at'] ?? $cat['createdAt'] ?? now(),
                            'updated_at' => now(),
                        ];

                        $updateColumns = [
                            'name',
                            'cover',
                            'order_no',
                            'order_at',
                            'store_branch_id',
                            'is_hidden',
                            'enabled',
                            'updated_at'
                        ];

                        Category::upsert([$insertData], ['id'], $updateColumns);

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة الفئة الرئيسية ID: " . ($cat['id'] ?? 'unknown') . " - " . $e->getMessage());
                    }
                }

                // 2. أقسام المتجر (store_sections)
                foreach ($store_sections as $secWrapper) {
                    try {
                        $sec = $secWrapper['stdClass'] ?? $secWrapper;
                        if (!isset($sec['id'])) {
                            continue;
                        }

                        $coverName = $sec['cover'] ?? null;
                        if (!empty($sec['cover'])) {
                            $prefix = "sec_{$sec['id']}_";
                            $localImage = $this->handleLaravelImageDownload($sec['cover'], 'sections', $prefix);
                            $coverName = $localImage ?? $sec['cover'];
                        }

                        $insertData = [
                            'id' => $sec['id'],
                            'name' => $sec['name'] ?? null,
                            'cover' => $coverName,
                            'order_no' => $sec['order_no'] ?? $sec['orderNo'] ?? 0,
                            'order_at' => $sec['order_at'] ?? $sec['orderAt'] ?? null,
                            'store_branch_id' => $sec['store_branch_id'] ?? $sec['storeBranchId'] ?? null,
                            'store_category_id' => $sec['store_category_id'] ?? $sec['storeCategoryId'] ?? null,
                            'is_hidden' => $sec['is_hidden'] ?? $sec['isHidden'] ?? 0,
                            'enabled' => $sec['enabled'] ?? 1,
                            'created_at' => $sec['created_at'] ?? $sec['createdAt'] ?? now(),
                            'updated_at' => now(),
                        ];

                        $updateColumns = [
                            'name',
                            'cover',
                            'order_no',
                            'order_at',
                            'store_branch_id',
                            'store_category_id',
                            'is_hidden',
                            'enabled',
                            'updated_at'
                        ];

                        Section::upsert([$insertData], ['id'], $updateColumns);

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة قسم المتجر ID: " . ($sec['id'] ?? 'unknown') . " - " . $e->getMessage());
                    }
                }

                // 3. الأقسام الفرعية (store_nested_sections)
                foreach ($store_nested_sections as $sectionWrapper) {
                    try {
                        $cat = $sectionWrapper['stdClass'] ?? $sectionWrapper;
                        if (!isset($cat['id'])) {
                            continue;
                        }

                        $coverName = $cat['cover'] ?? null;
                        if (!empty($cat['cover'])) {
                            $prefix = "cat_{$cat['id']}_";
                            $localImage = $this->handleLaravelImageDownload($cat['cover'], 'nested_sections', $prefix);
                            $coverName = $localImage ?? $cat['cover'];
                        }

                        $insertData = [
                            'id' => $cat['id'],
                            'name' => $cat['name'] ?? null,
                            'cover' => $coverName,
                            'order_no' => $cat['order_no'] ?? 0,
                            'order_at' => $cat['order_at'] ?? null,
                            'store_branch_id' => $cat['store_branch_id'] ?? null,
                            'store_section_id' => $cat['store_section_id'] ?? null,
                            'is_hidden' => $cat['is_hidden'] ?? 0,
                            'enabled' => $cat['enabled'] ?? 1,
                            'created_at' => $cat['created_at'] ?? now(),
                            'updated_at' => now(),
                        ];

                        $updateColumns = [
                            'name',
                            'cover',
                            'order_no',
                            'order_at',
                            'store_branch_id',
                            'store_section_id',
                            'is_hidden',
                            'enabled',
                            'updated_at'
                        ];

                        NestedSection::upsert([$insertData], ['id'], $updateColumns);

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة القسم ID: " . ($cat['id'] ?? 'unknown') . " - " . $e->getMessage());
                    }
                }

                // 4. المنتجات (products)
                foreach ($products as $prodWrapper) {
                    try {
                        $prod = $prodWrapper['stdClass'] ?? $prodWrapper;
                        if (!isset($prod['id'])) {
                            continue;
                        }

                        $coverName = $prod['cover'] ?? null;
                        if (!empty($prod['cover'])) {
                            $prefix = "prod_{$prod['id']}_";
                            $localImage = $this->handleLaravelImageDownload($prod['cover'], 'products/cover', $prefix);
                            $coverName = $localImage ?? $prod['cover'];
                        }

                        $insertData = [
                            'id' => $prod['id'],
                            'name' => $prod['name'] ?? null,
                            'description' => $prod['description'] ?? null,
                            'store_nested_section_id' => $prod['store_nested_section_id'] ?? $prod['storeNestedSectionId'] ?? 0,
                            'cover' => $coverName,
                            'created_at' => $prod['created_at'] ?? $prod['createdAt'] ?? now(),
                            'updated_at' => now(),
                        ];

                        $updateColumns = [
                            'name',
                            'description',
                            'store_nested_section_id',
                            'cover',
                            'updated_at'
                        ];

                        Product::upsert([$insertData], ['id'], $updateColumns);

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة المنتج ID: " . ($prod['id'] ?? 'unknown') . " - " . $e->getMessage());
                    }
                }

                // 5. خيارات المنتجات (product_options)
                foreach ($product_options as $optWrapper) {
                    try {
                        $opt = $optWrapper['stdClass'] ?? $optWrapper;
                        if (!isset($opt['id'])) {
                            continue;
                        }

                        $coverName = $opt['cover'] ?? null;
                        if (!empty($opt['cover'])) {
                            $prefix = "opt_{$opt['id']}_";
                            $localImage = $this->handleLaravelImageDownload($opt['cover'], 'options/cover', $prefix);
                            $coverName = $localImage ?? $opt['cover'];
                        }

                        $info = $opt['info'] ?? '[]';
                        if (is_array($info) || is_object($info)) {
                            $info = json_encode($info);
                        }

                        $insertData = [
                            'id' => $opt['id'],
                            'product_id' => $opt['product_id'] ?? $opt['productId'] ?? null,
                            'name' => $opt['name'] ?? null,
                            'description' => $opt['description'] ?? null,
                            'store_nested_section_id' => $opt['store_nested_section_id'] ?? $opt['storeNestedSectionId'] ?? null,
                            'store_product_view_id' => $opt['store_product_view_id'] ?? $opt['storeProductViewId'] ?? null,
                            'currency_id' => $opt['currency_id'] ?? $opt['currencyId'] ?? 1,
                            'price' => $opt['price'] ?? 0,
                            'pre_price' => $opt['pre_price'] ?? $opt['prePrice'] ?? 0,
                            'info' => $info,
                            'is_hidden' => $opt['is_hidden'] ?? $opt['isHidden'] ?? 0,
                            'enabled' => $opt['enabled'] ?? 1,
                            'order_no' => $opt['order_no'] ?? $opt['orderNo'] ?? 0,
                            'order_at' => $opt['order_at'] ?? $opt['orderAt'] ?? null,
                            'store_branch_id' => $opt['store_branch_id'] ?? $opt['storeBranchId'] ?? null,
                            'cover' => $coverName,
                            'created_at' => $opt['created_at'] ?? $opt['createdAt'] ?? now(),
                            'updated_at' => now(),
                        ];

                        $updateColumns = [
                            'product_id',
                            'name',
                            'description',
                            'store_nested_section_id',
                            'store_product_view_id',
                            'currency_id',
                            'price',
                            'pre_price',
                            'info',
                            'is_hidden',
                            'enabled',
                            'order_no',
                            'order_at',
                            'store_branch_id',
                            'cover',
                            'updated_at'
                        ];

                        ProductOption::upsert([$insertData], ['id'], $updateColumns);

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة خيار المنتج ID: " . ($opt['id'] ?? 'unknown') . " - " . $e->getMessage());
                    }
                }

                // 6. صور معرض المنتجات (product_images)
                foreach ($products_images as $imgWrapper) {
                    try {
                        $img = $imgWrapper['stdClass'] ?? $imgWrapper;

                        $prodId = $img['product_id'] ?? $img['productId'] ?? null;
                        $imgUrl = $img['image'] ?? null;

                        if (!$prodId || !$imgUrl) {
                            continue;
                        }

                        $prefix = "gallery_{$prodId}_";
                        $localImage = $this->handleLaravelImageDownload($imgUrl, 'products/gallery', $prefix);
                        $imageName = $localImage ?? $imgUrl;

                        ProductImage::updateOrCreate(
                            [
                                'product_id' => $prodId,
                                'image' => $imageName
                            ],
                            [
                                'store_branch_id' => $img['store_branch_id'] ?? $img['storeBranchId'] ?? 1,
                                'created_at' => $img['created_at'] ?? $img['createdAt'] ?? now(),
                                'updated_at' => now()
                            ]
                        );

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة صورة المنتج للمعرض: " . $e->getMessage());
                    }
                }

                // 7. إضافات المنتجات (product_addons)
                foreach ($product_addons as $addonWrapper) {
                    try {
                        $addon = $addonWrapper['stdClass'] ?? $addonWrapper;
                        if (!isset($addon['id'])) {
                            continue;
                        }

                        $insertData = [
                            'id' => $addon['id'],
                            'product_id' => $addon['product_id'] ?? $addon['productId'] ?? null,
                            'name' => $addon['name'] ?? null,
                            'price' => $addon['price'] ?? 0,
                            'is_hidden' => $addon['is_hidden'] ?? $addon['isHidden'] ?? 0,
                            'enabled' => $addon['enabled'] ?? 1,
                            'order_no' => $addon['order_no'] ?? $addon['orderNo'] ?? 0,
                            'store_branch_id' => $addon['store_branch_id'] ?? $addon['storeBranchId'] ?? null,
                            'order_at' => $addon['order_at'] ?? $addon['orderAt'] ?? null,
                            'created_at' => $addon['created_at'] ?? $addon['createdAt'] ?? now(),
                            'updated_at' => now(),
                        ];

                        $updateColumns = [
                            'product_id',
                            'name',
                            'price',
                            'is_hidden',
                            'enabled',
                            'order_no',
                            'store_branch_id',
                            'order_at',
                            'updated_at'
                        ];

                        ProductAddon::upsert([$insertData], ['id'], $updateColumns);

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة الإضافة ID: " . ($addon['id'] ?? 'unknown') . " - " . $e->getMessage());
                    }
                }

                // 8. طرق عرض منتجات المتجر (store_product_views)
                foreach ($store_product_views as $viewWrapper) {
                    try {
                        $view = $viewWrapper['stdClass'] ?? $viewWrapper;
                        if (!isset($view['id'])) {
                            continue;
                        }

                        // $coverName = $view['cover'] ?? null;
                        // if (!empty($view['cover'])) {
                        //     $prefix = "view_{$view['id']}_";
                        //     $localImage = $this->handleLaravelImageDownload($view['cover'], 'product_views/cover', $prefix);
                        //     $coverName = $localImage ?? $view['cover'];
                        // }

                        $insertData = [
                            'id' => $view['id'],
                            'name' => $view['name'] ?? null,
                            // 'cover' => $coverName,
                            // 'order_no' => $view['order_no'] ?? $view['orderNo'] ?? 0,
                            // 'order_at' => $view['order_at'] ?? $view['orderAt'] ?? null,
                            'store_branch_id' => $view['store_branch_id'] ?? $view['storeBranchId'] ?? null,
                            // 'is_hidden' => $view['is_hidden'] ?? $view['isHidden'] ?? 0,
                            // 'enabled' => $view['enabled'] ?? 1,
                            'created_at' => $view['created_at'] ?? $view['createdAt'] ?? now(),
                            'updated_at' => now(),
                        ];

                        $updateColumns = [
                            'name',
                            // 'cover',
                            // 'order_no',
                            // 'order_at',
                            'store_branch_id',
                            // 'is_hidden',
                            // 'enabled',
                            'updated_at'
                        ];

                        StoreProductView::upsert([$insertData], ['id'], $updateColumns);

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة عرض المنتجات ID: " . ($view['id'] ?? 'unknown') . " - " . $e->getMessage());
                    }
                }

                // 9. ربط المنتجات بطرق العرض (product_views)
                foreach ($product_views as $pViewWrapper) {
                    try {
                        $pv = $pViewWrapper['stdClass'] ?? $pViewWrapper;

                        $prodId = $pv['product_id'] ?? $pv['productId'] ?? null;
                        $viewId = $pv['store_product_view_id'] ?? $pv['storeProductViewId'] ?? null;

                        if (!$prodId || !$viewId) {
                            continue;
                        }

                        ProductView::updateOrCreate(
                            [
                                'product_id' => $prodId,
                                'store_product_view_id' => $viewId
                            ],
                            [
                                'id' => $pv['id'] ?? null,
                                'created_at' => $pv['created_at'] ?? $pv['createdAt'] ?? now(),
                                'updated_at' => now()
                            ]
                        );

                    } catch (Exception $e) {
                        Log::error("خطأ في معالجة ربط المنتج بالـ View: " . $e->getMessage());
                    }
                }

            });

            return response()->json(['status' => 'success']);

        } catch (Exception $e) {
            Log::error("فشلت عملية المزامنة addOrUpdateOne بالكامل: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function replaceAll(Request $request)
    {
        try {
            DB::transaction(function () {
                // 1. تعطيل التحقق من العلاقات الأجنبية لتفادي أي مشاكل عند حذف البيانات
                DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

                // 2. تفريغ الجداول المرتبطة بأمان لتجهيزها للبيانات الجديدة (باستخدام delete لتوافق المعاملات والتراجع)
                ProductView::query()->delete();
                StoreProductView::query()->delete();
                ProductAddon::query()->delete();
                ProductImage::query()->delete();
                ProductOption::query()->delete();
                Product::query()->delete();
                NestedSection::query()->delete();
                Section::query()->delete();
                Category::query()->delete();

                // 3. إعادة تفعيل التحقق من العلاقات الأجنبية
                DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            });

            // 4. استدعاء دالة الإضافة والتحديث لإدخال البيانات الجديدة المرسلة في الـ Request
            return $this->addOrUpdateOne($request);

        } catch (Exception $e) {
            // في حال حدوث أي خطأ، نتأكد من تفعيل العلاقات الأجنبية مرة أخرى لتجنب تعطيل قاعدة البيانات
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            } catch (Exception $ignored) {}

            Log::error("فشلت عملية المزامنة replaceAll بالكامل: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function handleLaravelImageDownload($url, $folder, $prefix)
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        try {
            // 1. استخراج اسم الصورة الأصلي الفعلي من الرابط (مثال: burger.jpg)
            $originalFileName = basename(parse_url($url, PHP_URL_PATH));

            // إذا لم ينجح استخراج الاسم أو كان فارغاً نضع اسم افتراضي
            if (empty($originalFileName)) {
                $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $originalFileName = uniqid() . '.' . $ext;
            }

            // 2. دمج الـ prefix مع الاسم الأصلي لتمييز المجلد (مثال: cat_burger.jpg)
            $localFileName = $prefix . $originalFileName;
            $savePath = "public/{$folder}/{$localFileName}";

            // 3. التميز الذكي: إذا كانت الصورة موجودة مسبقاً بنفس الاسم، لا تحملها مجدداً!
            if (Storage::exists($savePath)) {
                return $localFileName; // نرجع الاسم مباشرة ونقفل الدالة فوراً لتوفير الوقت
            }

            // 4. إذا لم تكن موجودة، نقوم بتحميلها الآن عبر الـ Http Client
            $response = Http::get($url);

            if ($response->successful()) {
                // حفظ الملف في الـ Storage الخاص بلارافل
                Storage::put($savePath, $response->body());
                return $localFileName;
            }
        } catch (Exception $e) {
            Log::error("فشل تحميل الصورة من الرابط {$url}: " . $e->getMessage());
        }

        return null;
    }
}
