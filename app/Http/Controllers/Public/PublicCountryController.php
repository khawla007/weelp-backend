<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Country;

class PublicCountryController extends Controller
{
    public function getCountries()
    {
        $countries = Country::all();

        if (empty($countries)) {
            return response()->json([
                'status' => 'false',
                'message' => 'Country not found'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $countries
        ], 200);
    }
}
