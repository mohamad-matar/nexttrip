<?php

namespace App\Policies;

use App\Enums\GuideBookingStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\BadDataException;
use App\Models\Guide;
use App\Models\GuideBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

class GuideBookingPolicy
{
    public function book(User $user,  Guide $guide, Carbon $start, int $days): bool
    {
        // 1) فقط السياح يمكنهم الحجز
        if (! $user->isTourist()) {
            throw new BadDataException('فقط السياح يمكنهم إنشاء حجز.');
        }

        // 2) حالة الحساب يجب أن تكون مفعلة
        if (! $user->isActive()) {
            throw new BadDataException('حسابك غير مفعل ولا يمكنك الحجز.');
        }

        // 3) حالة المرشد يجب أن تكون مفعلة
        if (! $guide->user->isActive()) {
            throw new BadDataException('لا يمكن الحجز مع مرشد غير مفعل.');
        }

        // 4) التحقق من التداخل في الحجوزات
        $end = (clone $start)->addDays($days - 1);

        $hasConflict = GuideBooking::query()
            ->where('guide_id', $guide->id)
            ->whereIn('status', [
                GuideBookingStatus::Accepted->value,
                GuideBookingStatus::Pending->value
            ])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<=', $end)
                    ->whereRaw('DATE_ADD(start_date, INTERVAL day_count-1 DAY) >= ?', [$start]);
            })
            ->exists();

        if ($hasConflict) {
            throw new BadDataException('المرشد محجوز مسبقًا في هذه الفترة.');
        }

        return true;
    }
    /**
     * إلغاء الحجز من قبل السائح
     */
    public function cancelByTourist(User $user, GuideBooking $booking): bool
    {
        // 1) يجب أن يكون صاحب الحجز
        if ($user->id !== $booking->tourist_id) {
            throw new AuthorizationException('لا يمكنك إلغاء حجز لا يخصك.');
        }

        // 2) لا يمكن إلغاء الحجوزات المكتملة أو المنتهية
        if ($booking->status === GuideBookingStatus::Completed) {
            throw new AuthorizationException('لا يمكن إلغاء حجز مكتمل.');
        }

        // 3) يجب أن تكون الحالة قابلة للإلغاء
        if (!in_array($booking->status, [
            GuideBookingStatus::Pending,
            GuideBookingStatus::Accepted,
        ])) {
            throw new AuthorizationException('لا يمكن إلغاء هذا الحجز في حالته الحالية.');
        }

        // 4) يجب ألا يكون الفرق أقل من 7 أيام
        $daysDiff = now()->diffInDays($booking->start_date, absolute: false);

        if ($daysDiff < 7) {
            throw new AuthorizationException('عذراً، بقي أقل من أسبوع للرحلة ولا يمكن إلغاء الحجز.');
        }

        return true;
    }

    /**
     * كتابة مراجعة (السائح فقط)
     */
    public function review(User $user, GuideBooking $booking): bool
    {
        // 1) يجب أن يكون السائح صاحب الحجز
        if ($user->id !== $booking->tourist_id) {
            throw new AuthorizationException('لا يمكنك كتابة مراجعة لحجز لا يخصك.');
        }

        // 2) يجب أن تكون الحالة Completed
        if ($booking->status !== GuideBookingStatus::Completed) {
            throw new AuthorizationException('يمكنك كتابة مراجعة فقط بعد انتهاء الرحلة.');
        }

        // 3) يجب ألا يكون هناك تقييم سابق
        if ($booking->review) {
            throw new AuthorizationException('لقد قمت بكتابة مراجعة مسبقاً.');
        }

        return true;
    }

    /**
     * عرض الحجز (السائح + المرشد + الأدمن)
     */
    public function view(User $user, GuideBooking $booking): bool
    {
        if (
            $user->id === $booking->tourist_id ||
            $user?->guide?->id === $booking->guide_id ||
            $user->isAdmin()
        ) {
            return true;
        }

        throw new AuthorizationException('غير مسموح لك بعرض تفاصيل هذا الحجز.');
    }

    /**
     * إلغاء الحجز من قبل المرشد
     */
    public function cancelByGuide(User $user, GuideBooking $booking): bool
    {
        // 1) يجب أن يكون المرشد صاحب الحجز
        if ($user?->guide?->id !== $booking->guide_id) {
            throw new AuthorizationException('لا يمكنك إلغاء حجز لا يخصك.');
        }

        // 2) لا يمكن إلغاء الحجوزات المكتملة
        if ($booking->status === GuideBookingStatus::Completed) {
            throw new AuthorizationException('لا يمكن إلغاء حجز مكتمل.');
        }

        // 3) يجب أن تكون الحالة Accepted فقط
        if ($booking->status !== GuideBookingStatus::Accepted) {
            throw new AuthorizationException('يمكن للمرشد إلغاء الحجوزات المقبولة فقط.');
        }

        return true;
    }

    /**
     * قبول أو رفض الحجز من قبل المرشد
     */
    public function reactByGuide(User $user, GuideBooking $booking): bool
    {
        // 1) يجب أن يكون المرشد صاحب الحجز
        if ($user?->guide?->id !== $booking->guide_id) {
            throw new AuthorizationException('لا يمكنك إدارة حجز لا يخصك.');
        }

        // 2) يجب أن تكون الحالة Pending فقط
        if ($booking->status !== GuideBookingStatus::Pending) {
            throw new AuthorizationException('يمكن إدارة الحجوزات المعلقة فقط.');
        }

        return true;
    }
}
