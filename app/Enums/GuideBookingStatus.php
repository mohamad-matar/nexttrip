<?php

namespace App\Enums;

enum GuideBookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case CancelledByTourist = 'cancelled_by_tourist';
    case CancelledByGuide = 'cancelled_by_guide';
    case Expired = 'expired';
    case Completed = 'completed';
    
}
