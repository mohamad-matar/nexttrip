<?php

namespace Database\Seeders;

use App\Enums\SuggestedPlaceStatus;
use App\Models\SuggestedPlace;
use App\Models\User;
use App\Models\City;
use App\Notifications\SuggestedPlaceSubmittedNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class SuggestedPlaceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $cities = City::all();
        $sourcePath = database_path('seeders/images/suggested');
        $imageFiles = File::exists($sourcePath) ? File::files($sourcePath) : [];

        if ($users->isEmpty() || $cities->isEmpty()) {
            $this->command->warn('No users or cities found — skipping SuggestedPlaceSeeder.');
            return;
        }

        if (empty($imageFiles)) {
            $this->command->warn('No suggested place images found in ' . $sourcePath . ' — images will not be copied.');
        }

        Storage::disk('public')->makeDirectory('suggested');

        $allImageNames = [];
        foreach ($imageFiles as $file) {
            $fileName = $file->getFilename();
            Storage::disk('public')->putFileAs('suggested', $file, $fileName);
            $allImageNames[] = $fileName;
        }

        $city = City::first();

        $tourist = User::where('role', 'tourist')->first() ?? $users->random();

        $suggestedPlace = SuggestedPlace::create([
            'user_id' => $tourist->id,
            'city_id' => $city->id,
            'name' => 'مغارة موسى - بلودان',
            'description' => 'مغارة تاريخية في بلودان بريف دمشق، تُعد وجهة سياحية فريدة تُظهِر تشكيلات صخرية ونقوشاً أثرية ضمن أجواء الكهف.',
            'latitude' => 33.6930,
            'longitude' => 36.2080,
            'images' => $allImageNames,
            'status' => SuggestedPlaceStatus::Pending->value,
        ]);

        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->notify(new SuggestedPlaceSubmittedNotification(
                $suggestedPlace->id,
                'مغارة موسى - بلودان',      
                $city->name,
                $tourist->name,
            ));
        }
        $this->command->info(" تم إنشاء اقتراح مكان بنجاح.");

    }
}
