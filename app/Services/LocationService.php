<?php

namespace App\Services;

use App\Models\Location;
use App\Models\UserLocation;
use App\Traits\APIResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationService
{
    use APIResponseTrait;

    private function locationQuery()
    {
        return Location::where('Status', '=', 'Y');
    }

    public function location()
    {
        $data = $this->locationQuery()->get();
        return $this->successResponse($data, '');
    }


    public function locationInfoByID($id)
    {

        return $this->locationQuery()->where('LocationCode', '=', $id);
    }

    public function locationIndex(Request $request)
    {
        $take   = $request->take;
        $search = $request->search;

        $query = $this->locationQuery()->where(function ($q) use ($search) {
            $q->where('LocationName', 'like', '%' . $search . '%');
        });

        if ($request->type === 'export') {
            $loc = $query->get();
        } else {
            $loc = $query->paginate($take);
        }

        return response()->json($loc);
    }

    public function createLocation($data)
    {
        return Location::create([
            'LocationName' => $data['locationName'],
            'LocationShortName' => $data['locationShortName'],
            'Status'       => 'Y',
        ]);
    }

    public function updateLocation($id, $data)
    {
        $location = $this->locationInfoByID($id)->first();
        $location->update([
            'LocationName' => $data['locationName'],
            'LocationShortName' => $data['locationShortName'],
            'Status'       => $data['status'] ?? 'Y',
        ]);
        return $location;
    }

    public function userLocation($userID)
    {
        $data = UserLocation::select('UserLocation.LocationCode', 'l.LocationName', 'l.LocationShortName', 'l.Status')
            ->join('UserManager as m', 'm.UserID', '=', 'UserLocation.UserID')
            ->join('Location as l', 'l.LocationCode', '=', 'UserLocation.LocationCode')
            ->where('UserLocation.UserID', '=', $userID)
            ->where('l.Status', '=', 'Y')
            ->get();

        if ($data->isNotEmpty()) {
            return $this->successResponse($data, '');
        } else {
            return $this->errorResponse('No data found!');
        }
    }
}