<?php

namespace App\Policies;

use App\Models\SuggestedPlace;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SuggestedPlacePolicy
{    

    /**
     * عرض الكل في index: الأدمن يرى الكل، السائح والمرشد يرون سجلاتهم فقط
     */
    public function viewAny(User $user): Response
    {
        
        if ($user->isAdmin()) {
            return Response::allow();
        }

        if ($user->isTourist() || $user->isGuide()) {
            return Response::allow();
        }

        return Response::deny('غير مصرح لك.');
    }

    /**
     * إنشاء: فقط للمرشد أو السائح
     */
    public function create(User $user): Response
    {
        if ($user->isTourist() || $user->isGuide()) {
            return Response::allow();
        }

        return Response::deny('غير مصرح لك بإضافة أماكن مقترحة.');
    }
    
    /**
     * المراجعة: فقط للأدمن
     */
    public function review(User $user, SuggestedPlace $suggestedPlace): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('غير مصرح لك بمراجعة الأماكن المقترحة.');
    }

    /**
     * الحذف: السائح والمرشد يحذفون سجلاتهم فقط
     */
    public function delete(User $user, SuggestedPlace $suggestedPlace): Response
    {    
        if ($user->id === $suggestedPlace->user_id) {
            return Response::allow();
        }

        return Response::deny('لا يمكنك حذف سجل لا يخصك.');
    }
}
