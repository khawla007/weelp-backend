<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Define Attributes with Values
        $attributes = [
            [
                'name' => 'Duration',
                'type' => 'single_select',
                'description' => 'How long the lasts',
                'values' => ['1 Hour', '2 Hours', 'Half Day', 'Full Day'],
                'default_value' => '2 Hours'
            ],
            [
                'name' => 'Difficulty Level',
                'type' => 'single_select',
                'description' => 'difficulty level',
                'values' => ['Easy', 'Medium', 'Hard'],
                'default_value' => 'Medium'
            ],
            [
                'name' => 'Group Size',
                'type' => 'single_select',
                'description' => 'Maximum number of participants',
                'values' => ['1-5', '6-10', '11-20', '20+'],
                'default_value' => '6-10'
            ],
            [
                'name' => 'Age Restriction',
                'type' => 'single_select',
                'description' => 'Minimum age required for the',
                'values' => ['All Ages', '12+', '18+'],
                'default_value' => '12+'
            ],
        
            [
                'name' => 'Language',
                'type' => 'multi_select',
                'description' => 'Languages supported in the activity',
                'values' => ['English', 'Spanish', 'French', 'German'],
                'default_value' => 'English'
            ],
            [
                'name' => 'Season',
                'type' => 'single_select',
                'description' => 'Best season to attend',
                'values' => ['Summer', 'Winter', 'Spring', 'Autumn'],
                'default_value' => 'Summer'
            ],
            [
                'name' => 'Transport Included',
                'type' => 'single_select',
                'description' => 'Whether transport is included',
                'values' => ['Yes', 'No'],
                'default_value' => 'No'
            ],
            [
                'name' => 'Meal Options',
                'type' => 'multi_select',
                'description' => 'Meals provided during the activity',
                'values' => ['Breakfast', 'Lunch', 'Dinner', 'Snacks'],
                'default_value' => 'Snacks'
            ],
            [
                'name' => 'Accessibility',
                'type' => 'single_select',
                'description' => 'Is the activity accessible?',
                'values' => ['Wheelchair Accessible', 'Not Accessible'],
                'default_value' => 'Wheelchair Accessible'
            ],
            [
                'name' => 'Pet Friendly',
                'type' => 'single_select',
                'description' => 'Can you bring pets?',
                'values' => ['Yes', 'No'],
                'default_value' => 'No'
            ],
            [
                'name' => 'Booking Type',
                'type' => 'single_select',
                'description' => 'Type of booking available',
                'values' => ['Private', 'Shared'],
                'default_value' => 'Shared'
            ],
            [
                'name' => 'Weather Dependency',
                'type' => 'single_select',
                'description' => 'Is the activity weather dependent?',
                'values' => ['Yes', 'No'],
                'default_value' => 'Yes'
            ],
            [
                'name' => 'Cancellation Policy',
                'type' => 'single_select',
                'description' => 'How cancellation is handled',
                'values' => ['Flexible', 'Moderate', 'Strict'],
                'default_value' => 'Moderate'
            ],
            [
                'name' => 'Guide Type',
                'type' => 'single_select',
                'description' => 'Type of guide provided',
                'values' => ['Professional', 'Local', 'Self-Guided'],
                'default_value' => 'Professional'
            ],
            [
                'name' => 'Equipment Provided',
                'type' => 'single_select',
                'description' => 'Is equipment provided?',
                'values' => ['Yes', 'No'],
                'default_value' => 'Yes'
            ],
            [
                'name' => 'Photography Allowed',
                'type' => 'single_select',
                'description' => 'Can participants take photos?',
                'values' => ['Yes', 'No'],
                'default_value' => 'Yes'
            ],
            [
                'name' => 'Certificate Provided',
                'type' => 'single_select',
                'description' => 'Is a certificate given at the end?',
                'values' => ['Yes', 'No'],
                'default_value' => 'No'
            ],
            [
                'name' => 'Activity Level',
                'type' => 'single_select',
                'description' => 'Intensity of the activity',
                'values' => ['Low', 'Moderate', 'High'],
                'default_value' => 'Moderate'
            ],
            [
                'name' => 'Region',
                'type' => 'single_select',
                'description' => 'Region of the activity',
                'values' => ['North', 'South', 'East', 'West'],
                'default_value' => 'North'
            ],
            [
                'name' => 'Clothing Required',
                'type' => 'multi_select',
                'description' => 'What clothes should be worn?',
                'values' => ['Casual', 'Swimwear', 'Hiking Gear', 'Formal'],
                'default_value' => 'Casual'
            ],
            [
                'name' => 'Skill Required',
                'type' => 'single_select',
                'description' => 'Skill level required',
                'values' => ['None', 'Basic', 'Advanced'],
                'default_value' => 'Basic'
            ],
            [
                'name' => 'Travel Distance',
                'type' => 'single_select',
                'description' => 'How far to travel to location',
                'values' => ['< 5 km', '5-15 km', '15-30 km', '30+ km'],
                'default_value' => '5-15 km'
            ],
            [
                'name' => 'Online Available',
                'type' => 'single_select',
                'description' => 'Is virtual participation possible?',
                'values' => ['Yes', 'No'],
                'default_value' => 'No'
            ],
            [
                'name' => 'Insurance Required',
                'type' => 'single_select',
                'description' => 'Need insurance to attend?',
                'values' => ['Yes', 'No'],
                'default_value' => 'No'
            ],
            [
                'name' => 'Check-in Time',
                'type' => 'single_select',
                'description' => 'Recommended check-in time',
                'values' => ['15 mins before', '30 mins before', '1 hour before'],
                'default_value' => '30 mins before'
            ],
            [
                'name' => 'Safety Gear Provided',
                'type' => 'single_select',
                'description' => 'Is safety gear given?',
                'values' => ['Yes', 'No'],
                'default_value' => 'Yes'
            ],
            [
                'name' => 'Food Allergies Support',
                'type' => 'single_select',
                'description' => 'Is allergy-friendly food available?',
                'values' => ['Yes', 'No'],
                'default_value' => 'No'
            ],
            [
                'name' => 'Recording Allowed',
                'type' => 'single_select',
                'description' => 'Are participants allowed to record?',
                'values' => ['Yes', 'No'],
                'default_value' => 'Yes'
            ],
            [
                'name' => 'Accessibility Guide',
                'type' => 'single_select',
                'description' => 'Will an accessibility guide be present?',
                'values' => ['Yes', 'No'],
                'default_value' => 'No'
            ],
            [
                'name' => 'Rest Breaks',
                'type' => 'single_select',
                'description' => 'Are there scheduled breaks?',
                'values' => ['Yes', 'No'],
                'default_value' => 'Yes'
            ],
            [
                'name' => 'Free Time Included',
                'type' => 'single_select',
                'description' => 'Is there free/exploration time?',
                'values' => ['Yes', 'No'],
                'default_value' => 'No'
            ],
            [
                'name' => 'Live Interaction',
                'type' => 'single_select',
                'description' => 'Will there be live Q&A or chat?',
                'values' => ['Yes', 'No'],
                'default_value' => 'Yes'
            ],
            [
                'name' => 'Host Gender Preference',
                'type' => 'single_select',
                'description' => 'Preferred gender of host/guide',
                'values' => ['No Preference', 'Male', 'Female'],
                'default_value' => 'No Preference'
            ],
            [
                'name' => 'Repeatable Booking',
                'type' => 'single_select',
                'description' => 'Can users book this multiple times?',
                'values' => ['Yes', 'No'],
                'default_value' => 'Yes'
            ]
        ];        

        // Insert Attributes
        foreach ($attributes as $data) {
            Attribute::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'],
                'default_value' => $data['default_value'],
                // 'values' => in_array($data['type'], ['single_select', 'multi_select']) ? json_encode($data['values']) : null
                'values' => in_array($data['type'], ['single_select', 'multi_select']) ? implode(',', $data['values']) : null
            ]);
        }
    }
}
