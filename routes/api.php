<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SRController;
use App\Http\Controllers\Web\DoctorController;
use App\Http\Controllers\Web\PatientController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login']);
Route::group(['middleware' => 'jwt:api'], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
    Route::get('get-menu', [SettingController::class, 'getMenu']);
    Route::get('app-supporting-data', [SettingController::class, 'appSupportingData']);
    Route::get('get-location-list',[MobileApiController::class,'getUserLocation']);
    Route::get('get-user-location-list/{userId}',[MobileApiController::class,'getUserLocation']);
    Route::get('get-product-list',[MobileApiController::class,'getProduct']);
    Route::post('store-market-price',[MobileApiController::class,'store']);
    Route::get('get-wholesale-market-price-list',[MobileApiController::class,'getMarketPrice']);

});

Route::group(['middleware' => ['jwt:api']], function () {
    Route::get('encoded-result/{param}',[CommonController::class,'encode']);
    Route::get('decoded-result/{param}',[CommonController::class,'decode']);


    Route::group(['prefix' => 'doctor'],function () {
        Route::get('info', [DoctorController::class, 'doctorInfo']);
    });
    Route::group(['prefix' => 'patients'],function () {
        Route::get('info/{patientCode}', [PatientController::class, 'patientInfoByID']);
        Route::get('list/{doctorID}', [PatientController::class, 'list']);

    });
    Route::group(['prefix' => 'user'],function () {
        Route::get('info', [DoctorController::class, 'doctorInfo']);
        Route::post('update', [UserController::class, 'update']);
        Route::post('list', [UserController::class, 'index']);

        Route::get('modal',[CommonController::class,'userModalData']);
        Route::get('get-user-info/{staffId}',[UserController::class,'getUserInfo']);
        Route::post('reset-password',[UserController::class,'updatePassword']);
        Route::post('password-change',[UserController::class,'passwordChange']);
    });

    Route::group(['prefix' => 'report'],function () {
        Route::get('market-rate', [ReportController::class, 'marketRateReport']);
        Route::get('market-rate-pivot', [ReportController::class, 'marketRatePivotReport']);

    });

    Route::group(['prefix'=>'product'],function (){
        Route::post('list',[ProductController::class,'index']);
        Route::post('market-price-list',[ProductController::class,'marketPriceIndex']);
        Route::post('wholesale-market-price-list',[ProductController::class,'wholesaleMarketPriceIndex']);

    });

    Route::group(['prefix'=>'setup'],function (){
       Route::post('sr-info',[SRController::class,'Index']);
       Route::post('location-list',[LocationController::class,'Index']);
       Route::get('get-location-info/{locationCode}',[LocationController::class,'getLocationInfo']);
       Route::post('store-location',[LocationController::class,'store']);

       Route::get('get-product-info/{prodCode}',[ProductController::class,'getProductInfo']);
        Route::post('store-product',[ProductController::class,'store']);
    });

//    Route::get('/send-test-email', function () {
//        $toEmail = 'rabiul@aci-bd.com';
//
//        try {
//            Mail::to($toEmail)->send(new ApprovedEmail('AMS advance approval request.', [
//                'title' => 'Advance Approval Request',
//                'msg' => 'A new request is waiting for your approval.',
//                'link' => 'my-approvals',
//                'linkName' => 'My Approvals',
//                'linkShow' => true
//            ]));
//
//            Log::info('Test email sent successfully to: ' . $toEmail);
//            return 'Test email sent successfully!';
//        } catch (\Exception $e) {
//            Log::error('Failed to send test email to: ' . $toEmail, [
//                'error' => $e->getMessage(),
//                'trace' => $e->getTraceAsString()
//            ]);
//
//            return response()->json([
//                'status' => 'error',
//                'message' => 'Failed to send email',
//                'error' => $e->getMessage()
//            ], 500);
//        }
//    });


});

//Route::get('test-mail/{email}',[ReportController::class,'sendPHPMailerEmail']);