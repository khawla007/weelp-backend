<?php

// Admin
use Illuminate\Support\Facades\Route;
use Stevebauman\Location\Facades\Location;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StripeController;

use App\Http\Controllers\Admin\UserController;
// use App\Http\Controllers\Admin\UserProfileController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\AttributeController;

use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CountryImportController;

use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\StateImportController;

use App\Http\Controllers\Admin\CityImportController;

use App\Http\Controllers\Admin\PlaceController;
use App\Http\Controllers\Admin\PlaceImportController;
use App\Http\Controllers\Admin\RegionController;

use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ItineraryController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\AddonController;
use App\Http\Controllers\Admin\DashboardController;

// Public
use App\Http\Controllers\Guest\PublicMenuController;
use App\Http\Controllers\Guest\PublicRegionController;
use App\Http\Controllers\Guest\PublicCountryController;
use App\Http\Controllers\Guest\PublicStateController;
use App\Http\Controllers\Guest\PublicCitiesController;
use App\Http\Controllers\Guest\PublicPlaceController;
use App\Http\Controllers\Guest\PublicActivityController;
use App\Http\Controllers\Guest\PublicItineraryController;
use App\Http\Controllers\Guest\PublicPackageController;
use App\Http\Controllers\Guest\PublicTransferController;
use App\Http\Controllers\Guest\PublicHomeSearchController;
use App\Http\Controllers\Guest\PublicShopController;
use App\Http\Controllers\Guest\PublicCategoryController;
use App\Http\Controllers\Guest\PublicTagController;
use App\Http\Controllers\Guest\PublicFilterController;
use App\Http\Controllers\Guest\PublicBlogController;
use App\Http\Controllers\Guest\PublicReviewController;
use App\Http\Controllers\Guest\OtpController;

Route::get('/test', function () {
    return response()->json(['message' => 'Route Working!']);
});

Route::post('/register', [AuthController::class, 'register']);

Route::post('/send-otp', [OtpController::class, 'sendOtp']);
Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);

Route::get('/verify-email', [AuthController::class, 'verifyEmail']);

Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Route::middleware('auth:api')->group(function () {
Route::middleware(['auth:api', 'customer'])->prefix('customer')->group(function () {
    // Route::get('/getuserdetails', [AuthController::class, 'getUserDetails']);
    // Route::get('/user', [UserController::class, 'getUser']);
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);

    // 👇 Logged-in user ke orders
    Route::get('/userorders', [UserProfileController::class, 'getUserOrders']);

    Route::prefix('review')->group(function () {
        Route::get('/', [UserProfileController::class, 'reviewIndex']);
        Route::post('/', [UserProfileController::class, 'reviewStore']);
        Route::get('{id}', [UserProfileController::class, 'reviewShow']);
        Route::post('{id}', [UserProfileController::class, 'reviewUpdate']);
        Route::delete('{id}', [UserProfileController::class, 'reviewDelete']);
    });
});

// Stripe Payment api
// Route::post('/create-payment-intent', [StripePaymentController::class, 'createPaymentIntent']);
// Route::post('/create-payment-intent', [StripePaymentController::class, 'bookAndCreatePaymentIntent']);
// Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::post('/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
Route::post('/confirm-payment', [StripeController::class, 'confirmPayment']);

// New striep payment flow self hosted
// Route::post('/stripe/initialize', [StripeController::class, 'initializeCheckout']);
Route::post('/stripe/create-order', [StripeController::class, 'createOrder']);
Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);
Route::get('/order/thankyou', [StripeController::class, 'getOrderByPaymentIntent']);



Route::middleware(['auth:api', 'admin'])->prefix('admin')->group(function () {

    // Dashboard Routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/metrics', [DashboardController::class, 'getMetrics']);
        Route::get('/overview-chart', [DashboardController::class, 'getOverviewChart']);
        Route::get('/recent-sales', [DashboardController::class, 'getRecentSales']);
        Route::get('/search', [DashboardController::class, 'search']);
    });

    // Admin Side Users Routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'getAllUsers']);
        Route::post('/create', [UserController::class, 'createUser']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::put('/update{id}', [UserController::class, 'update']);
        Route::delete('{id}', [UserController::class, 'destroy']);
        Route::post('/bulk-delete', [UserController::class, 'bulkDelete']);
    });

    // Admin Side Category Routes
    Route::apiResource('/categories', CategoryController::class);
    Route::get('/categorylist', [CategoryController::class, 'getCatList']);
    Route::post('/categories/bulk-delete', [CategoryController::class, 'bulkDelete']);

    // Admin Side Acitivty Tag Routes
    Route::apiResource('/tags', TagController::class);
    Route::get('/taglist', [TagController::class, 'getTagList']);
    Route::post('/tags/bulk-delete', [TagController::class, 'bulkDelete']);

    Route::prefix('attributes')->group(function () {
        Route::get('/slug/{slug}', [AttributeController::class, 'getValuesBySlug']);
        Route::get('{id}', [AttributeController::class, 'show']);
        Route::get('/', [AttributeController::class, 'index']);
        Route::post('/', [AttributeController::class, 'store']);
        Route::put('{id}', [AttributeController::class, 'update']);
        Route::delete('{id}', [AttributeController::class, 'destroy']);
        Route::post('/bulk-delete', [AttributeController::class, 'bulkDelete']);
    });


    // Admin Side Destination Countries Routes
    // Route::apiResource('/countries', CountryController::class);



    Route::post('/import-countries', [CountryImportController::class, 'import']);
    Route::post('/import-states', [StateImportController::class, 'import']);
    Route::post('/import-cities', [CityImportController::class, 'import']);
    Route::post('/import-places', [PlaceImportController::class, 'import']);

    Route::get('/destinations/counts', [CountryController::class, 'getDestinationsCounts']);

    Route::prefix('/countries')->group(function () {
        Route::get('/', [CountryController::class, 'index']);
        Route::get('/list', [CountryController::class, 'countryList']);
        Route::get('{id}', [CountryController::class, 'show']);
        Route::post('/', [CountryController::class, 'store']);
        Route::put('{id}', [CountryController::class, 'update']);
        Route::post('{id}/partial-remove', [CountryController::class, 'partialRemove']);
        Route::delete('{id}', [CountryController::class, 'destroy']);
        Route::post('/bulk-delete', [CountryController::class, 'bulkDelete']);
    });

    Route::prefix('/states')->group(function () {
        Route::get('/', [StateController::class, 'index']);
        Route::get('/list', [StateController::class, 'stateList']);
        Route::get('{id}', [StateController::class, 'show']);
        Route::post('/', [StateController::class, 'store']);
        Route::put('{id}', [StateController::class, 'update']);
        Route::post('{id}/partial-remove', [StateController::class, 'partialRemove']);
        Route::delete('{id}', [StateController::class, 'destroy']);
        Route::post('/bulk-delete', [StateController::class, 'bulkDelete']);
    });
    
    Route::prefix('/cities')->group(function () {
        Route::get('/', [CityController::class, 'index']);
        Route::get('/list', [CityController::class, 'cityList']);
        Route::get('{id}', [CityController::class, 'show']);
        Route::post('/', [CityController::class, 'store']);
        Route::put('{id}', [CityController::class, 'update']);
        Route::post('{id}/partial-remove', [CityController::class, 'partialRemove']);
        Route::delete('{id}', [CityController::class, 'destroy']);
        Route::post('/bulk-delete', [CityController::class, 'bulkDelete']);
    });

    Route::prefix('/places')->group(function () {
        Route::get('/', [PlaceController::class, 'index']);
        Route::get('/list', [PlaceController::class, 'placeList']);
        Route::get('{id}', [PlaceController::class, 'show']);
        Route::post('/', [PlaceController::class, 'store']);
        Route::put('{id}', [PlaceController::class, 'update']);
        Route::post('{id}/partial-remove', [PlaceController::class, 'partialRemove']);
        Route::delete('{id}', [PlaceController::class, 'destroy']);
        Route::post('/bulk-delete', [PlaceController::class, 'bulkDelete']);
    });

    Route::prefix('/regions')->group(function () {
        Route::get('/', [RegionController::class, 'index']);
        Route::get('/list', [RegionController::class, 'regionList']);
        Route::get('{id}', [RegionController::class, 'show']);
        Route::post('/', [RegionController::class, 'store']);
        Route::put('{id}', [RegionController::class, 'update']);
        Route::delete('{id}', [RegionController::class, 'destroy']);
    });

    // Admin Side media route
    Route::prefix('media')->group(function () {
        Route::get('/', [MediaController::class, 'index']);
        Route::get('{id}', [MediaController::class, 'show']);
        Route::post('/store', [MediaController::class, 'store']);
        Route::put('/update/{id}', [MediaController::class, 'update']);
        Route::delete('/delete/{id}', [MediaController::class, 'destroy']);
        Route::post('/bulk-delete', [MediaController::class, 'bulkDestroy']);
    });

    // Admin Side vendors route
    Route::prefix('vendors')->group(function () {
        Route::get('', [VendorController::class, 'index']);      // List vendors
        Route::get('vendor-select', [VendorController::class, 'getVendorsForSelect']);
        Route::get('{vendor}/routes', [VendorController::class, 'getRoutes']);
        Route::get('{vendor}/routes-select', [VendorController::class, 'getRoutesForSelect']);

        Route::get('{vendor}/pricing-tiers', [VendorController::class, 'getPricingTiers']);
        Route::get('{vendor}/pricing-tiers-select', [VendorController::class, 'getPricingTiersForSelect']);


        Route::get('{vendor}/vehicles', [VendorController::class, 'getVehicles']);
        Route::get('{vendor}/vehiclesdropdown', [VendorController::class, 'getVehiclesfordropdown']);
        Route::get('{vendor}/drivers', [VendorController::class, 'getDrivers']);
        Route::get('{vendor}/driversforselect', [VendorController::class, 'getDriversForSchedule']);
        Route::get('{vendor}/schedules', [VendorController::class, 'getSchedules']);


        Route::get('{vendor}/availability-time-slots', [VendorController::class, 'getAvailabilityTimeSlots']);
        Route::get('{vendor}/availability-time-slots-select', [VendorController::class, 'getAvailabilityTimeSlotsForSelect']);

        Route::get('{id}', [VendorController::class, 'show']); // Show a vendor

        Route::delete('{id}', [VendorController::class, 'destroy']);  // Delete vendor
        Route::post('/store/{request_type}', [VendorController::class, 'store']);
        Route::put('/update/{request_type}/{id}', [VendorController::class, 'update']);
        // Route::delete('/relation/{request-type}{id}', [VendorController::class, 'destroy']);
    });
    Route::get('/drivers/{driver}/schedules', [VendorController::class, 'getDriverSchedules']);

    // Admin Side Transfer route
    Route::prefix('/transfers')->group(function () {
        Route::post('/', [TransferController::class, 'store']); // Create
        Route::put('{id}', [TransferController::class, 'update']); // Update
        Route::get('/', [TransferController::class, 'index']);
        Route::get('{id}', [TransferController::class, 'show']);
        Route::delete('{id}', [TransferController::class, 'destroy']);
        Route::post('/bulk-delete', [TransferController::class, 'destroyMultiple']);
    });

    // Admin Side activity route
    // Route::apiResource('activities', ActivityController::class);
    Route::prefix('/activities')->group(function () {
        Route::post('/', [ActivityController::class, 'store']); // Create
        Route::put('{id}', [ActivityController::class, 'update']); // Update
        Route::patch('{id}', [ActivityController::class, 'update']); // Partial Update
        Route::get('/', [ActivityController::class, 'index']); // Get all
        Route::get('{id}', [ActivityController::class, 'show']); // Get single
        Route::delete('{id}', [ActivityController::class, 'destroy']); // Delete
        Route::delete('{id}/partial-delete', [ActivityController::class, 'partialDelete']); //partialDelete
        Route::post('/bulk-delete', [ActivityController::class, 'bulkDestroy']);
    });

    // Admin Side Itinerary route
    // Route::apiResource('itineraries', ItineraryController::class);
    Route::prefix('/itineraries')->group(function () {
        Route::post('/', [ItineraryController::class, 'store']); // Create
        Route::put('{id}', [ItineraryController::class, 'update']); // Update
        Route::patch('{id}', [ItineraryController::class, 'update']); // Partial Update
        Route::get('/', [ItineraryController::class, 'index']); // Get all
        Route::get('{id}', [ItineraryController::class, 'show']); // Get single
        Route::delete('{id}', [ItineraryController::class, 'destroy']); // Delete
        Route::delete('{id}/partial-delete', [ItineraryController::class, 'partialDelete']); //partialDelete
        Route::post('/bulk-delete', [ItineraryController::class, 'bulkDestroy']);
    });

    // Admin Side Package route
    // Route::apiResource('packages', PackageController::class);
    Route::prefix('/packages')->group(function () {
        Route::post('/', [PackageController::class, 'store']); // Create
        Route::put('{id}', [PackageController::class, 'update']); // Update
        Route::patch('{id}', [PackageController::class, 'update']); // Partial Update
        Route::get('/', [PackageController::class, 'index']); // Get all
        Route::get('{id}', [PackageController::class, 'show']); // Get single
        Route::delete('{id}', [PackageController::class, 'destroy']); // Delete
        Route::delete('{id}/partial-delete', [PackageController::class, 'partialDelete']); //partialDelete
        Route::post('/bulk-delete', [PackageController::class, 'bulkDestroy']);
    });



    // Admin Side Order Create Update Delete route
    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/', [OrderController::class, 'index']);
        Route::get('{id}', [OrderController::class, 'show']);
        Route::put('{id}', [OrderController::class, 'updateOrder']);
        Route::delete('{id}', [OrderController::class, 'destroy']);
    });

    Route::prefix('blogs')->group(function () {
        Route::get('/', [BlogController::class, 'index']); // List all blogs
        Route::get('{id}', [BlogController::class, 'show']); // Show a single blog
        Route::post('/', [BlogController::class, 'store']); // Store a new blog
        Route::put('{id}', [BlogController::class, 'update']); // Update an existing blog
        Route::delete('{id}', [BlogController::class, 'destroy']); // Delete a blog
        Route::post('/bulk-delete', [BlogController::class, 'bulkDestroy']);
    });

    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']); // सब reviews की list
        Route::get('/items', [ReviewController::class, 'getItemsByType']);
        Route::post('/', [ReviewController::class, 'store']); // नया review create
        Route::get('{id}', [ReviewController::class, 'show']); // single review detail
        Route::put('{id}', [ReviewController::class, 'update']); // review update
        Route::delete('{id}', [ReviewController::class, 'destroy']); // review delete
        Route::post('/bulk-delete', [ReviewController::class, 'bulkDelete']);
    });

    Route::prefix('addons')->group(function () {
        Route::get('/', [AddonController::class, 'index']);
        Route::get('/list-addon', [AddonController::class, 'dropdownAddon']);
        // Type-specific addon endpoints
        Route::get('/list-activity-addons', [AddonController::class, 'listActivityAddons']);
        Route::get('/list-itinerary-addons', [AddonController::class, 'listItineraryAddons']);
        Route::get('/list-package-addons', [AddonController::class, 'listPackageAddons']);
        Route::get('/list-transfer-addons', [AddonController::class, 'listTransferAddons']);
        Route::post('/', [AddonController::class, 'store']); // नया review create
        Route::get('{id}', [AddonController::class, 'show']); // single review detail
        Route::put('{id}', [AddonController::class, 'update']); // review update
        Route::delete('{id}', [AddonController::class, 'destroy']); // review delete
        Route::post('/bulk-delete', [AddonController::class, 'bulkDelete']);
    });

});

// *****************************************************************************************************************
// Public-------------------------API___________________Public API_________________Public -----------------------API

// *****************************************************************************************************************

// ____Menu Api
Route::prefix('menu')->group(function () {
    Route::get('/{region_slug}', [PublicMenuController::class, 'getAllRegionsWithCities']);
});

Route::get('/categories', [PublicCategoryController::class, 'getAllCategories']);
Route::get('/tags', [PublicTagController::class, 'getAllTags']);

Route::prefix('region')->group(function () {
    Route::get('/', [PublicRegionController::class, 'getRegions']);
    Route::get('/{slug}', [PublicRegionController::class, 'getRegionDetails']);
    Route::get('/{region_slug}/cities', [PublicRegionController::class, 'getCitiesByRegion']);
    Route::get('/{region_slug}/region-packages', [PublicRegionController::class, 'getPackagesByRegion']);

    Route::get('/{region_slug}/region-all-items', [PublicRegionController::class, 'getAllItemsByRegion']);
});

// get all cities with pagination
Route::get('/cities', [PublicCitiesController::class, 'index']);

// get all featured Cities for home page
Route::get('/featured-cities', [PublicCitiesController::class, 'getFeaturedCities']);

// get single city page by slug
Route::get('/city/{slug}', [PublicCitiesController::class, 'getCityDetails']);

// getting all items by city (filter section)
Route::get('/cities/{city_slug}/all-items', [PublicCitiesController::class, 'getAllItemsByCity']);

// activity api
Route::prefix('activities')->group(function () {
    Route::get('/', [PublicActivityController::class, 'getActivities']);
    Route::get('/featured-activities', [PublicActivityController::class, 'getFeaturedActivities']);
    Route::get('/{activity_slug}', [PublicActivityController::class, 'getActivityBySlug']);
});

// transfer api
Route::prefix('transfers')->group(function () {
    Route::get('/', [PublicTransferController::class, 'index']);
    Route::get('{id}', [PublicTransferController::class, 'show']);
});

// itineraries api
Route::prefix('itineraries')->group(function () {
    Route::get('/', [PublicItineraryController::class, 'index']);
    Route::get('/featured-itineraries', [PublicItineraryController::class, 'getFeaturedItineraries']);
    Route::get('/{slug}', [PublicItineraryController::class, 'show']);
});

// Packages api
Route::prefix('packages')->group(function () {
    Route::get('/', [PublicPackageController::class, 'index']);
    Route::get('/featured-packages', [PublicPackageController::class, 'getFeaturedPackages']);
    Route::get('/{slug}', [PublicPackageController::class, 'show']);
});

// Search API
Route::get('/regions-cities', [PublicHomeSearchController::class, 'getRegionsAndCities']);
Route::get('/homesearch', [PublicHomeSearchController::class, 'homeSearch']);

// Filter API
Route::get('/filter', [PublicFilterController::class, 'filter']);

// Shop Page all items API
Route::get('/shop', [PublicShopController::class, 'index']);

Route::get('blogs', [PublicBlogController::class, 'index']);
Route::get('blogs/{slug}', [PublicBlogController::class, 'show']);

// Public reviews api
Route::prefix('reviews')->group(function () {
    Route::get('/', [PublicReviewController::class, 'index']);
    Route::get('/featured-reviews', [PublicReviewController::class, 'getFeaturedReviews']);
    Route::get('/activity/{activity_slug}', [PublicReviewController::class, 'getActivityReviews']);
    Route::get('/activity/{activity_slug}/featured', [PublicReviewController::class, 'getActivityFeaturedReviews']);
});
