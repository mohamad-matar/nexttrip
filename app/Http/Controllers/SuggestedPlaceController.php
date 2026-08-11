<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\SuggestedPlace;
use App\Models\User;
use App\Notifications\SuggestedPlaceSubmittedNotification;
use App\Notifications\SuggestedPlaceReviewedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;


class SuggestedPlaceController extends Controller
{
    public function index(Request $request)
    {
        $suggestedPlaces = SuggestedPlace::query()
            ->with(['user:id,name,role', 'city'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->user()->isTourist() || $request->user()->isGuide(), fn($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->get();

        return api_success($suggestedPlaces);
    }

    public function show(SuggestedPlace $suggestedPlace)
    {
        Gate::authorize('view', $suggestedPlace);
        return api_success($suggestedPlace->load(['user', 'city']));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', SuggestedPlace::class);

        $data = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            // التحقق من مصفوفة الصور وأن كل عنصر داخلها هو صورة فعلياً
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $user = $request->user();
        $data['user_id'] = $user->id;
        $storedImages = [];
        // الدوران على مصفوفة الصور ورفعها ملفاً تلو الآخر
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file->isValid()) {
                    $storedImages[] = basename($file->store('suggested-places', 'public'));
                }
            }
        }

        // تنظيف وتصفية المسارات المخزنة
        $data['images'] = array_values(array_unique(array_filter($storedImages)));

        $suggestedPlace = SuggestedPlace::create($data);

        // 2. جلب بيانات المدينة لإرسالها في الإشعار
        $city = City::find($data['city_id']);

        // 3. إرسال الإشعار للأدمن
        $admin = User::where('role', 'admin')->first();
        if ($admin && $city) {
            $admin->notify(new SuggestedPlaceSubmittedNotification(
                $suggestedPlace->id,
                $suggestedPlace->name, 
                $city->name,           
                $user->name         
            ));
        }

        return api_success($suggestedPlace->load(['user', 'city']), 'تم الإنشاء والمراسلة بنجاح', 201);
    }

    public function review(Request $request, SuggestedPlace $suggestedPlace)
    {
        Gate::authorize('review', $suggestedPlace);

        $data = $request->validate([
            'status' => 'sometimes|in:approved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $suggestedPlace->update($data);

        // إرسال إشعار للمستخدم الذي قدم الاقتراح
        if (isset($data['status']) && $suggestedPlace->user) {
            $suggestedPlace->user->notify(new SuggestedPlaceReviewedNotification(
                $suggestedPlace->id,
                $suggestedPlace->name,
                $suggestedPlace->city?->name,
                $data['status'],
                $data['admin_notes'] ?? null
            ));
        }

        return api_success($suggestedPlace->fresh()->load(['user', 'city']), 'تم التحديث');
    }


    public function destroy(SuggestedPlace $suggestedPlace)
    {
        Gate::authorize('delete', $suggestedPlace);

        $suggestedPlace->delete();
        return api_success(null, 'تم الحذف');
    }
}
