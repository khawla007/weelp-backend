<?php

// Admin
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AddonController;
use App\Http\Controllers\Admin\AdminLocationSearchController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CityController;
// use App\Http\Controllers\Admin\UserProfileController;
use App\Http\Controllers\Admin\CityImportController;
use App\Http\Controllers\Admin\CountryController;
use App\Http\Controllers\Admin\CountryImportController;
use App\Http\Controllers\Admin\CreatorApplicationManagementController;
use App\Http\Controllers\Admin\CreatorItineraryManagementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ItineraryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PlaceController;
use App\Http\Controllers\Admin\PlaceImportController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\StateController;
use App\Http\Controllers\Admin\StateImportController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\TransferRouteController;
use App\Http\Controllers\Admin\TransferZoneController;
use App\Http\Controllers\Admin\TransferZoneLocationController;
use App\Http\Controllers\Admin\TransferZonePriceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Creator\CreatorApplicationController;
use App\Http\Controllers\Creator\CreatorDashboardController;
use App\Http\Controllers\Creator\CreatorItineraryController;
// Public
use App\Http\Controllers\Creator\CreatorPostController;
use App\Http\Controllers\Customer\CustomerItineraryController;
use App\Http\Controllers\Guest\OtpController;
use App\Http\Controllers\Guest\PublicActivityController;
use App\Http\Controllers\Guest\PublicBlogController;
use App\Http\Controllers\Guest\PublicCategoryController;
use App\Http\Controllers\Guest\PublicCitiesController;
use App\Http\Controllers\Guest\PublicHomeSearchController;
use App\Http\Controllers\Guest\PublicItineraryController;
use App\Http\Controllers\Guest\PublicLocationSearchController;
use App\Http\Controllers\Guest\PublicMenuController;
use App\Http\Controllers\Guest\PublicPackageController;
use App\Http\Controllers\Guest\PublicPostController;
use App\Http\Controllers\Guest\PublicRegionController;
use App\Http\Controllers\Guest\PublicReviewController;
use App\Http\Controllers\Guest\PublicShopController;
use App\Http\Controllers\Guest\PublicTagController;
use App\Http\Controllers\Guest\PublicToursSearchController;
use App\Http\Controllers\Guest\PublicTransferController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;
use Stevebauman\Location\Facades\Location;

// Login - named limiter: 5/min per email+IP and 20/min per IP
Route::middleware('throttle:login')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Registration - moderate rate limit (10 per minute)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
});

// Password reset - strict rate limit (3 per minute)
Route::middleware('throttle:3,1')->group(function () {
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

// OTP - moderate rate limit (5 per minute)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/send-otp', [OtpController::class, 'sendOtp']);
    Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);
});

// Token refresh - relaxed rate limit (10 per minute)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
});

// Username availability - throttle to prevent enumeration
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/check-username', [AuthController::class, 'checkUsername']);
});

// Email verification - named limiter: 5/min per email+IP (or per IP when email absent)
Route::middleware('throttle:verify_email')->group(function () {
    Route::get('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
});

// Role-agnostic authenticated routes - 30/min baseline; avatar upload also capped at 10/min for MinIO write cost
Route::middleware(['auth:api', 'throttle:30,1'])->prefix('user')->group(function () {
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::delete('/avatar', [UserProfileController::class, 'deleteAvatar']);
    Route::middleware('throttle:10,1,avatar')->post('/avatar', [UserProfileController::class, 'uploadAvatar']);
});

// Route::middleware('auth:api')->group(function () {
Route::middleware(['auth:api', 'customer'])->prefix('customer')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Route::get('/getuserdetails', [AuthController::class, 'getUserDetails']);
    // Route::get('/user', [UserController::class, 'getUser']);
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
    Route::put('/password', [UserProfileController::class, 'changePassword']);

    // 👇 Logged-in user ke orders
    Route::get('/userorders', [UserProfileController::class, 'getUserOrders']);

    Route::prefix('review')->group(function () {
        Route::get('/', [UserProfileController::class, 'reviewIndex']);
        Route::post('/', [UserProfileController::class, 'reviewStore']);
        Route::get('{id}', [UserProfileController::class, 'reviewShow']);
        Route::post('{id}', [UserProfileController::class, 'reviewUpdate']);
        Route::delete('{id}', [UserProfileController::class, 'reviewDelete']);
    });

    // DEPRECATED: Replaced by creator itinerary system
    // Route::post('/upgrade-to-creator', [AuthController::class, 'upgradeToCreator']);

    // Creator application
    Route::post('/creator/apply', [CreatorApplicationController::class, 'apply']);
    Route::get('/creator/application-status', [CreatorApplicationController::class, 'status']);

    // Customer Itinerary Submission
    Route::post('/itineraries', [CustomerItineraryController::class, 'store']);
    Route::get('/my-itineraries', [CustomerItineraryController::class, 'myItineraries']);

    // Customer Resources
    Route::get('/cities', [CustomerItineraryController::class, 'getCities']);
    Route::get('/activities', [CustomerItineraryController::class, 'getActivities']);
    Route::get('/transfers', [CustomerItineraryController::class, 'getTransfers']);

    // Customer Itinerary Edit & Save
    Route::get('/itineraries/edit/{slug}', [CustomerItineraryController::class, 'getEditData']);
    Route::post('/itineraries/save', [CustomerItineraryController::class, 'saveCustomized']);
    Route::post('/itineraries/book', [CustomerItineraryController::class, 'bookItinerary']);
});

// Public explore routes - accessible without authentication
Route::prefix('creator')->group(function () {
    Route::get('/explore', [CreatorItineraryController::class, 'exploreIndex']);
    Route::get('/explore/{id}', [CreatorItineraryController::class, 'exploreShow']);
});

// Creator routes - require authentication and creator role
Route::middleware(['auth:api', 'creator'])->prefix('creator')->group(function () {
    // DEPRECATED: Replaced by creator itinerary system
    // Route::prefix('posts')->group(function () {
    //     Route::get('/', [CreatorPostController::class, 'index']);
    //     Route::post('/', [CreatorPostController::class, 'store']);
    //     Route::put('/{id}', [CreatorPostController::class, 'update']);
    //     Route::delete('/{id}', [CreatorPostController::class, 'destroy']);
    // });

    Route::get('/dashboard/stats', [CreatorDashboardController::class, 'stats']);
    Route::get('/completed-bookings', [CreatorDashboardController::class, 'completedBookings']);
    Route::post('/resolve-link', [CreatorDashboardController::class, 'resolveLink']);

    // Creator Itinerary Submission
    Route::post('/itineraries', [CreatorItineraryController::class, 'store']);
    Route::get('/my-itineraries', [CreatorItineraryController::class, 'myItineraries']);

    // Creator Itinerary Edit/Removal Requests
    Route::get('/itineraries/drafts/{id}', [CreatorItineraryController::class, 'getDraft']);
    Route::post('/itineraries/{id}/request-edit', [CreatorItineraryController::class, 'requestEdit']);
    Route::put('/itineraries/drafts/{id}', [CreatorItineraryController::class, 'updateDraft']);
    Route::put('/itineraries/drafts/{id}/submit', [CreatorItineraryController::class, 'submitDraft']);
    Route::post('/itineraries/{id}/request-removal', [CreatorItineraryController::class, 'requestRemoval']);

    // Creator Itineraries - Create new draft from Explore page
    Route::post('/itineraries/create', [CreatorItineraryController::class, 'createDraft']);

    // Creator Itineraries - Explore (write operations require auth)
    Route::post('/explore/{id}/like', [CreatorItineraryController::class, 'toggleLike']);
    Route::post('/explore/{id}/view', [CreatorItineraryController::class, 'recordView']);

    // Creator Resources
    Route::get('/cities', [CreatorItineraryController::class, 'getCities']);
    Route::get('/activities', [CreatorItineraryController::class, 'getActivities']);
    Route::get('/transfers', [CreatorItineraryController::class, 'getTransfers']);

    // Creator Edit
    Route::get('/itineraries/edit/{slug}', [CreatorItineraryController::class, 'getEditData']);
});

// Stripe Payment api
// Route::post('/create-payment-intent', [StripePaymentController::class, 'createPaymentIntent']);
// Route::post('/create-payment-intent', [StripePaymentController::class, 'bookAndCreatePaymentIntent']);
// Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Authenticated payment routes (rate-limited)
Route::middleware(['auth:api', 'throttle:30,1'])->group(function () {
    Route::post('/create-checkout-session', [StripeController::class, 'createCheckoutSession']);
    Route::post('/confirm-payment', [StripeController::class, 'confirmPayment']);
    Route::post('/stripe/create-order', [StripeController::class, 'createOrder']);
    Route::get('/order/thankyou', [StripeController::class, 'getOrderByPaymentIntent']);
});

// Stripe webhook stays PUBLIC — Stripe-Signature header is the auth.
Route::post('/stripe/webhook', [StripeController::class, 'handleWebhook']);

// Admin group - auth + role gate run before throttle so unauth requests bail before counting
Route::middleware(['auth:api', 'admin', 'throttle:60,1'])->prefix('admin')->group(function () {

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
        Route::post('{id}/avatar', [UserController::class, 'uploadUserAvatar']);
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
        Route::get('/by-city/{cityId}', [PlaceController::class, 'placesByCity']);
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

    // Admin Side Transfer Zones
    Route::prefix('/transfer-zones')->group(function () {
        Route::get('/', [TransferZoneController::class, 'index']);
        Route::post('/', [TransferZoneController::class, 'store']);
        Route::post('/bulk-delete', [TransferZoneController::class, 'bulkDelete']);
        Route::get('{id}', [TransferZoneController::class, 'show']);
        Route::put('{id}', [TransferZoneController::class, 'update']);
        Route::delete('{id}', [TransferZoneController::class, 'destroy']);

        // Zone ↔ Locations
        Route::get('{id}/locations', [TransferZoneLocationController::class, 'index']);
        Route::post('{id}/locations/assign', [TransferZoneLocationController::class, 'assign']);
        Route::delete('{id}/locations/unassign', [TransferZoneLocationController::class, 'unassign']);
    });

    // Admin Side Transfer Zone Prices (matrix)
    Route::prefix('/transfer-zone-prices')->group(function () {
        Route::get('/', [TransferZonePriceController::class, 'index']);
        Route::post('/upsert', [TransferZonePriceController::class, 'upsert']);
        Route::post('/bulk-upsert', [TransferZonePriceController::class, 'bulkUpsert']);
    });

    // Admin Side Transfer Routes
    Route::prefix('/transfer-routes')->group(function () {
        Route::get('/dropdown', [TransferRouteController::class, 'dropdown']);
        Route::get('/', [TransferRouteController::class, 'index']);
        Route::post('/', [TransferRouteController::class, 'store']);
        Route::post('/bulk-delete', [TransferRouteController::class, 'bulkDelete']);
        Route::get('{id}', [TransferRouteController::class, 'show']);
        Route::put('{id}', [TransferRouteController::class, 'update']);
        Route::delete('{id}', [TransferRouteController::class, 'destroy']);
        Route::patch('{id}/toggle-status', [TransferRouteController::class, 'toggleStatus']);
        Route::patch('{id}/toggle-popular', [TransferRouteController::class, 'togglePopular']);
    });

    // Admin Side unified Location search (cities + places)
    Route::get('/locations/search', [AdminLocationSearchController::class, 'search']);

    // Admin Side activity route
    // Route::apiResource('activities', ActivityController::class);
    Route::prefix('/activities')->group(function () {
        Route::post('/', [ActivityController::class, 'store']); // Create
        Route::put('{id}', [ActivityController::class, 'update']); // Update
        Route::patch('{id}', [ActivityController::class, 'update']); // Partial Update
        Route::get('/', [ActivityController::class, 'index']); // Get all
        Route::get('{id}', [ActivityController::class, 'show']); // Get single
        Route::delete('{id}', [ActivityController::class, 'destroy']); // Delete
        Route::delete('{id}/partial-delete', [ActivityController::class, 'partialDelete']); // partialDelete
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
        Route::delete('{id}/partial-delete', [ItineraryController::class, 'partialDelete']); // partialDelete
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
        Route::delete('{id}/partial-delete', [PackageController::class, 'partialDelete']); // partialDelete
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

    // Admin Creator Application Management
    Route::prefix('creator-applications')->group(function () {
        Route::get('/', [CreatorApplicationManagementController::class, 'index']);
        Route::get('/{id}', [CreatorApplicationManagementController::class, 'show']);
        Route::put('/{id}', [CreatorApplicationManagementController::class, 'update']);
        Route::delete('/{id}', [CreatorApplicationManagementController::class, 'destroy']);
        Route::put('/{id}/approve', [CreatorApplicationManagementController::class, 'approve']);
        Route::put('/{id}/reject', [CreatorApplicationManagementController::class, 'reject']);
    });

    // Admin Creator Itinerary Management
    Route::prefix('creator-itineraries')->group(function () {
        Route::get('/', [CreatorItineraryManagementController::class, 'index']);
        Route::get('/{id}', [CreatorItineraryManagementController::class, 'show']);
        Route::get('/{id}/original', [CreatorItineraryManagementController::class, 'original']);
        Route::put('/{id}/approve', [CreatorItineraryManagementController::class, 'approve']);
        Route::put('/{id}/update-and-approve', [CreatorItineraryManagementController::class, 'updateAndApprove']);
        Route::put('/{id}/reject', [CreatorItineraryManagementController::class, 'reject']);
        Route::put('/{id}/update', [CreatorItineraryManagementController::class, 'update']);
        Route::delete('/{id}', [CreatorItineraryManagementController::class, 'destroy']);
        Route::put('/{id}/approve-edit', [CreatorItineraryManagementController::class, 'approveEdit']);
        Route::put('/{id}/reject-edit', [CreatorItineraryManagementController::class, 'rejectEdit']);
        Route::put('/{id}/approve-removal', [CreatorItineraryManagementController::class, 'approveRemoval']);
        Route::put('/{id}/reject-removal', [CreatorItineraryManagementController::class, 'rejectRemoval']);
    });

});

// *****************************************************************************************************************
// Public-------------------------API___________________Public API_________________Public -----------------------API

// *****************************************************************************************************************

// ____Menu Api
Route::prefix('menu')->group(function () {
    Route::get('/{region_slug}', [PublicMenuController::class, 'getAllRegionsWithCities']);
});

Route::get('/mega-menu', [PublicMenuController::class, 'getMegaMenuData']);

Route::get('/categories', [PublicCategoryController::class, 'getAllCategories']);
Route::get('/tags', [PublicTagController::class, 'getAllTags']);

Route::prefix('region')->group(function () {
    Route::get('/', [PublicRegionController::class, 'getRegions']);
    Route::get('/{slug}', [PublicRegionController::class, 'getRegionDetails']);
    Route::get('/{region_slug}/cities', [PublicRegionController::class, 'getCitiesByRegion']);
    Route::get('/{region_slug}/region-itineraries', [PublicRegionController::class, 'getItinerariesByRegion']);

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
    Route::get('/{slug}/quote', [PublicActivityController::class, 'quote'])
        ->middleware('throttle:60,1');
    Route::get('/{activity_slug}', [PublicActivityController::class, 'getActivityBySlug']);
});

// transfer api
Route::prefix('transfers')->group(function () {
    Route::get('/', [PublicTransferController::class, 'index']);
    Route::get('{id}', [PublicTransferController::class, 'show']);
});

// Public location search (used by transfers pickup/destination combobox)
Route::get('/public/locations/search', [PublicLocationSearchController::class, 'search']);

// itineraries api
Route::prefix('itineraries')->group(function () {
    Route::get('/', [PublicItineraryController::class, 'index']);
    Route::get('/featured-itineraries', [PublicItineraryController::class, 'getFeaturedItineraries']);
    Route::get('/{slug}', [PublicItineraryController::class, 'show']);
    Route::get('/{slug}/addons', [PublicItineraryController::class, 'getAddons']);
});

// Packages api
Route::prefix('packages')->group(function () {
    Route::get('/', [PublicPackageController::class, 'index']);
    Route::get('/featured-packages', [PublicPackageController::class, 'getFeaturedPackages']);
    Route::get('/{slug}', [PublicPackageController::class, 'show']);
    Route::get('/{slug}/addons', [PublicPackageController::class, 'getAddons']);
});

// Search API
Route::get('/regions-cities', [PublicHomeSearchController::class, 'getRegionsAndCities']);
Route::get('/homesearch', [PublicHomeSearchController::class, 'homeSearch']);
Route::get('/toursearch', [PublicToursSearchController::class, 'search']);

// Featured Cities with Starting Price
Route::get('/featured-cities/with-starting-price', [PublicCitiesController::class, 'getFeaturedCitiesWithStartingPrice']);

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

// DEPRECATED: Replaced by creator itinerary system
// Route::prefix('posts')->group(function () {
//     Route::get('/', [PublicPostController::class, 'index']);
//     Route::get('/{id}', [PublicPostController::class, 'show']);
// });

// DEPRECATED: Replaced by creator itinerary system
// Route::middleware('auth:api')->group(function () {
//     Route::post('/posts/{id}/like', [PublicPostController::class, 'toggleLike']);
//     Route::post('/posts/{id}/share', [PublicPostController::class, 'incrementShare']);
// });

// Notifications (all authenticated users)
Route::middleware('auth:api')->group(function () {
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount']);
    Route::put('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
});
