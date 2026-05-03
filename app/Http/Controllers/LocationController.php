<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationStoreRequest;
use App\Models\Location;
use App\Services\LocationService;
use App\Traits\APIResponseTrait;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    protected $locationService;
    use APIResponseTrait;
    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function Index(Request $request){
        return $this->locationService->locationIndex($request);
    }
    public function store(LocationStoreRequest $request)
    {
        $locationCode = $request->locationCode ?? null;
        $exist = $this->locationService->locationInfoByID($locationCode)->first();

        if ($exist) {

            $updated = $this->locationService->updateLocation($locationCode, $request->all());

            return $this->successResponseWeb('', 'Location updated successfully',200);
        } else {
            $created = $this->locationService->createLocation($request->all());
            return $this->successResponseWeb('', 'Location created successfully',200);
        }
    }

    public function getLocationInfo($locationCode){

        return $this->locationService->locationInfoByID($locationCode)->first();
    }


}
