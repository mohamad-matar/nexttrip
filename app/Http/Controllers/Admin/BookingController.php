<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuideBookingStatus;
use App\Http\Controllers\Controller;
use App\Models\GuideBooking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = GuideBooking::with(['tourist:id,name', 'guide.user:id,name'])
            ->orderBy('created_at', 'desc');

        // فلترة حسب الحالة إذا تم تمريرها
        if ($status && GuideBookingStatus::tryFrom($status)) {
            $query->where('status', GuideBookingStatus::from($status));
        }

        $bookings = $query->get();

        return api_success([
            'stats' => $this->stats(),
            'bookings' => $bookings,
        ]);
    }

    public function show(GuideBooking $booking)
    {
        $booking->load(['tourist:id,name', 'guide.user:id,name', 'trip']);
        return api_success($booking);
    }

    /**
     * إحصائيات حجوزات المرشدين.
     */
    private function stats()
    {
        $total = GuideBooking::count();

        $byStatus = GuideBooking::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();       

        return [
            'total' => $total,
            'pending' => $byStatus[GuideBookingStatus::Pending->value] ?? 0,
            'accepted' => $byStatus[GuideBookingStatus::Accepted->value] ?? 0,
            'rejected' => $byStatus[GuideBookingStatus::Rejected->value] ?? 0,
            'completed' => $byStatus[GuideBookingStatus::Completed->value] ?? 0,
            'cancelled' => ($byStatus[GuideBookingStatus::CancelledByTourist->value] ?? 0)
                + ($byStatus[GuideBookingStatus::CancelledByGuide->value] ?? 0),
            'expired' => $byStatus[GuideBookingStatus::Expired->value] ?? 0,
        ];
    }
}
