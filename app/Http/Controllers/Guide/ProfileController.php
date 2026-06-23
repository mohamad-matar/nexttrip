<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateGuideProfileRequest;
use App\Http\Resources\GuideResource;
use App\Models\User;

use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        $guide = Auth::user()->guide;
        $guide->load('languages' , 'cities');
        // return $guide;
        return api_success(data: new GuideResource($guide , true));
    }

    public function update(UpdateGuideProfileRequest $request)
    {    
        $guideData = $request->only('gender', 'phone', 'DOB', 'daily_price', 'bio' );
        $userData = $request->only('name' , 'status');
             
        if ($request->hasFile('avatar')) {
            $guideData['avatar'] = $request->file('avatar')->store('avatars');
        }
        
        $guide = Auth::user()->guide;

        User::find(Auth::id())->update($userData);
        $guide->update($guideData);

        // تحديث اللغات
        $guide->languages()->sync($request->languages);
        
        // تحديث المدن
        $guide->cities()->sync($request->cities);

        return api_success(data: new GuideResource($guide));
    }
}
