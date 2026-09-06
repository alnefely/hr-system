# مهارة: تطوير Laravel 13 باحترافية

هذا الملف يحدد القواعد الإلزامية التي يجب على أي AI اتباعها عند كتابة أو تعديل كود في مشروع Laravel. الهدف: كود نظيف، سريع، منظم، وسهل الصيانة.

---

## 1. التوافق مع الإصدار

- الكود يجب أن يكون متوافقًا مع **Laravel 13** وآخر ممارسات PHP (PHP 8.3+).
- استخدام الميزات الحديثة: `enum`, `readonly properties`, `named arguments`, `constructor property promotion`.
- تجنب أي أكواد أو دوال تم إهمالها (deprecated) في الإصدارات الحديثة.
- الاعتماد على Laravel Facades والـ Helpers الرسمية فقط، وتفادي الحلول اليدوية إذا كان يوجد حل جاهز من Laravel نفسه.

---

## 2. فصل الطبقات (Separation of Concerns)

القاعدة الذهبية: **الـ Controller لا يحتوي على منطق العمل (Business Logic) إطلاقًا.**

الهيكل المطلوب:

| الطبقة | المسؤولية |
|---|---|
| **Controller** | استقبال الطلب، استدعاء Service، إرجاع Response فقط |
| **Service** | كل منطق العمل (Business Logic) |
| **Repository** (اختياري حسب حجم المشروع) | التعامل مع قواعد البيانات مباشرة |
| **Form Request** | التحقق من صحة البيانات (Validation) |
| **Resource** | تنسيق شكل البيانات المُرجعة (API Response) |
| **Action Classes** (للعمليات المعقدة والمحددة) | عملية واحدة فقط بمسؤولية واحدة (Single Action) |

### مثال على الشكل الصحيح:

```php
// Controller - نظيف وخفيف
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function store(StoreOrderRequest $request): JsonResponse
    {
        // الكنترولر بيستقبل الطلب بس ويبعته للسيرفس
        $order = $this->orderService->create($request->validated());

        return response()->json(new OrderResource($order), 201);
    }
}
```

```php
// Service - هنا المنطق كله
class OrderService
{
    public function __construct(
        private readonly OrderRepository $orderRepository
    ) {}

    public function create(array $data): Order
    {
        // هنا بيتحط كل منطق العمل: حسابات، شروط، استدعاء خدمات تانية
        return DB::transaction(function () use ($data) {
            $order = $this->orderRepository->create($data);

            // مثال: تحديث المخزون بعد إنشاء الطلب
            $this->updateInventory($order);

            return $order;
        });
    }

    private function updateInventory(Order $order): void
    {
        // منطق تحديث المخزون
    }
}
```

### الفصل التنظيمي بين Controllers الويب والـ API

المشروع فيه نظامين منفصلين تمامًا: **Admin Panel** (بيرجع Views عبر Blade) و **API** (بيرجع JSON عبر `HelperClass::handleResponse()`). الفصل بينهم لازم يكون على مستوى المجلدات من البداية، مش بس في المنطق:

```
app/Http/Controllers/Admin/   → Controllers الأدمن بانل (بترجع view())
app/Http/Controllers/Api/     → Controllers الموبايل/API (بترجع HelperClass::handleResponse())
```

- **ممنوع** خلط الاتنين في نفس الـ Controller. لو نفس الـ Resource (زي Orders) محتاج يتعرض في الأدمن بانل والـ API، يتعمل **Controller منفصل لكل واحد**، وممكن الاتنين يشتركوا في نفس الـ Service (ده بالظبط الغرض من فصل المنطق في Service من الأساس).
- Controller في `Admin/` بيرجع `view(...)` أو `redirect()`.
- Controller في `Api/` بيرجع دايمًا عبر `HelperClass::handleResponse()` (راجع قسم API Responses).
- لو الـ AI مش متأكد الـ Controller الجديد تابع لأنهي نظام، يسأل بدل ما يخمّن — الفرق ده بيأثر على شكل الـ Response بالكامل.

---

## 3. الأداء ومنع مشكلة N+1

- **إلزامي**: استخدام `with()` أو `load()` عند جلب أي علاقة (Relationship) سيتم استخدامها لاحقًا.
- ممنوع استخدام Loop لجلب بيانات من علاقة بدون Eager Loading.
- استخدام `withCount()` بدل جلب العلاقة كاملة إذا كان المطلوب رقم فقط (عدد).
- استخدام `select()` لتحديد الأعمدة المطلوبة فقط بدل `SELECT *` في الاستعلامات الكبيرة.
- استخدام `chunk()` أو `lazy()` عند التعامل مع بيانات ضخمة بدل `all()` أو `get()` مباشرة.
- استخدام Database Indexing على الأعمدة المستخدمة كثيرًا في `WHERE`, `JOIN`, `ORDER BY`.

### مثال (خطأ شائع يجب تجنبه):

```php
// ❌ خطأ: N+1 problem
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->customer->name; // بيعمل Query جديد لكل Order!
}
```

```php
// ✅ صحيح
$orders = Order::with('customer')->get();
foreach ($orders as $order) {
    echo $order->customer->name; // البيانات محملة مسبقًا
}
```

---

## 4. التعليقات في الكود

- كل دالة (Method) تحتوي على تعليق قصير بالعربي يشرح وظيفتها.
- التعليقات تشرح "ليه" مش بس "إيه"، خاصة في الأجزاء اللي فيها منطق معقد.
- عدم الإفراط في التعليقات على أشياء واضحة من اسم الدالة نفسه.

```php
/**
 * حساب السعر الإجمالي للطلب بعد تطبيق الخصومات والضرائب
 */
public function calculateTotal(Order $order): float
{
    // ...
}
```

---

## 5. تسمية الأشياء (Naming Conventions)

- Controllers: `PascalCase` + لاحقة `Controller` → `OrderController`
- Services: `PascalCase` + لاحقة `Service` → `OrderService`
- Models: `PascalCase` مفرد → `Order` (مش `Orders`)
- Methods: `camelCase` وأفعال واضحة → `createOrder()`, `getActiveUsers()`
- Variables: `camelCase` وأسماء وصفية، ممنوع أسماء زي `$data`, `$temp`, `$x`
- Database tables: `snake_case` جمع → `orders`, `order_items`

---

## 6. Validation

- كل عملية إدخال بيانات لازم يكون ليها **Form Request** خاص بيها، مش Validation جوه الـ Controller.

```php
class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'items'       => ['required', 'array', 'min:1'],
        ];
    }

    // رسائل الخطأ لازم تمر عبر ملفات الترجمة، مش نص عربي مباشر هنا
    public function messages(): array
    {
        return [
            'customer_id.required' => __('validation.custom.customer_id.required'),
            'items.min'            => __('validation.custom.items.min'),
        ];
    }
}
```

---

## 7. الأحداث الجانبية (Events & Listeners)

أي عملية "جانبية" بتحصل نتيجة لعملية أساسية — يعني مش هي جوهر الطلب نفسه لكنها بتترتب عليه — **لازم** تتفصل في Event و Listener منفصلين، مش تتحشر جوه الـ Service. أمثلة على عمليات جانبية: إرسال إشعار بعد إنشاء طلب، إرسال إيميل ترحيب بعد تسجيل أدمن جديد، تسجيل نشاط (Activity Log) بعد أي تعديل حساس.

### القاعدة الفاصلة بين Service و Event

| السؤال | لو الإجابة "أيوه" |
|---|---|
| هل العملية دي جزء أساسي من نجاح الطلب؟ (لو فشلت، الطلب كله يفشل) | تفضل جوه الـ Service |
| هل العملية دي نتيجة جانبية مش بتأثر على نجاح الطلب الأساسي؟ (لو فشلت، الطلب الأساسي يفضل ناجح) | تتحول لـ Event/Listener |

### مثال:

```php
// Service - يطلق Event بعد إتمام العملية الأساسية بنجاح
class OrderService
{
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create($data);

            // إطلاق حدث بعد إنشاء الطلب بنجاح
            event(new OrderCreated($order));

            return $order;
        });
    }
}
```

```php
// Listener - كل عملية جانبية في Listener منفصل بمسؤولية واحدة
class SendOrderConfirmationNotification
{
    public function handle(OrderCreated $event): void
    {
        // إرسال إشعار للعميل، مش جزء من نجاح إنشاء الطلب نفسه
        $event->order->customer->notify(new OrderConfirmedNotification($event->order));
    }
}
```

- **إلزامي**: كل Listener بيعمل حاجة بطيئة أو غير حرجة (إرسال إيميل، إشعار، استدعاء API خارجي) يُنفَّذ عبر `ShouldQueue` عشان ميأخرش استجابة الطلب الأساسي للمستخدم:

```php
class SendOrderConfirmationNotification implements ShouldQueue
{
    // ...
}
```

- تسمية الـ Events بصيغة الماضي (`OrderCreated`, `AdminPermissionUpdated`) لأنها بتوصف حاجة حصلت بالفعل.
- تسمية الـ Listeners بفعل واضح لما بيعمله (`SendOrderConfirmationNotification`, `ClearRoleCache`) مش أسماء عامة زي `Handler` أو `Listener1`.
- ممنوع استدعاء أكتر من Listener منطقي مختلف جوه Listener واحد؛ كل Listener مسؤولية واحدة بس (نفس مبدأ Action Classes في قسم فصل الطبقات).

---

## 8. التعامل مع الأخطاء وتوحيد استجابات الـ API

- استخدام Custom Exceptions للحالات الخاصة بالمشروع بدل `throw new Exception()` العام.
- استخدام `try/catch` فقط عند الحاجة الفعلية للتعامل مع الخطأ، مش لإخفائه.
- تسجيل الأخطاء المهمة عبر `Log::error()` مع بيانات كافية للتتبع.

### توحيد شكل استجابة الـ API

**قاعدة إلزامية**: أي Response بيرجع من API لازم يمر عبر `HelperClass::handleResponse()` بدل `response()->json()` مباشرة، عشان يضمن شكل موحّد في كل نقاط النهاية (Endpoints):

```php
namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;

class HelperClass
{
    public static function handleResponse(
        bool $success = true,
        mixed $data = null,
        ?string $message = null,
        mixed $errors = null,
        int $statusCode = 200,
        bool $returnArray = false
    ): JsonResponse {
        // تحويل MessageBag (رسائل الـ Validation) لـ array عادي
        if ($errors instanceof MessageBag) {
            $errors = $errors->all();
        }

        $errors = is_string($errors) ? [$errors] : (array) $errors;

        // لو فيه أخطاء، الاستجابة تتحول تلقائيًا لفشل بكود مناسب
        if (!empty($errors)) {
            $statusCode = ($statusCode === 200) ? 422 : $statusCode;
            $success    = false;
            $message    = $errors[0];
            $errors     = array_values($errors);
        }

        return response()->json([
            'success' => $success,
            'message' => $message,
            'errors'  => $errors,
            'data'    => $data,
        ], $statusCode);
    }
}
```

### الاستخدام في الـ Controller

```php
// حالة نجاح
return HelperClass::handleResponse(
    data: new OrderResource($order),
    message: __('orders.created_successfully'),
    statusCode: 201
);

// حالة فشل (خطأ عادي)
return HelperClass::handleResponse(
    success: false,
    message: __('orders.not_found'),
    statusCode: 404
);

// حالة فشل Validation (تمرير MessageBag مباشرة)
return HelperClass::handleResponse(
    errors: $validator->errors()
);
```

نقاط أساسية:
- **ممنوع** كتابة `response()->json([...])` يدويًا في أي Controller أو Service خاص بالـ API؛ كل الاستجابات تمر عبر `handleResponse()` للحفاظ على شكل موحّد (`success`, `message`, `errors`, `data`) في كل المشروع.
- تمرير `message` دايمًا عبر ملفات الترجمة (`__('...')`) وليس نص مباشر، حتى في هذا الهيلبر.
- لو الخطأ ناتج عن Validation، تمرير الـ `MessageBag` كامل لـ `errors` بدل استخراج أول رسالة يدويًا، والهيلبر بيتولى التحويل والتنسيق تلقائيًا.

### توحيد شكل الـ Pagination

أي استجابة API بترجع بيانات مقسّمة على صفحات (Pagination) تمر عبر `HelperClass::CustomPagination()` بدل إرجاع نتيجة `paginate()` الافتراضية من Laravel مباشرة، لتوحيد شكل بيانات الصفحات بين كل الـ Endpoints:

```php
public function index(Request $request)
{
    $orders = Order::with('customer')->paginate(15);

    return HelperClass::handleResponse(
        data: HelperClass::CustomPagination($orders, 'orders')
    );
}
```

### Helper مساعدة أخرى

- `HelperClass::imgUrl()`: تُستخدم دايمًا عند إرجاع رابط صورة/ملف في أي API Response، بدل بناء الرابط يدويًا في كل مرة (`url($path)`)، لضمان التعامل الموحّد مع القيم الفارغة (`null`).

---

## 9. حماية الـ APIs (Middlewares + Rate Limiting)

### التحقق من مفتاح الـ API

كل الـ API Routes الخاصة بالتطبيقات الخارجية (الموبايل مثلاً) لازم تكون محمية بـ Middleware `VerifyApiKey`، اللي بيتحقق من هيدر `X-API-KEY` مقابل القيمة المخزنة في `config('services.mobile_key')`:

```php
Route::middleware('VerifyApiKey')->group(function () {
    // كل الـ Routes الخاصة بالموبايل هنا
});
```

- أي Route API جديد **لازم** يتحط جوه هذا الـ group من الأول، مش بعد التسليم.
- عند الرفض، الاستجابة لازم تمر عبر `HelperClass::handleResponse()` بنفس الشكل الموحّد (زي باقي استجابات الـ API)، مش `abort()` أو `response()` مباشرة.
- مفتاح الـ API بيُقرأ دايمًا من ملف `config`، **ممنوع** كتابته مباشرة (Hardcoded) في الكود.

### Rate Limiting

النظام معرّف ليه Limiters منفصلة للويب والـ API في `AppServiceProvider`:

```php
protected function configureRateLimiting(): void
{
    RateLimiter::for('WebRateLimiter', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip());
    });

    RateLimiter::for('ApiRateLimiter', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip())
            ->response(fn () => response()->json([
                'message' => __('api.too_many_requests'),
            ], 429));
    });
}
```

- كل Route جديد (ويب أو API) **لازم** يكون تحت الـ Limiter المناسب (`throttle:WebRateLimiter` أو `throttle:ApiRateLimiter`)، مش بدون حماية.
- رسالة تجاوز الحد (429) لازم تمر عبر ملف الترجمة (`__('api.too_many_requests')`) زي أي نص ثابت تاني في المشروع، مش نص عربي مكتوب مباشرة جوه الـ Provider.
- لو Endpoint معين حساس بشكل خاص (زي تسجيل الدخول أو إعادة تعيين كلمة المرور)، يُفضّل عمل Limiter مخصص بحد أقل (مثال: 5 محاولات/دقيقة) بدل استخدام الـ Limiter العام.

---

## 10. رفع وتخزين الملفات (File Storage Scoping)

المشروع بيستخدم مكتبة LFM (Laravel File Manager) للتعامل مع رفع الملفات، مع طبقة تخصيص إضافية بتفصل ملفات كل Admin عن التاني:

```php
class LfmConfigHandler
{
    public function userField()
    {
        if (auth('admin')->check()) {
            $admin = auth('admin')->user();

            // الأدمن الرئيسي (main) بيشتغل على المجلد الجذري بدون عزل
            if ($admin->main) {
                return '';
            }

            // أي أدمن تاني بيتحدد له مجلد خاص بيه فقط
            return 'admins/'.auth('admin')->id();
        }
    }
}
```

- **قاعدة إلزامية**: أي ملف بيتم رفعه من خلال أي أدمن (غير الرئيسي) لازم يُخزَّن جوه مجلد مخصص باسم `admins/{id}` بدل مجلد عام مشترك، لمنع أي أدمن من الوصول لملفات أدمن تاني عن طريق الخطأ.
- عند بناء أي منطق رفع ملفات جديد بره LFM (Service مخصص مثلاً)، يُتبع نفس المبدأ: تحديد مسار التخزين بناءً على هوية المستخدم صاحب الملف، مش مسار ثابت واحد للكل.
- عند حذف أو تحديث ملف قديم (زي تغيير صورة شخصية)، **لازم** حذف الملف القديم من التخزين (`Storage::delete()`) قبل أو بعد حفظ الجديد مباشرة، لتفادي تراكم ملفات "يتيمة" (orphan files) على السيرفر.
- روابط الملفات المُرجعة في أي API Response تمر عبر `HelperClass::imgUrl()` دايمًا (راجع قسم API Responses)، مش بناء الرابط يدويًا.

---

## 11. قاعدة البيانات

- كل Migration واضح ومقسم، وله اسم يوضح الغرض منه.
- استخدام `foreignId()->constrained()` بدل كتابة العلاقة يدويًا.
- عدم حذف أعمدة أو تعديل جداول Production بدون Migration جديد (لا تعديل مباشر).
- استخدام Seeders و Factories للبيانات التجريبية بدل إدخالها يدويًا.

---

## 12. الأمان (Security)

- استخدام `$fillable` في كل Model لتحديد الحقول المسموح تعبئتها عبر Mass Assignment (هو الأساس المعتمد بالمشروع، وليس `$guarded`).
- عند استخدام `$request->all()` مع `create()` أو `update()`، التأكد إن `$fillable` بالموديل **لا يحتوي** على حقول حساسة (زي `role_id`) إلا لو الـ Form Request بيتحقق صراحة من صلاحية المستخدم لتعديلها.
- عدم كتابة أي Query يدوي (Raw SQL) بدون Binding Parameters (تفاديًا لـ SQL Injection).
- التوثيق (Authentication) في الأدمن بانل بيتم دايمًا عبر Guard مخصص باسم `admin` (`auth('admin')`)، **مش** الـ Guard الافتراضي (`auth()`). أي كود جديد خاص بالأدمن بانل لازم يستخدم `auth('admin')` صراحة.
- النظام مبني بحيث يسمح بإضافة Guards تانية مستقبلًا (زي guard خاص بالعملاء أو بواجهة تانية)؛ لو تم إضافة Guard جديد، يُتبع نفس نمط التسمية الصريحة (`auth('customer')` مثلاً) بدل الاعتماد على الـ Guard الافتراضي في أي مكان بالمشروع.
- المشروع عنده نظام صلاحيات مخصص (Custom Permissions System) عبر `app/Helpers/AdminRoles.php`، ولازم يتم الاعتماد عليه حصريًا، **مش** Laravel Policies/Gates.
- كل صلاحية جديدة تُضاف داخل دالة `Roles()` في نفس الملف، بنفس الشكل الموجود (name + roles array):

```php
[
    'name'  => 'trans.admin.title',
    'roles' => [
        'admin_show',
        'admin_create',
        'admin_update',
        'admin_delete',
    ],
],
```

- تسمية الصلاحيات تتبع نمط `{module}_{action}` (مثال: `admin_show`, `admin_create`, `setting_clear_cache`) للحفاظ على الاتساق، ولتفعيل التعرف التلقائي على نوع العملية عبر `checkPermissionAction()`.

### الفرق بين `admin_can()` والـ Middleware — استخدام كل واحد في مكانه الصح

النظام فيه **طبقتين** للتحقق من الصلاحية، ولازم يُستخدما في السياق الصحيح مش أي واحدة مكان التانية:

| الطريقة | مكان الاستخدام | مثال |
|---|---|---|
| `admin_can('permission')` | جوه الكود (Blade، Controller، Service) للتحقق الشرطي أو إخفاء/إظهار عناصر | `@if(admin_can('admin_delete'))` |
| `AdminPermission` Middleware | على مستوى الـ Route نفسه، لمنع الوصول للـ Endpoint بالكامل | `Route::middleware('permission:admin_show')` |

```php
// حماية Route كامل بصلاحية معينة
Route::middleware(['auth:admin', 'permission:admin_show'])
    ->get('/admins', [AdminController::class, 'index']);

// حالة خاصة: السماح لطلبات الـ ajax بدون فحص صلاحية معينة
Route::middleware(['auth:admin', 'permission:ajax'])
    ->get('/admins/datatable', [AdminController::class, 'index']);
```

- أي Route جديد يتعامل مع بيانات محمية **لازم** يتحط عليه `AdminPermission` Middleware من الأول، مش الاعتماد على `admin_can()` جوه الكونترولر بس (لتفادي إمكانية الوصول للـ Endpoint حتى لو الواجهة مخفية).
- الـ Middleware بيتحقق أولاً من تسجيل الدخول (`auth('admin')`)، فلو الأدمن مش مسجل بيتم تحويله لصفحة اللوجين تلقائيًا قبل حتى فحص الصلاحية.

### مصدر الصلاحيات والكاش

- الصلاحيات الفعلية الخاصة بكل Role مخزنة في قاعدة البيانات (`Role::permissions`)، وتُجلب عبر `AdminPermissions()` باستخدام نفس نمط الـ Static Caching (راجع قسم الكاش):

```php
function AdminPermissions(): array
{
    $roleId = Admin()->role_id;
    return cache()->rememberForever("role-$roleId", function () use ($roleId) {
        return Role::find($roleId)?->permissions ?? [];
    });
}
```

- **إلزامي**: أي تعديل على صلاحيات Role (إضافة/حذف) لازم يمسح الكاش الخاص بيه (`cache()->forget("role-{$roleId}")`) فورًا بعد الحفظ، بنفس مبدأ `booted()` المستخدم في Settings.
- دالة `permissions()` بترجع كل الصلاحيات المتاحة في النظام (مجمّعة من `Roles()`) وتُستخدم عادة في شاشة إدارة الأدوار (Roles Management) لعرض كل الصلاحيات القابلة للتفعيل، وليس للتحقق الفعلي من صلاحية مستخدم.
- ممنوع كتابة شروط صلاحيات يدوية (زي `if ($user->role === 'admin')`)؛ لازم يتم عمل صلاحية جديدة في `AdminRoles.php` واستخدام `admin_can()` أو الـ Middleware بدلها، حتى لو كانت الصلاحية بسيطة.

---

## 13. الكاش (Caching)

الكاش أداة قوية لكن استخدامها الخاطئ بيسبب مشاكل (بيانات قديمة تتعرض للمستخدم). القواعد:

- **يُكاش**: بيانات شبه ثابتة أو بطيئة التغيير (إعدادات النظام، قوائم التصنيفات، صلاحيات المستخدم، نتائج تقارير ثقيلة).
- **لا يُكاش**: بيانات بتتغير باستمرار (أرقام لحظية، حالة الطلبات، إشعارات).
- استخدام `Cache::remember()` بدل `Cache::get()` + `Cache::put()` منفصلين.
- كل مفتاح Cache لازم يكون له اسم واضح ومنظم، ويُفضّل عمل دالة مركزية لتوليد أسماء المفاتيح لتفادي التعارض:

```php
// مثال: تكاش نتيجة استعلام تقيل لمدة ساعة
public function getActiveCategories(): Collection
{
    return Cache::remember('categories.active', now()->addHour(), function () {
        return Category::where('is_active', true)->get();
    });
}
```

- **إلزامي**: مسح الكاش (Cache::forget أو Cache Tags) فور أي تعديل/إضافة/حذف على البيانات المرتبطة به، عشان نتفادى عرض بيانات قديمة للمستخدم.
- لو المشروع بيستخدم Redis، الأفضل استخدام Cache Tags لتسهيل مسح مجموعة كاملة من الكاش دفعة واحدة بدل مفاتيح متفرقة.
- تفادي كاش أي بيانات خاصة بمستخدم معين تحت مفتاح عام (زي كاش سلة مشتريات بمفتاح ثابت)، لازم يكون المفتاح مرتبط بالـ user id.

### نمط Static Model Caching (للبيانات شبه الثابتة زي Settings)

للـ Models اللي بياناتها شبه ثابتة وبتتقرأ كتير جدًا (زي إعدادات النظام)، النمط المعتمد بالمشروع هو كاش على مستوى الـ Model نفسه عبر `static` methods، مش كاش متفرق في كل Controller:

```php
protected static function booted(): void
{
    // مسح الكاش تلقائيًا عند أي حفظ أو حذف
    static::saved(function () {
        static::clearCache();
    });

    static::deleted(function () {
        static::clearCache();
    });
}

public static function get(string $key, mixed $default = null): mixed
{
    return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
        return static::where('key', $key)->first()?->value ?? $default;
    });
}

public static function clearCache(): void
{
    // مسح كل المفاتيح المرتبطة، مش مسح كامل عشوائي
    $keys = static::whereNotNull('key')->pluck('key');
    foreach ($keys as $key) {
        Cache::forget("setting_{$key}");
    }
}
```

نقاط أساسية في هذا النمط:
- استخدام `booted()` مع أحداث `saved` و `deleted` لمسح الكاش تلقائيًا، بدل الاعتماد على مسحه يدويًا من كل مكان بيعدل البيانات.
- استخدام `Cache::rememberForever()` للبيانات الثابتة فعليًا (زي الإعدادات) بدل مدة زمنية محدودة، طالما المسح بيحصل تلقائيًا عند التعديل.
- عند تخزين Eloquent Collection في الكاش، يُفضّل تحويلها لـ `->toArray()` قبل التخزين وإعادة بنائها بـ `forceFill()` بعد الاسترجاع، لتفادي مشاكل تسلسل (serialization) الـ Model الكامل.
- عمل Helper function عامة (زي `setting()`) بترجع للـ static method، عشان الاستخدام في أي مكان يبقى بسيط وقصير بدل استدعاء الـ Model كامل كل مرة:

```php
if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}
```

---

## 14. الترجمة (Localization) — عدم كتابة النصوص مباشرة

**قاعدة إلزامية**: أي نص ثابت ظاهر للمستخدم (عنوان، رسالة، زرار، Validation Message) **ممنوع** يتكتب كنص عادي جوه الكود أو الـ Blade، ولازم يمر عبر ملفات الترجمة من أول يوم في المشروع، مش تعديل لاحق.

### الطريقة الصحيحة:

```
resources/lang/ar/orders.php
resources/lang/en/orders.php
```

```php
// resources/lang/ar/orders.php
return [
    'created_successfully' => 'تم إنشاء الطلب بنجاح',
    'not_found'             => 'الطلب غير موجود',
];
```

### الاستخدام في الكود:

```php
// ❌ خطأ
return response()->json(['message' => 'تم إنشاء الطلب بنجاح']);

// ✅ صحيح
return response()->json(['message' => __('orders.created_successfully')]);
```

```blade
{{-- ❌ خطأ --}}
<button>حفظ</button>

{{-- ✅ صحيح --}}
<button>{{ __('common.save') }}</button>
```

### تنظيم ملفات الترجمة:

- تقسيم الملفات حسب الموضوع مش ملف واحد ضخم: `orders.php`, `auth.php`, `validation.php`, `common.php`.
- استخدام مفاتيح واضحة (`orders.created_successfully`) مش نصوص عربي كمفاتيح (`__('تم الإنشاء')`) عشان يسهل الصيانة ويدعم أي لغة تانية بسهولة مستقبلًا.
- في Form Requests، استخدام `messages()` مع مفاتيح ترجمة بدل نصوص مباشرة.
- في الـ Frontend (لو فيه JS منفصل زي Vue/React جوه Vuexy)، لازم نظام ترجمة موازي (زي i18n) بنفس المبدأ، وعدم كتابة أي نص ثابت جوه ملفات JS/Vue مباشرة.

---

## 15. المكونات المشتركة (Reusable Blade Components)

المشروع عنده مجلد مخصص لتجميع أي عنصر واجهة هيتكرر استخدامه أكتر من مرة، تحت:

```
resources/views/components/
```

مقسّم حسب النوع، مثال:

```
resources/views/components/dataTable/table.blade.php
resources/views/components/inputs/field.blade.php
resources/views/components/inputs/lfm.blade.php
```

### القاعدة الإلزامية

- **قبل كتابة أي جزء HTML/Blade جديد**، لازم يتفحص هل موجود Component مشابه بالفعل في `resources/views/components` واستخدامه بدل تكرار الكود.
- أي عنصر (input, table, modal, card ...) هيتكرر في أكتر من صفحة، يتحول لـ Component جديد في المجلد المناسب من أول مرة، مش بعد التكرار الثالث أو الرابع.
- الـ Components تُبنى بحيث تستقبل بيانات عبر Props واضحة، مش تعتمد على متغيرات عامة (Global variables) بره نطاقها.

### مثال: استخدام DataTable الموحّد

الجداول اللي بتُعرض بيانات (Admins, Orders, ...) تعتمد على مكتبة `yajra/laravel-datatables` مع نمط ثابت في الـ Controller:

```php
public function index(Request $request)
{
    if ($request->ajax()) {
        // لازم Eager Loading للعلاقات المستخدمة في الجدول لتفادي N+1
        $admins = Admin::with('role:id,name');
        return datatables($admins)->make(true);
    }

    $roles = $this->roles();
    return view('admin.admins.index', compact('roles'));
}
```

نقاط مهمة عند استخدام هذا النمط:
- استعلام الـ Model الأساسي في `index()` يُمرَّر مباشرة لـ `datatables()` بدون `->get()`، لأن المكتبة بتتولى الـ Pagination والـ Filtering بنفسها.
- أي علاقة (Relation) هيتم عرضها في أعمدة الجدول لازم تكون موجودة في `with()` من البداية (تفاديًا لـ N+1).
- تحديد الأعمدة المطلوبة فقط من العلاقة (زي `role:id,name`) بدل جلب كل أعمدة الجدول المرتبط.
- استخدام Blade Component الموحّد لعرض الجدول (`dataTable/table.blade.php`) بدل كتابة جدول HTML يدوي في كل صفحة.

---

## 16. استخدام قالب واجهة جاهز (مثل Vuexy)

عند دمج قالب لوحة تحكم جاهز (Vuexy أو غيره) داخل المشروع:

- **تأكيد التوافق أولاً**: التأكد إن نسخة القالب (خصوصًا لو فيه نسخة Laravel رسمية منه) متوافقة مع Laravel 13 و PHP 8.3+ قبل الاعتماد عليها.
- **عدم استخدام HTML الخام كما هو**: يتم تقسيم القالب لـ Blade Components و Layouts قابلة لإعادة الاستخدام، وتُوضع في نفس مكان المكونات المشتركة `resources/views/components/` (راجع قسم المكونات المشتركة)، مش في مجلد منفصل خاص بالقالب. مثال: عناصر التصميم العامة (Sidebar, Navbar, Cards) تتحول لمكونات زي `<x-layouts.sidebar>` أو `<x-components.card>` بنفس نمط باقي مكونات المشروع.
- **تنظيف الـ Assets**: القوالب الجاهزة غالبًا بتحتوي على مكتبات ومكونات كتير مش هيتم استخدامها كلها؛ يُفضّل الاحتفاظ فقط بالـ CSS/JS المُستخدم فعليًا لتقليل حجم التحميل.
- **فصل التصميم عن البيانات**: الجزء الخاص بالتصميم (الألوان، الأيقونات، الـ Layout) يبقى منفصل تمامًا عن أي منطق أو بيانات، ويُفضّل تمرير البيانات للـ View عبر متغيرات واضحة أو View Composers بدل خلط الاستعلامات جوه ملفات الـ Blade.
- **تطبيق نظام الترجمة على القالب أيضًا**: أي نصوص جاهزة داخل القالب (زي "Dashboard", "Settings") يتم تحويلها لملفات ترجمة فور دمجها، مش تُترك كما هي.
- الحذر من تعارض مكتبات الـ CSS/JS بتاعة القالب مع أي أدوات تانية مستخدمة في المشروع (Tailwind, Alpine.js, إلخ).

---

## 17. اختبار الكود (Testing)

- كل Service جديد يفضل أن يكون له Unit Test أو Feature Test يغطي الحالة الأساسية والحالات الحدّية (edge cases).
- استخدام Laravel Factories في الاختبارات بدل بيانات ثابتة يدويًا.

---

### قاعدة: ملف ترجمة مستقل لكل موديول

**إلزامي**: أي موديول/فيتشر جديد يتضاف للمشروع لازم يتعمل له **ملف ترجمة خاص بيه** بنفس اسم الموديول، في اللغتين، من لحظة إنشائه — مش يتحشر في ملف عام زي `common.php` أو `messages.php`.

```
lang/ar/admin.php     lang/en/admin.php
lang/ar/role.php      lang/en/role.php
lang/ar/branch.php    lang/en/branch.php
lang/ar/order.php     lang/en/order.php
```

القواعد:

- اسم الملف = اسم الموديول بـ `snake_case` **مفرد** (زي اسم الموديل): `branch.php` مش `branches.php`.
- الملف بيحتوي على **كل** نصوص الموديول: العناوين، الأزرار، رسائل النجاح والفشل، أسماء الحقول، ورسائل الـ Validation الخاصة بيه.
- `common.php` مخصص **فقط** للنصوص المشتركة اللي بتتكرر في كل الموديولات (حفظ، إلغاء، حذف، بحث، تعديل)، وممنوع تتحط فيه نصوص خاصة بموديول معين.
- ممنوع استدعاء مفتاح ترجمة من ملف موديول تاني (مثال: استخدام `__('admin.created')` جوه موديول Branch)؛ كل موديول مكتفي بملفه أو بـ `common.php`.

الشكل المتفق عليه لمفاتيح كل ملف موديول:

```php
// lang/ar/branch.php
return [
    'title'                => 'الفروع',
    'singular'             => 'فرع',
    'fields'  => [
        'name'    => 'اسم الفرع',
        'address' => 'العنوان',
    ],
    'messages' => [
        'created' => 'تم إضافة الفرع بنجاح',
        'updated' => 'تم تعديل الفرع بنجاح',
        'deleted' => 'تم حذف الفرع بنجاح',
        'not_found' => 'الفرع غير موجود',
    ],
];
```

- **الـ AI ممنوع** يسلّم أي موديول جديد من غير ملف الترجمة بتاعه في اللغتين (`ar` و `en`) مكتمل، حتى لو الملف الإنجليزي مجرد ترجمة مبدئية.

## ملخص القواعد الأساسية (Checklist سريع)

### القواعد الحرجة (شيك عليها أولاً)

- [ ] لا يوجد منطق عمل داخل الـ Controller (المنطق كله في Service)
- [ ] تم استخدام `with()` لتفادي N+1
- [ ] الصلاحيات عبر `admin_can()` أو `AdminPermission` Middleware، مش شروط يدوية
- [ ] كل استجابات الـ API عبر `HelperClass::handleResponse()`
- [ ] كل API Route جديد محمي بـ `VerifyApiKey` و Rate Limiter مناسب
- [ ] `$fillable` محدد في كل Model، مع الحذر من الحقول الحساسة عند استخدام `$request->all()`

### باقي القواعد

- [ ] الكود متوافق مع Laravel 13 و PHP 8.3+
- [ ] Controllers الأدمن بانل و API في مجلدات منفصلة (`Admin/` و `Api/`)
- [ ] كل دالة لها تعليق بالعربي يوضح وظيفتها
- [ ] التسمية واضحة ومتبعة للـ Conventions
- [ ] Validation عبر Form Request مع رسائل مترجمة في `messages()`
- [ ] العمليات الجانبية (إشعارات، إيميلات) عبر Events/Listeners مع `ShouldQueue`، مش جوه الـ Service
- [ ] `auth('admin')` مستخدم صراحة بدل الـ Guard الافتراضي
- [ ] Pagination عبر `CustomPagination()`
- [ ] ملفات كل أدمن (غير الرئيسي) مخزنة في مجلد مخصص `admins/{id}`، والملف القديم يتحذف عند التحديث
- [ ] Migrations منظمة وواضحة
- [ ] لا توجد نصوص مكتوبة مباشرة، كل النصوص عبر ملفات الترجمة (`__()`)
- [ ] الكاش مُستخدم فقط في البيانات المناسبة، ويُمسح تلقائيًا عبر `booted()` عند التعديل
- [ ] تم فحص `resources/views/components` قبل كتابة أي HTML متكرر
- [ ] الجداول تعتمد على نمط DataTable الموحّد مع Eager Loading
- [ ] عناصر القالب (Vuexy) محوّلة لمكونات جوه `resources/views/components`
- [ ] كل موديول جديد له ملف ترجمة مستقل باسمه في `ar` و `en`، ومفيش نصوص خاصة بيه في `common.php`