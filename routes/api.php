<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Http\Controllers\API\IVRSubscriptionController;
use App\Http\Controllers\API\UserController;

use App\Http\Controllers\API\AutoDebitSubscriptionController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {
    Route::prefix('ivr')->group(function () {
        Route::post("/subscription", [IVRSubscriptionController::class, 'ivr_subscription'])
            ->name('subscription'); // Example route name

        Route::get("/getPlans", [IVRSubscriptionController::class, 'getPlans'])
            ->name('get_plans'); // Example route name

        Route::post("/getProducts", [IVRSubscriptionController::class, 'getProducts'])
            ->name('get_products'); // Example route name

        // Other routes related to IVR can be added here
    });
});

Route::prefix('v1')->group(function () {
    Route::prefix('auto-debit')->group(function () {
        Route::post("/auto-subscription", [AutoDebitSubscriptionController::class, 'AutoDebitSubscription'])
            ->name('AutoDebitSubscription'); // Example route name
        // Other routes related to IVR can be added here
    });
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post("login",[UserController::class,'index']);

Route::group(['middleware' => 'auth:sanctum'], function(){
    Route::get("v1/takafulplus",[UserController::class,'takafulplus']);
    });



