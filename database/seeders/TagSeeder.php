<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creating Activity Tags
        $tags = [
            ['name' => 'Outdoor', 'slug' => 'outdoor', 'description' => 'Activities conducted outdoors'],
            ['name' => 'Indoor', 'slug' => 'indoor', 'description' => 'Activities conducted indoors'],
            ['name' => 'Beginner', 'slug' => 'beginner', 'description' => 'Beginner-friendly activities'],
            ['name' => 'Advanced', 'slug' => 'advanced', 'description' => 'Activities for advanced users'],
        
            ['name' => 'Adventure', 'slug' => 'adventure', 'description' => 'Thrilling and exciting experiences'],
            ['name' => 'Cultural', 'slug' => 'cultural', 'description' => 'Experience different cultures and traditions'],
            ['name' => 'Romantic', 'slug' => 'romantic', 'description' => 'Perfect for couples and romantic getaways'],
            ['name' => 'Luxury', 'slug' => 'luxury', 'description' => 'Premium and high-end experiences'],
            ['name' => 'Budget', 'slug' => 'budget', 'description' => 'Cost-effective and affordable options'],
            ['name' => 'Wildlife', 'slug' => 'wildlife', 'description' => 'Animal encounters and safaris'],
            ['name' => 'Historical', 'slug' => 'historical', 'description' => 'Explore historical sites and stories'],
            ['name' => 'Nature', 'slug' => 'nature', 'description' => 'Explore the beauty of natural surroundings'],
            ['name' => 'Eco-Friendly', 'slug' => 'eco-friendly', 'description' => 'Sustainable and green experiences'],
            ['name' => 'Wellness', 'slug' => 'wellness', 'description' => 'Relaxation and health-focused activities'],
            ['name' => 'Food & Drink', 'slug' => 'food-drink', 'description' => 'Culinary tours and tastings'],
            ['name' => 'Photography', 'slug' => 'photography', 'description' => 'Photo tours and scenic spots'],
            ['name' => 'Spiritual', 'slug' => 'spiritual', 'description' => 'Meditation, temples, and retreats'],
            ['name' => 'Nightlife', 'slug' => 'nightlife', 'description' => 'Bars, clubs, and night experiences'],
            ['name' => 'Shopping', 'slug' => 'shopping', 'description' => 'Local markets and malls'],
            ['name' => 'Water Sports', 'slug' => 'water-sports', 'description' => 'Activities in and on water'],
            ['name' => 'Mountain', 'slug' => 'mountain', 'description' => 'Mountain adventures and hikes'],
            ['name' => 'Desert', 'slug' => 'desert', 'description' => 'Sand dunes and desert safaris'],
            ['name' => 'Snow', 'slug' => 'snow', 'description' => 'Winter activities and snow experiences'],
            ['name' => 'Festival', 'slug' => 'festival', 'description' => 'Join local events and festivals'],
            ['name' => 'Educational', 'slug' => 'educational', 'description' => 'Learning-focused experiences'],
            ['name' => 'Cruise', 'slug' => 'cruise', 'description' => 'Sea voyages and boat tours'],
            ['name' => 'Scenic', 'slug' => 'scenic', 'description' => 'Beautiful viewpoints and nature'],
            ['name' => 'Extreme', 'slug' => 'extreme', 'description' => 'High-adrenaline adventure activities'],
            ['name' => 'Volunteer', 'slug' => 'volunteer', 'description' => 'Give back with meaningful work'],
            ['name' => 'Team Building', 'slug' => 'team-building', 'description' => 'Group cooperation and bonding'],
            ['name' => 'DIY', 'slug' => 'diy', 'description' => 'Do-it-yourself workshops and crafts'],
            ['name' => 'Art & Craft', 'slug' => 'art-craft', 'description' => 'Creative and handmade experiences'],
            ['name' => 'Music', 'slug' => 'music', 'description' => 'Concerts, jams, and music events'],
            ['name' => 'Fitness', 'slug' => 'fitness', 'description' => 'Health and exercise activities'],
            ['name' => 'Tech', 'slug' => 'tech', 'description' => 'Technology-related experiences'],
        ];
        

        foreach ($tags as $tag) {
            Tag::create($tag);
        }
    }
}
