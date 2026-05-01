<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItineraryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_itinerary_id' => 'required|exists:itineraries,id',
            'schedules' => 'required|array|min:1',
            'schedules.*.day' => 'required|integer|min:1',
            'schedules.*.title' => 'nullable|string|max:255',
            'schedules.*.activities' => 'nullable|array',
            'schedules.*.activities.*.activity_id' => 'required|exists:activities,id',
            'schedules.*.activities.*.start_time' => 'nullable|string|max:8',
            'schedules.*.activities.*.end_time' => 'nullable|string|max:8',
            'schedules.*.activities.*.notes' => 'nullable|string|max:5000',
            'schedules.*.activities.*.price' => 'nullable|numeric',
            'schedules.*.activities.*.included' => 'nullable|boolean',
            'schedules.*.transfers' => 'nullable|array',
            'schedules.*.transfers.*.transfer_id' => 'required|exists:transfers,id',
            'schedules.*.transfers.*.pickup_location' => 'nullable|string|max:255',
            'schedules.*.transfers.*.dropoff_location' => 'nullable|string|max:255',
            'schedules.*.transfers.*.start_time' => 'nullable|string|max:8',
            'schedules.*.transfers.*.end_time' => 'nullable|string|max:8',
            'schedules.*.transfers.*.notes' => 'nullable|string|max:5000',
            'schedules.*.transfers.*.price' => 'nullable|numeric',
            'schedules.*.transfers.*.included' => 'nullable|boolean',
            'schedules.*.transfers.*.pax' => 'nullable|integer|min:1',
            'schedules.*.transfers.*.bag_count' => 'nullable|integer|min:0',
            'schedules.*.transfers.*.waiting_minutes' => 'nullable|integer|min:0',
        ];
    }
}
