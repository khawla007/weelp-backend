<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\Country;

class PublicStateController extends Controller
{
    public function getStatesByCountry($country_slug)
    {
        $country = Country::where('slug', $country_slug)->first();

        if (!$country) {
            return response()->json(['success' => false, 'message' => 'Country not found'], 404);
        }

        $states = State::where('country_id', $country->id)->get();

        // return response()->json([
        //     'success' => true,
        //     'data' => $states
        // ]);
        if (collect($states)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'States not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $states
        ], 200);
    }
}

