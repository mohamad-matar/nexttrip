<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;


#[Fillable([
    'user_id',
    'city_id',
    'name',
    'description',
    'latitude',
    'longitude',
    'images',
    'status',
    'admin_notes',
])]
class SuggestedPlace extends Model
{
    // protected $appends = ['image_urls'];

    protected $casts = [
        'images' => 'array',
        'status' => \App\Enums\SuggestedPlaceStatus::class,
    ];



    /**
     * Accessor لتحويل أسماء الصور إلى مسارات كاملة باستخدام asset
     */
    protected function images(): Attribute
    {
        return Attribute::make(
            get: function ($value) {

                // 1. فك تشفير حقل الـ JSON المخزن في قاعدة البيانات إلى مصفوفة PHP
                $images = is_string($value) ? json_decode($value, true) : $value;

                // التأكد من أنها مصفوفة صالحة وليست فارغة            
                if (! is_array($images) || empty($images)) {
                    return [];
                }

                // 2. الدوران على كل اسم ملف وبناء الرابط الكامل له عبر دالة asset
                return array_map(function ($imageName) {
                    return asset('storage/suggested-places/' . $imageName);
                }, $images);
            }
        );
    }

    // protected function imageUrls(): Attribute
    // {
    //     return Attribute::make(
    //         get: function () {
    //             $images = $this->images ?? [];
    //             if (! is_array($images) || empty($images)) {
    //                 return [];
    //             }

    //             return array_map(fn ($image) => asset('storage/suggested/' . $image), $images);
    //         }
    //     );
    // }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
