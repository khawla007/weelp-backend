<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creating Activity Categories
        $categories = [
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'All sports-related activities'],
            ['name' => 'Music', 'slug' => 'music', 'description' => 'Musical events and activities'],
            ['name' => 'Fitness', 'slug' => 'fitness', 'description' => 'Health and fitness activities'],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Learning and educational activities'],
        
            ['name' => 'Adventure', 'slug' => 'adventure', 'description' => 'Adventure trips and challenges'],
            ['name' => 'Wellness', 'slug' => 'wellness', 'description' => 'Wellness and self-care programs'],
            ['name' => 'Cultural', 'slug' => 'cultural', 'description' => 'Cultural festivals and traditions'],
            ['name' => 'Food & Drink', 'slug' => 'food-drink', 'description' => 'Culinary tours and tastings'],
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Tech-based events and expos'],
            ['name' => 'Travel', 'slug' => 'travel', 'description' => 'Travel and tourism experiences'],
            ['name' => 'Nature', 'slug' => 'nature', 'description' => 'Activities in nature and outdoors'],
            ['name' => 'Photography', 'slug' => 'photography', 'description' => 'Photo walks and contests'],
            ['name' => 'Business', 'slug' => 'business', 'description' => 'Entrepreneurship and networking'],
            ['name' => 'Volunteering', 'slug' => 'volunteering', 'description' => 'Community service and charity'],
            ['name' => 'Science', 'slug' => 'science', 'description' => 'Science fairs and experiments'],
            ['name' => 'Fashion', 'slug' => 'fashion', 'description' => 'Style shows and clothing workshops'],
            ['name' => 'Film & TV', 'slug' => 'film-tv', 'description' => 'Film screenings and TV events'],
            ['name' => 'Art & Craft', 'slug' => 'art-craft', 'description' => 'Creative art and handmade items'],
            ['name' => 'Gaming', 'slug' => 'gaming', 'description' => 'Video games and tournaments'],
            ['name' => 'History', 'slug' => 'history', 'description' => 'Historical tours and lectures'],
            ['name' => 'Literature', 'slug' => 'literature', 'description' => 'Book readings and writing'],
            ['name' => 'Kids', 'slug' => 'kids', 'description' => 'Activities designed for children'],
            ['name' => 'Teens', 'slug' => 'teens', 'description' => 'Teen-focused events and hobbies'],
            ['name' => 'Seniors', 'slug' => 'seniors', 'description' => 'Programs for older adults'],
            ['name' => 'Pet-Friendly', 'slug' => 'pet-friendly', 'description' => 'Bring your pets along!'],
            ['name' => 'Environmental', 'slug' => 'environmental', 'description' => 'Eco and green activities'],
            ['name' => 'DIY', 'slug' => 'diy', 'description' => 'Hands-on workshops and building things'],
            ['name' => 'Startups', 'slug' => 'startups', 'description' => 'Startup showcases and pitch events'],
            ['name' => 'Health', 'slug' => 'health', 'description' => 'Medical and wellness awareness'],
            ['name' => 'Language', 'slug' => 'language', 'description' => 'Language learning and exchange'],
            ['name' => 'Debate', 'slug' => 'debate', 'description' => 'Public speaking and debates'],
            ['name' => 'Comedy', 'slug' => 'comedy', 'description' => 'Stand-up, improv and fun shows'],
            ['name' => 'Nightlife', 'slug' => 'nightlife', 'description' => 'Clubbing, drinks, and late night events'],
            ['name' => 'Public Speaking', 'slug' => 'public-speaking', 'description' => 'Improve your speaking skills'],
            ['name' => 'Spirituality', 'slug' => 'spirituality', 'description' => 'Yoga, meditation, and peace'],
        ];        

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}