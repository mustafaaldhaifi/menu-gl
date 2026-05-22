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
    public function index()
    {

        // 1. إعداد الحماية
        $sentKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (trim($sentKey) !== "SECRET_KEY_123") {
            http_response_code(403);
            exit(json_encode(['error' => 'Unauthorized']));
        }

        // 2. استقبال البيانات والتحقق
        $input = json_decode(file_get_contents('php://input'), true);
        // if (!isset($input['products'])) {
//     http_response_code(400);
//     exit(json_encode(['error' => 'Invalid structure: "products" array missing']));
// }
        $uploadBase = __DIR__ . '/uploads/images/products';

        $strategy = $input['strategy'] ?? null; // استخدام ?? null لتجنب Warning

        if ($strategy == 'replaceAll') { // ⚠️ تصحيح: استخدام == بدلاً من =
            $this->replaceAll($uploadBase, $input);
        } elseif ($strategy == 'addOrUpdateOne' || $strategy == 'addOrUpdate') { // ⚠️ تصحيح: استخدام ==
            $this->addOrUpdateOne($input);
        } elseif ($strategy == 'delete') { // ⚠️ تصحيح: استخدام ==
            // $this->deleteStrategy($pdo, $uploadBase, $input);
        } else {
            // حالة عدم تطابق أي استراتيجية
            echo json_encode(['status' => 'error', 'message' => 'Invalid Strategy', 'strategy_received' => $strategy]);
        }
    }

    function addOrUpdateOne($input)
    {
        DB::transaction(function () use ($input) {

            foreach ($input['store_nested_sections'] as $cat) {
                try {
                    // 1. الحقول الإلزامية
                    $insertData = [
                        'id' => $cat['id'],
                    ];

                    $updateColumns = [];

                    // قائمة بجميع الحقول المتوقعة
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

                            // 🔹 هنا السحر: إذا كان الحقل هو الـ cover، نقوم بتحميل الصورة أولاً
                            if ($field === 'cover') {
                                $prefix = "cat_{$cat['id']}_";
                                // حفظ الصورة في مجلد storage/app/public/categories
                                $localImage = $this->handleLaravelImageDownload($cat['cover'], 'nested_sections', $prefix);

                                // إذا نجح التحميل نضع اسم الملف الجديد، وإذا فشل نترك الحقل كما هو أو null
                                $insertData[$field] = $localImage ?? $cat['cover'];
                            } else {
                                // الحقول العادية النصية والرقمية تُؤخذ كما هي
                                $insertData[$field] = $cat[$field];
                            }

                            // إضافة الحقل للتحديث بشرط ألا يكون تاريخ الإنشاء
                            if ($field !== 'created_at') {
                                $updateColumns[] = $field;
                            }
                        }
                    }

                    // دائماً نحدث وقت التعديل
                    $insertData['updated_at'] = now();
                    $updateColumns[] = 'updated_at';

                    // 3. تنفيذ الـ upsert الديناميكي بأمان
                    NestedSection::upsert([$insertData], ['id'], $updateColumns);

                } catch (Exception $e) {
                    $report['errors'][] = "Category ID {$cat['id']} error: " . $e->getMessage();
                }
            }
        });
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
