<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Media;
use Illuminate\Support\Str;

class MediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $urls = [
            'http://192.168.29.153:9000/weelp-media/media/vR2h5SE1Lnd7FXesz118ATQiT8zgMiAO17rjvyDK.jpg',
            'http://localhost:9000/weelp-media/media/nIwFNdhcdSR3ZmF604DHgMco6SmU1yt0rbV6WZf3.jpg',
            'http://localhost:9000/weelp-media/media/7RQzvyQSHrm9SR0tEUHyngNTYxJ8h7QH2tNVEydl.jpg',
            'http://localhost:9000/weelp-media/media/TbMSeYAjpVxZyzXJiR7OaCcu0uEsrCjCxlSVpZDv.jpg',
            'http://localhost:9000/weelp-media/media/6xs2MO3Q3OgN77qkERyhICwAq8CQ6loPAvkRRBqQ.jpg',
            'http://localhost:9000/weelp-media/media/MDLptl1a9zByONJFWh4q37bb8N0ToY8wZpzrhQac.jpg',
        ];

        foreach (array_slice($urls, 0, 5) as $url) {
            Media::create([
                'name'     => 'Media ' . Str::random(5),
                'alt_text' => 'Alt ' . Str::random(5),
                'url'      => $url,
            ]);
        }
    }
}
