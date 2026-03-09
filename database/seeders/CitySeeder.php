<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\City;
use App\Models\CityMediaGallery;
use App\Models\CityLocationDetail;
use App\Models\CityTravelInfo;
use App\Models\CitySeason;
use App\Models\CityEvent;
use App\Models\CityAdditionalInfo;
use App\Models\CityFaq;
use App\Models\CitySeo;

class CitySeeder extends Seeder
{
    /**
     * Generate a random date/datetime in 2027
     * @param bool $dateOnly If true, return date only (Y-m-d), otherwise datetime (Y-m-d H:i:s)
     */
    private function random2027Date(bool $dateOnly = false): string
    {
        $start = strtotime('2027-01-01');
        $end = strtotime('2027-12-31');
        $timestamp = mt_rand($start, $end);
        return date($dateOnly ? 'Y-m-d' : 'Y-m-d H:i:s', $timestamp);
    }

    public function run()
    {
        // Delete all existing cities and related data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        City::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        echo "All existing cities deleted.\n";
    }
}
