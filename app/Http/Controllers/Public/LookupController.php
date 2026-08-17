<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Language;
use App\Models\Place;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function cities()
    {
        $cities = City::all();
        return api_success($cities, "كافة المدن");
    }

    public function languages()
    {
        $languages = Language::all();
        return api_success($languages, "كافة اللغات");
    }

    public function topPlaces()
    {
        $places = Place::with(['city:id,name'])
            ->with(['images:id,place_id,image_url,order'])
            ->select('id', 'name', 'city_id', 'average_rating', 'reviews_count')
            ->orderByDesc('average_rating')
            ->orderByDesc('reviews_count')
            ->limit(10)
            ->get();

        return api_success(data: $places, message: "أكثر الأماكن زيارة");
    }
}
