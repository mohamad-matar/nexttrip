<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TripPlannerService;
use App\Services\TripSavingService;
use Illuminate\Http\Request;

class AIController extends Controller
{

    protected TripPlannerService $tripPlanner;
    protected TripSavingService $tripSaver;

    public function __construct(TripPlannerService $tripPlanner, TripSavingService $tripSaver)
    {
        $this->tripPlanner = $tripPlanner;
        $this->tripSaver = $tripSaver;
    }

    public function planTrip(Request $request)
{
    $user = $request->user();

    // 1) تجهيز البيانات للذكاء الاصطناعي
    $data = $this->tripPlanner->prepareDataForAI($user, $request->all());

    // 2) إرسال البيانات للذكاء الاصطناعي (placeholder)
    $aiResponse = $this->sendToAI($data);

    // 3) حفظ الرحلة في قاعدة البيانات
    $trip = $this->tripSaver->savePlannedTrip($user, $aiResponse);

    return response()->json([
        'success' => true,
        'trip' => $trip
    ]);
}


    private function sendToAI(array $data)
    {
        // هنا فقط نرسل البيانات للذكاء الاصطناعي
        // بدون أي منطق تخطيط
        // بدون أي تحليل
        // بدون أي ذكاء

        // هذا مجرد placeholder
        // رفاعي سيكتب المنطق الحقيقي لاحقًا    
        // لاحقًا هنا سيكتب رفاعي الـ Prompt والمنطق الذكي
        // الآن فقط نرجع البيانات كما هي

        return [
            'status' => 'AI placeholder',
            'note' => 'add the AI logic.',
            'received_data' => $data
        ];
    }
}
