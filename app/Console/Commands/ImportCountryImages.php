<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Media;
use App\Models\CountryMediaGallery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportCountryImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'countries:import-images
                            {--country= : Specific country ID or name to import images for}
                            {--source=pexels : Image source API (pexels, unsplash, pixabay)}
                            {--count=5 : Number of images to download per country}
                            {--force : Overwrite existing images}
                            {----dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import country images from external APIs (Pexels, Unsplash, Pixabay) and upload to MinIO';

    /**
     * Image sources configuration.
     *
     * @var array
     */
    protected array $sources;

    /**
     * Search query templates.
     *
     * @var array
     */
    protected array $searchQueries;

    /**
     * Statistics for the import.
     *
     * @var array
     */
    protected array $stats = [
        'countries_processed' => 0,
        'images_downloaded' => 0,
        'images_uploaded' => 0,
        'media_created' => 0,
        'gallery_created' => 0,
        'errors' => 0,
    ];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Country Images Import ===');
        $this->newLine();

        // Load configuration
        $this->loadConfiguration();

        // Parse options
        $source = $this->option('source') ?: 'pexels';
        $count = (int) $this->option('count') ?: 5;
        $force = $this->option('force') ?: false;
        $dryRun = $this->option('dry-run') ?: false;

        // Validate source
        if (!$this->isValidSource($source)) {
            $this->error("Invalid source: {$source}");
            $this->error("Available sources: " . implode(', ', array_keys($this->sources)));
            return 1;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get countries to process
        $countries = $this->getCountries();

        if ($countries->isEmpty()) {
            $this->warn('No countries found to process.');
            return 0;
        }

        $this->info("Found {$countries->count()} countries to process");
        $this->newLine();

        // Process each country
        $this->processCountries($countries, $source, $count, $force, $dryRun);

        // Show results
        $this->showResults();

        return 0;
    }

    /**
     * Load configuration from config file.
     */
    protected function loadConfiguration(): void
    {
        $config = config('country-images');

        $this->sources = $config['sources'] ?? [];
        $this->searchQueries = $config['search_queries'] ?? [
            'landmarks' => '{country} landmarks famous buildings',
            'travel' => '{country} travel tourism',
            'skyline' => '{capital} skyline cityscape',
            'culture' => '{country} culture tradition',
            'food' => '{famous_dish} food cuisine',
        ];
    }

    /**
     * Check if source is valid and enabled.
     */
    protected function isValidSource(string $source): bool
    {
        return isset($this->sources[$source]) && ($this->sources[$source]['enabled'] ?? false);
    }

    /**
     * Get countries to process.
     */
    protected function getCountries()
    {
        $countryFilter = $this->option('country');

        $query = Country::with('locationDetails');

        if ($countryFilter) {
            // Filter by ID or name
            if (is_numeric($countryFilter)) {
                $query->where('id', $countryFilter);
            } else {
                $query->where('name', 'like', "%{$countryFilter}%");
            }
        }

        return $query->get();
    }

    /**
     * Process countries and import images.
     */
    protected function processCountries($countries, string $source, int $count, bool $force, bool $dryRun): void
    {
        $bar = $this->output->createProgressBar($countries->count());
        $bar->start();

        foreach ($countries as $country) {
            try {
                $this->processCountry($country, $source, $count, $force, $dryRun);
                $this->stats['countries_processed']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                if ($this->getVerbosity() >= 2) { // verbose
                    $this->newLine();
                    $this->error("Error processing {$country->name}: {$e->getMessage()}");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Process a single country.
     */
    protected function processCountry(Country $country, string $source, int $count, bool $force, bool $dryRun): void
    {
        // Check if country already has images (unless force)
        if (!$force && !$dryRun) {
            $existingCount = $country->mediaGallery()->count();
            if ($existingCount >= $count) {
                $this->line("  Skipping {$country->name} (already has {$existingCount} images)");
                return;
            }
        }

        if ($dryRun) {
            $this->line("  Would import {$count} images for {$country->name}");
            return;
        }

        // Generate search queries
        $queries = $this->generateSearchQueries($country, $count);

        // Download and process images
        $images = $this->fetchImages($source, $queries);

        if (empty($images)) {
            $this->line("  No images found for {$country->name}");
            return;
        }

        // Process each image
        foreach ($images as $index => $imageUrl) {
            $this->processImage($country, $imageUrl, $index === 0);
        }
    }

    /**
     * Generate search queries for a country.
     */
    protected function generateSearchQueries(Country $country, int $count): array
    {
        $queries = [];
        $details = $country->locationDetails;

        $capital = $details?->capital_city ?? $country->name;
        $famousDish = null;

        if ($details && !empty($details->local_cuisine)) {
            if (is_array($details->local_cuisine)) {
                $famousDish = $details->local_cuisine[0] ?? null;
            } else {
                $famousDish = $details->local_cuisine;
            }
        }

        $replacements = [
            '{country}' => $country->name,
            '{capital}' => $capital,
            '{famous_dish}' => $famousDish ?: 'local',
        ];

        // Generate queries from templates
        foreach ($this->searchQueries as $type => $template) {
            if (count($queries) >= $count) break;

            $query = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $template
            );
            $queries[] = $query;
        }

        // Fill remaining with variations
        while (count($queries) < $count) {
            $queries[] = $country->name . ' beautiful landscape';
        }

        return array_slice($queries, 0, $count);
    }

    /**
     * Fetch images from API.
     */
    protected function fetchImages(string $source, array $queries): array
    {
        $images = [];
        $sourceConfig = $this->sources[$source];

        foreach ($queries as $query) {
            if (count($images) >= count($queries)) break;

            try {
                $response = $this->callApi($source, $sourceConfig, $query);

                if ($response->successful()) {
                    $imageUrls = $this->extractImageUrls($source, $response->json());
                    $images = array_merge($images, $imageUrls);
                }
            } catch (\Exception $e) {
                // Continue to next query on error
                continue;
            }

            // Small delay to avoid rate limiting
            usleep(200000); // 0.2 seconds
        }

        return array_unique(array_slice($images, 0, count($queries)));
    }

    /**
     * Call the API for the given source.
     */
    protected function callApi(string $source, array $config, string $query)
    {
        $url = $config['url'];
        $key = $config['key'] ?? '';

        return match ($source) {
            'pexels' => Http::withHeaders([
                'Authorization' => $key ?: 'free', // Pexels works without key for limited requests
            ])->get($url, [
                'query' => $query,
                'per_page' => 1,
                'orientation' => 'landscape',
            ]),
            'unsplash' => Http::withHeaders([
                'Authorization' => "Client-ID {$key}",
            ])->get($url, [
                'query' => $query,
                'per_page' => 1,
                'orientation' => 'landscape',
            ]),
            'pixabay' => Http::get($url, [
                'key' => $key,
                'q' => $query,
                'per_page' => 1,
                'image_type' => 'photo',
                'orientation' => 'horizontal',
            ]),
            default => Http::get($url, ['query' => $query]),
        };
    }

    /**
     * Extract image URLs from API response.
     */
    protected function extractImageUrls(string $source, ?array $data): array
    {
        if (empty($data)) return [];

        return match ($source) {
            'pexels' => [
                $data['photos'][0]['src']['large'] ?? null,
            ],
            'unsplash' => [
                $data['results'][0]['urls']['regular'] ?? null,
            ],
            'pixabay' => [
                $data['hits'][0]['largeImageURL'] ?? null,
            ],
            default => [],
        };
    }

    /**
     * Process a single image: download, upload, create records.
     */
    protected function processImage(Country $country, string $imageUrl, bool $isFeatured): void
    {
        try {
            // Download image
            $imageData = $this->downloadImage($imageUrl);
            if (!$imageData) {
                return;
            }

            $this->stats['images_downloaded']++;

            // Generate filename
            $filename = $this->generateFilename($country);
            $path = "countries/{$filename}";

            // Upload to MinIO
            $path = Storage::disk('minio')->put($path, $imageData);
            $publicUrl = Storage::disk('minio')->url($path);

            $this->stats['images_uploaded']++;

            // Get image dimensions
            $dimensions = $this->getImageDimensions($imageData);

            // Create Media record
            $media = Media::create([
                'name' => $country->name,
                'alt_text' => "{$country->name} travel image",
                'url' => $publicUrl,
                'file_size' => strlen($imageData),
                'width' => $dimensions['width'] ?? null,
                'height' => $dimensions['height'] ?? null,
            ]);

            $this->stats['media_created']++;

            // Create CountryMediaGallery record
            CountryMediaGallery::create([
                'country_id' => $country->id,
                'media_id' => $media->id,
                'is_featured' => $isFeatured,
            ]);

            $this->stats['gallery_created']++;

        } catch (\Exception $e) {
            $this->stats['errors']++;
            throw $e;
        }
    }

    /**
     * Download image from URL.
     */
    protected function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if ($response->successful() && $response->header('Content-Type')) {
                return $response->body();
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate unique filename for country image.
     */
    protected function generateFilename(Country $country): string
    {
        return Str::slug($country->name) . '-' . Str::random(8) . '.jpg';
    }

    /**
     * Get image dimensions from binary data.
     */
    protected function getImageDimensions(string $imageData): array
    {
        try {
            $imageInfo = getimagesizefromstring($imageData);
            if ($imageInfo) {
                return [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return ['width' => null, 'height' => null];
    }

    /**
     * Show import results.
     */
    protected function showResults(): void
    {
        $this->newLine();
        $this->info('=== Import Results ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Countries Processed', $this->stats['countries_processed']],
                ['Images Downloaded', $this->stats['images_downloaded']],
                ['Images Uploaded to MinIO', $this->stats['images_uploaded']],
                ['Media Records Created', $this->stats['media_created']],
                ['Gallery Records Created', $this->stats['gallery_created']],
                ['Errors', $this->stats['errors']],
            ]
        );

        if ($this->stats['errors'] > 0) {
            $this->warn("Completed with {$this->stats['errors']} errors");
        } else {
            $this->info('Import completed successfully!');
        }
    }
}
