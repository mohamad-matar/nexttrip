# 7-2-2 اختبار الصندوق الأبيض (White Box Testing)

## المقدمة

تم تطبيق اختبارات الصندوق الأبيض على وحدات باك اند النظام (NextTrip Backend) باستخدام إطار عمل **PHPUnit 12** المدمج في إطار عمل **Laravel 13**. تستهدف هذه الاختبارات داخليات الكود الأعلى مثل: المسارات البرمجية، شروط التحقق، سياسات الصلاحيات، وyatrigات المراقبة (Observers).

تُنفَّذ الاختبارات على قاعدة بيانات **SQLite in-memory** لضمان السرعة والعزل بين كل اختبار، مع استخدام سمة `RefreshDatabase` لإعادة إنشاء هيكل قاعدة البيانات تلقائياً بعد كل اختبار.

---

## أولاً: وحدات الاختبار المُنشأة

تم إنشاء ملفين رئيسيين للاختبارات وسبع ملفات factory لتوليد بيانات الاختبار:

### الملفات والمكونات

| الملف | الموقع | عدد الاختبارات |
|---|---|---|
| `AuthTest.php` | `tests/Feature/` | 18 اختبار |
| `GuideBookingTest.php` | `tests/Feature/` | 28 اختبار |
| `UserFactory.php` | `database/factories/` | بيانات مستخدمين |
| `GuideFactory.php` | `database/factories/` | بيانات مرشدين |
| `GuideBookingFactory.php` | `database/factories/` | بيانات حجوزات |
| `LanguageFactory.php` | `database/factories/` | بيانات لغات |
| `TripFactory.php` | `database/factories/` | بيانات رحلات |
| `BookingReviewFactory.php` | `database/factories/` | بيانات مراجعات |
| `GuideBookingLogFactory.php` | `database/factories/` | بيانات سجلات الحالة |

**الإجمالي: 46 اختبار / 101 تصريح / جميعها ناجحة**

---

## ثانياً: اختبار وحدة المصادقة (AuthTest)

تُختبر الوحدة المكونة من `AuthController` و `AuthService` و `RegisterRequest` و `LoginRequest`.

### 2.1 اختبارات التسجيل (Register)

| م | اسم الاختبار | الحالة المتوقعة | الكود المُختبر | التحقق |
|---|---|---|---|---|
| 1 | tourist_can_register | 201 Created | `POST /api/register` مع `role=tourist` | إنشاء مستخدم في جدول users مع الدور الصحيح + إرجاع token |
| 2 | guide_can_register_with_avatar | 201 Created | `POST /api/register` مع `role=guide` + صورة | إنشاء مستخدم في users + سجل في guides مع بيانات المرشد |
| 3 | guide_register_without_avatar_fails | 500 (Bug مكتشف) | `POST /api/register` مع `role=guide` بدون صورة | يكشف خطأ `basename(null)` في `AuthService::register()` |
| 4 | register_with_invalid_email | 422 | `POST /api/register` مع email غير صحيح | التحقق من خطأ التحقق `email` |
| 5 | register_with_duplicate_email | 422 | `POST /api/register` مع email مكرر | التحقق من `unique:users,email` |
| 6 | register_with_short_password | 422 | `POST /api/register` مع كلمة سر أقل من 6 حروف | التحقق من `min:6` |
| 7 | register_with_mismatched_password | 422 | كلمة المرور وتأكيد كلمة المرور مختلفان | التحقق من قاعدة `confirmed` |
| 8 | guide_missing_required_fields | 422 | تسجيل مرشد بدون gender, phone, DOB, daily_price, bio | التحقق من `required_if:role,guide` |
| 9 | register_returns_token | 201 | تسجيل ناجح | التحقق من وجود `data.token` في الاستجابة |

**المسار البرمجي المُختبر في `AuthService::register()`:**
```
┌─ register(data)
│  ├─ DB::transaction
│  │  ├─ User::create() ← [المسار الأساسي]
│  │  ├─ if role == Guide
│  │  │  ├─ isset(avatar) → store avatar ← [المسار مع صورة]
│  │  │  └─ avatar == null → basename(null) ← [المسار بدون صورة - BUG]
│  │  │  └─ Guide::create()
│  │  │  └─ if languages → attach()
│  │  └─ createToken() ← [المسار الأساسي]
│  └─ return ['user', 'token']
```

### 2.2 اختبارات تسجيل الدخول (Login)

| م | اسم الاختبار | الحالة المتوقعة | الكود المُختبر | التحقق |
|---|---|---|---|---|
| 10 | user_can_login | 200 OK | بيانات صحيحة | إرجاع token + بيانات المستخدم |
| 11 | login_with_wrong_password | 422 | كلمة سر خاطئة | رمي `ValidationException` |
| 12 | login_with_nonexistent_email | 422 | email غير موجود | رمي `ValidationException` |
| 13 | blocked_user_cannot_login | 422 | مستخدم محظور (`status=blocked`) | منع الدخول مع رسالة خطأ |
| 14 | closed_user_cannot_login | 422 | مستخدم مغلق (`status=closed`) | منع الدخول مع رسالة خطأ |

**المسار البرمجي المُختبر في `AuthService::login()`:**
```
┌─ login(data)
│  ├─ User::where('email') ← [بحث المستخدم]
│  ├─ if status == Blocked || Closed
│  │  └─ throw ValidationException ← [المسار 1: حساب محظور/مغلق]
│  ├─ if !user || !Hash::check()
│  │  └─ throw ValidationException ← [المسار 2: بيانات خاطئة]
│  └─ createToken() ← [المسار 3: نجاح]
```

### 2.3 اختبارات تسجيل الخروج (Logout) و جلب المعلومات (Me)

| م | اسم الاختبار | الحالة المتوقعة | الكود المُختبر | التحقق |
|---|---|---|---|---|
| 15 | user_can_logout | 200 OK | `POST /api/logout` مع token | حذف Access Token الحالي |
| 16 | unauthenticated_user_cannot_logout | 401 | `POST /api/logout` بدون token | رفض الطلب |
| 17 | user_can_get_me | 200 OK | `GET /api/me` مع token | إرجاع بيانات المستخدم الحالي |
| 18 | unauthenticated_user_cannot_get_me | 401 | `GET /api/me` بدون token | رفض الطلب |

---

## ثالثاً: اختبار وحدة حجوزات المرشدين (GuideBookingTest)

تُختبر الوحدة المكونة من `TouristGuideBookingController` و `Guide\BookingController` و `GuideBookingPolicy` و `GuideBookingObserver` و `CheckGuideAvailability`.

### 3.1 حجز المرشد من قبل السائح (Tourist → Book)

| م | اسم الاختبار | الحالة المتوقعة | الكود المُختبر | التحقق |
|---|---|---|---|---|
| 1 | tourist_can_book_guide | 201 | حجز ببيانات صحيحة | إنشاء سجل في guide_bookings مع الحالة Pending |
| 2 | booking_calculates_total_price | 201 | حجز 4 أيام × سعر 50 | التحقق من total_price = 200.00 في قاعدة البيانات |
| 3 | tourist_cannot_book_unavailable_guide | 422 | حجز مرشد غير متاح (`status=unavailable`) | رفض الحجز |
| 4 | tourist_cannot_book_guide_with_past_date | 422 | تاريخ بدء في الماضي | التحقق من خطأ `start_date` |
| 5 | tourist_cannot_book_guide_with_missing_fields | 422 | حقول مفقودة | التحقق من أخطاء `start_date`, `day_count`, `description` |
| 6 | guide_cannot_book_themselves | 400 | مرشد يحاول الوصول كtourist | رفض عبر `UserRole` middleware |

**المسار البرمجي المُختبر في `TouristGuideBookingController::book()`:**
```
┌─ book(request, checkGuideAvailability, guide)
│  ├─ if !guide->user->isAvailable()
│  │  └─ return api_error(422) ← [المسار 1: مرشد غير متاح]
│  ├─ if checkGuideAvailability->handle() == true
│  │  └─ return api_error(422) ← [المسار 2: مرشد محجوز مسبقاً]
│  ├─ data['total_price'] = day_count × daily_price ← [حساب السعر]
│  ├─ GuideBooking::create(data) ← [المسار الأساسي]
│  ├─ guide->user->notify() ← [إرسال إشعار]
│  └─ return api_success(201)
```

### 3.2 عرض الحجوزات (Tourist/Guide → List & Show)

| م | اسم الاختبار | الحالة المتوقعة | التحقق |
|---|---|---|---|
| 7 | tourist_can_list_own_bookings | 200 | السائح يرى حجوزاته فقط |
| 8 | tourist_sees_only_own_bookings | 200 | التحقق من أن العدد = 1 (لا يرى حجوزات الآخرين) |
| 9 | tourist_can_show_own_booking | 200 | عرض تفاصيل حجز مملوك |
| 10 | guide_can_list_own_bookings | 200 | المرشد يرى حجوزاته فقط |

### 3.3 إلغاء الحجز من قبل السائح (Tourist → Cancel)

| م | اسم الاختبار | الحالة المتوقعة | التحقق |
|---|---|---|---|
| 11 | tourist_can_cancel_pending_booking | 200 | حالة → CancelledByTourist |
| 12 | tourist_can_cancel_accepted_booking | 200 | حالة → CancelledByTourist |
| 13 | tourist_cannot_cancel_rejected_booking | 403 | رفض الإلغاء عبر `GuideBookingPolicy::cancelByTourist` |
| 14 | tourist_cannot_cancel_within_7_days | 403 | رفض الإلغاء لأن الفرق < 7 أيام |
| 15 | tourist_cannot_cancel_other_tourists_booking | 403 | رفض الإلغاء لأن الحجز لا يخصه |

**المسار البرمجي المُختبر في `TouristGuideBookingController::cancel()`:**
```
┌─ cancel(booking, request)
│  ├─ Gate::authorize('cancelByTourist') ← [التحقق من السياسة]
│  │  ├─ if user.id !== booking.tourist_id → deny ← [ليس صاحب الحجز]
│  │  ├─ if status ∉ [Pending, Accepted] → deny ← [حالة غير قابلة للإلغاء]
│  │  └─ allow ← [المسار الأساسي]
│  ├─ if diffInDays < 7 → throw AuthorizationException ← [أقل من أسبوع]
│  ├─ update(status = CancelledByTourist) ← [الإلغاء]
│  ├─ guide->user->notify() ← [إشعار المرشد]
│  └─ return api_success
```

### 3.4 إدارة الحجوزات من قبل المرشد (Guide → Accept/Reject/Cancel)

| م | اسم الاختبار | الحالة المتوقعة | التحقق |
|---|---|---|---|
| 16 | guide_can_accept_pending_booking | 200 | حالة → Accepted + سجل في guide_booking_logs |
| 17 | guide_cannot_accept_already_accepted_booking | 403 | رفض عبر `GuideBookingPolicy::acceptOrReject` |
| 18 | guide_cannot_accept_rejected_booking | 403 | رفض لأن الحالة ≠ Pending |
| 19 | guide_can_reject_pending_booking | 200 | حالة → Rejected |
| 20 | guide_cannot_reject_other_guides_booking | 403 | رفض لأن الحجز لا يخصه |
| 21 | guide_can_cancel_accepted_booking | 200 | حالة → CancelledByGuide |
| 22 | guide_cannot_cancel_pending_booking | 403 | رفض لأن الحالة ≠ Accepted |
| 23 | guide_cannot_cancel_other_guides_booking | 403 | رفض لأن الحجز لا يخصه |

**المسار البرمجي المُختبر في `GuideBookingPolicy::acceptOrReject()`:**
```
┌─ acceptOrReject(user, booking)
│  ├─ if user.guide.id !== booking.guide_id
│  │  └─ deny ← [ليس المرشد صاحب الحجز]
│  ├─ if booking.status !== Pending
│  │  └─ deny ← [الحالة ليست معلقة]
│  └─ allow ← [المسار الأساسي]
```

### 3.5 فرض الصلاحيات حسب الدور (Role Enforcement)

| م | اسم الاختبار | الحالة المتوقعة | التحقق |
|---|---|---|---|
| 24 | tourist_cannot_access_guide_booking_routes | 400 | السائح لا يصل لمسارات المرشد (`role:guide` middleware) |
| 25 | guide_cannot_access_tourist_booking_routes | 400 | المرشد لا يصل لمسارات السائح (`role:tourist` middleware) |

### 3.6 مراقب الحالة (Observer) والسجلات

| م | اسم الاختبار | الحالة المتوقعة | التحقق |
|---|---|---|---|
| 26 | booking_status_change_creates_log | 200 | تغيير الحالة من Pending → Accept ينشئ سجل في `guide_booking_logs` مع `old_status` و `new_status` |

### 3.7 الوصول غير المصرح به

| م | اسم الاختبار | الحالة المتوقعة | التحقق |
|---|---|---|---|
| 27 | unauthenticated_user_cannot_book | 401 | رفض عبر `auth:sanctum` middleware |
| 28 | unauthenticated_user_cannot_list_bookings | 401 | رفض عبر `auth:sanctum` middleware |

---

## رابعاً: الأخطاء المكتشفة عبر الاختبارات

اكتشفت اختبارات الصندوق الأبيض خطأً برمجياً واحداً في الكود:

### Bug #1: `basename(null)` في AuthService::register()

- **الموقع:** `app/Services/AuthService.php` السطر 40
- **السبب:** عند تسجيل مرشد بدون رفع صورة، `$avatarPath` يكون `null`، و `basename(null)` يرمي `TypeError`
- **الصيغة الحالية:** `'avatar' => basename($avatarPath)`
- **الصيغة المقترحة للإصلاح:** `'avatar' => $avatarPath ? basename($avatarPath) : null`
- **التأثير:** يمنع تسجيل المرشدين بدون صورة个人ية
- **ال気づき:** كشف بواسطة اختبار `test_guide_register_without_avatar_fails_due_to_bug`

---

## خامساً: ملخص تغطية الاختبارات

```
╔═══════════════════════════════════════════════════════╗
║          ملخص اختبارات الصندوق الأبيض               ║
╠═══════════════════════════════════════════════════════╣
║  إجمالي الاختبارات:        46                        ║
║  اختبارات ناجحة:           46 (100%)                 ║
║  اختبارات فاشلة:            0                        ║
║  إجمالي التصريحات:        101                        ║
║  وقت التنفيذ:          ~3.24 ثانية                   ║
╠═══════════════════════════════════════════════════════╣
║  وحدة المصادقة (AuthTest):     18 اختبار             ║
║  وحدة الحجوزات (GuideBooking): 28 اختبار             ║
╠═══════════════════════════════════════════════════════╣
║  الأخطاء المكتشفة:            1 (basename bug)       ║
╚═══════════════════════════════════════════════════════╝
```

### أكثر الحالات اختباراً:

| الفئة | العدد | النسبة |
|---|---|---|
| التحقق من المدخلات (Validation) | 14 | 30.4% |
| منع الوصول غير المصرح به (Authorization) | 12 | 26.1% |
| المسار الأساسي الناجح (Happy Path) | 10 | 21.7% |
| فرض الصلاحيات حسب الدور (Role) | 6 | 13.0% |
| التحقق من الأمان (Security) | 4 | 8.7% |

### الأدوات والتقنيات المستخدمة:

- **PHPUnit 12** — إطار الاختبار
- **RefreshDatabase** — سمة لإعادة إنشاء قاعدة البيانات بعد كل اختبار
- **Mockery** — لمحاكاة خدمات التحقق من توفر المرشد (لتفادي مشكلة SQL غير متوافقة مع SQLite)
- **Database Factories** — لتوليد بيانات اختبار واقعية
- **SQLite in-memory** — قاعدة بيانات سريعة و معزولة للاختبارات
