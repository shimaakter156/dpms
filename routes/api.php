<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SRController;
use App\Http\Controllers\Web\DoctorController;
use App\Http\Controllers\Web\LookupController;
use App\Http\Controllers\Web\MedicineController;
use App\Http\Controllers\Web\PatientController;
use App\Http\Controllers\Web\PrescriptionController;
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
    Route::get ('lookup/all',           [LookupController::class, 'all']);
    Route::get ('lookup/{type}',        [LookupController::class, 'search']);
    Route::post('lookup/{type}',        [LookupController::class, 'quickAdd']);

    // Patients
    Route::get ('patients/search',          [PatientController::class, 'search']);
    Route::get ('patients/list/{doctorId}', [PatientController::class, 'listForDoctor']);
    Route::get ('patients/{id}',            [PatientController::class, 'show']);

    // Medicines
    Route::get ('medicines/search', [MedicineController::class, 'search']);

    // Prescriptions
    Route::get ('prescriptions/last-by-patient/{patientId}', [PrescriptionController::class, 'lastByPatient']);
    Route::get ('prescriptions/{id}',                        [PrescriptionController::class, 'show']);
    Route::post('prescriptions',                             [PrescriptionController::class, 'save']);
    Route::post('prescriptions/{id}',                        [PrescriptionController::class, 'save']);


});

