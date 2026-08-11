<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SuggestedPlaceReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $suggestedPlaceId,
        public string $placeName,
        public ?string $cityName,
        public string $status,
        public ?string $adminNotes
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $message = $this->status === 'approved' 
            ? 'تم قبول اقتراح المكان الخاص بك' 
            : 'تم رفض اقتراح المكان الخاص بك';

        return [
            'type' => 'suggested-place-reviewed',
            'message' => $message,
            'suggested_place_id' => $this->suggestedPlaceId,
            'place_name' => $this->placeName,
            'city_name' => $this->cityName,
            'status' => $this->status,
            'admin_notes' => $this->adminNotes,
        ];
    }
}
