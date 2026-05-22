<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\NestedSection;
use App\Models\Product;
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
            // $this->replaceAll($uploadBase, $request->all());
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
        // جلب المصفوفة مباشرة من كائن الـ Request دون خوف من تفريغ البيانات
        $store_nested_sections = $request->input('payload.store_nested_sections', []);

        $report = [
            'categories_synced' => 0,
            'errors' => []
        ];

        // التحقق من أن المصفوفة ليست فارغة
        if (empty($store_nested_sections)) {
            return response()->json(['status' => 'error', 'message' => 'No sections found in payload'], 400);
        }

        try {
            DB::transaction(function () use ($store_nested_sections, &$report) {

                foreach ($store_nested_sections as $sectionWrapper) {
                    try {
                        // 🔹 معالجة التفاف كائن stdClass (مهم جداً بناءً على الـ JSON المرسل من سيرفرك)
                        $cat = isset($sectionWrapper['stdClass']) ? $sectionWrapper['stdClass'] : $sectionWrapper;

                        // 1. الحقل الإلزامي
                        $insertData = [
                            'id' => $cat['id'],
                        ];

                        $updateColumns = [];

                        // قائمة بجميع الحقول المتوقعة بنمط snake_case المطابق للـ داتابيز والـ JSON
                        $optionalFields = [
                            'name',
                            'cover',
                            'order_no',
                            'order_at',
                            'store_branch_id',
                            'is_hidden',
                            'enabled',
                            'created_at',
                            'stores_section_id'
                        ];

                        // 2. فحص الحقول ديناميكياً
                        foreach ($optionalFields as $field) {
                            if (isset($cat[$field])) {

                                // إذا كان الحقل هو الـ cover، نقوم بتحميل الصورة أولاً عبر دالة الـ Storage
                                if ($field === 'cover') {
                                    $prefix = "cat_{$cat['id']}_";
                                    $localImage = $this->handleLaravelImageDownload($cat['cover'], 'nested_sections', $prefix);

                                    $insertData[$field] = $localImage ?? $cat['cover'];
                                } else {
                                    $insertData[$field] = $cat[$field];
                                }

                                // إضافة الحقل للتحديث بشرط ألا يكون تاريخ الإنشاء لضمان استقرار الداتابيز
                                if ($field !== 'created_at') {
                                    $updateColumns[] = $field;
                                }
                            }
                        }

                        // دائماً نحدث وقت التعديل محلياً
                        $insertData['updated_at'] = now();
                        $updateColumns[] = 'updated_at';

                        // 3. تنفيذ الـ upsert الديناميكي بأمان داخل الـ Database
                        NestedSection::upsert([$insertData], ['id'], $updateColumns);

                        $report['categories_synced']++;

                    } catch (Exception $e) {
                        $report['errors'][] = "Category ID " . ($sectionWrapper['stdClass']['id'] ?? 'Unknown') . " error: " . $e->getMessage();
                    }
                }
            });

            return response()->json(['status' => 'success', 'report' => $report]);

        } catch (Exception $e) {
            Log::error("فشلت عملية المزامنة addOrUpdateOne: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // function addOrUpdateOne($request)
    // {
    //     $store_nested_sections = $request->input('payload.store_nested_sections', []);

    //     DB::transaction(function () use ($store_nested_sections) {

    //         foreach ($store_nested_sections as $cat) {
    //             try {
    //                 // 1. الحقول الإلزامية
    //                 $insertData = [
    //                     'id' => $cat['id'],
    //                 ];

    //                 $updateColumns = [];

    //                 // قائمة بجميع الحقول المتوقعة
    //                 $optionalFields = [
    //                     'name',
    //                     'cover',
    //                     'order_no',
    //                     'order_at',
    //                     'store_branch_id',
    //                     'is_hidden',
    //                     'enabled',
    //                     'created_at',
    //                     'stores_section_id'
    //                 ];

    //                 // 2. فحص الحقول ديناميكياً
    //                 foreach ($optionalFields as $field) {
    //                     if (isset($cat[$field])) {

    //                         // 🔹 هنا السحر: إذا كان الحقل هو الـ cover، نقوم بتحميل الصورة أولاً
    //                         if ($field === 'cover') {
    //                             $prefix = "cat_{$cat['id']}_";
    //                             // حفظ الصورة في مجلد storage/app/public/categories
    //                             $localImage = $this->handleLaravelImageDownload($cat['cover'], 'nested_sections', $prefix);

    //                             // إذا نجح التحميل نضع اسم الملف الجديد، وإذا فشل نترك الحقل كما هو أو null
    //                             $insertData[$field] = $localImage ?? $cat['cover'];
    //                         } else {
    //                             // الحقول العادية النصية والرقمية تُؤخذ كما هي
    //                             $insertData[$field] = $cat[$field];
    //                         }

    //                         // إضافة الحقل للتحديث بشرط ألا يكون تاريخ الإنشاء
    //                         if ($field !== 'created_at') {
    //                             $updateColumns[] = $field;
    //                         }
    //                     }
    //                 }

    //                 // دائماً نحدث وقت التعديل
    //                 $insertData['updated_at'] = now();
    //                 $updateColumns[] = 'updated_at';

    //                 // 3. تنفيذ الـ upsert الديناميكي بأمان
    //                 NestedSection::upsert([$insertData], ['id'], $updateColumns);

    //             } catch (Exception $e) {
    //                 $report['errors'][] = "Category ID {$cat['id']} error: " . $e->getMessage();
    //             }
    //         }
    //     });
    // }

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
