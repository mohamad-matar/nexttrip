<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TouristInterestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $interest_ids = $user->interests()->orderBy('interest_id')->pluck('interest_id');
        return api_success($interest_ids);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'interests' => 'required|array|min:1',
            'interests.*' => 'integer|exists:interests,id',
        ]);

        $user = $request->user();
        $user->interests()->sync(array_map('intval', $data['interests']));
        $interest_ids = $user->interests()->orderBy('interest_id')->pluck('interest_id');
        return api_success($interest_ids, 'تم حفظ اهتماماتك');
    }
}
