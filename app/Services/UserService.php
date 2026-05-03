<?php

namespace App\Services;

use App\Models\SubMenuPermission;
use App\Models\User;
use App\Models\UserLocation;
use App\Traits\APIResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserService
{
    use  APIResponseTrait;

    public function userExist($username){
        $user = User::where('UserName', $username)
            ->where('IsActive', 1)
            ->where('IsDeleted', 0)
            ->first();
        return $user;
    }
    public function userIndex(Request $request)
    {
        $take = $request->take;
        $search = $request->search;

        $query = $this->userIndexQuery($search)
            ->where('UserManager.UserTypeID', '!=', '1')
            ->orderBy('StaffID', 'desc')
            ->select('UserManager.*', 'UserType.UserTypeName');

        if ($request->type === 'export') {
            $users = $query->get();
        } else {
            $users = $query->paginate($take);
        }

        $this->userLocationGroupQuery($users);

        return response()->json($users);
    }
    public function srIndex(Request $request)
    {
        $take = $request->take;
        $search = $request->search;

        $query = $this->userIndexQuery($search)
            ->where('UserManager.UserTypeID', '=', '3')
            ->orderBy('StaffID', 'desc')
            ->select('UserManager.UserID','UserManager.StaffID','UserManager.Name','UserManager.Status');

        if ($request->type === 'export') {
            $users = $query->get();
        } else {
            $users = $query->paginate($take);
        }

        $this->userLocationGroupQuery($users);

        return response()->json($users);
    }

    public function userIndexQuery($search){
        return User::join('UserType', 'UserType.UserTypeID', 'UserManager.UserTypeID')
            ->where(function ($q) use ($search) {
                $q->where('Name', 'like', '%' . $search . '%');
                $q->orWhere('StaffID', 'like', '%' . $search . '%');
                $q->orWhere('Email', 'like', '%' . $search . '%');
                $q->orWhere('PhoneNo', 'like', '%' . $search . '%');
            });

    }
    public function userLocationGroupQuery($users){
        $users->getCollection()->transform(function ($user) {
            $data = DB::table('UserLocation')
                ->join('Location', 'Location.LocationCode', '=', 'UserLocation.LocationCode')
                ->where('UserLocation.UserID', $user->UserID)
                ->select('Location.LocationCode', 'Location.LocationName')
                ->get();

            $user->locations = implode(',', $data->pluck('LocationName')->toArray());

            return $user;   });

    }

    public function storeUser(Request $request,$newUserID,$userTypeID){
        $selectedSubMenu =$request->selectedSubMenu;

        $user = new User();
        $user->UserID = $newUserID;
        $user->StaffID = $request->staffId;
        $user->Name = $request->staffName;
        $user->Email = $request->email;
        $user->PhoneNo = $request->mobile;
        $user->Password = bcrypt($request->password);
        $user->Status = $request->status;
        $user->UserTypeID = $userTypeID;
        $user->CreatedBy = Auth::user()->UserID;
        $user->CreatedAt = Carbon::now()->format('Y-m-d H:i:s');
        $user->save();
        if ($selectedSubMenu){
            $this->insertUserSubmenu($selectedSubMenu,$newUserID);
        }
        return $user;

    }
    public function insertUserLocation($location, $newUserID) {
        $userLocations = array_map(function ($loc) use ($newUserID) {
            return [
                'UserID' => $newUserID,
                'LocationCode' => $loc['LocationCode']
            ];
        }, $location);

        UserLocation::insert($userLocations);
    }

    public function insertUserSubmenu($selectedSubMenu,$newUserID){
        $submenus = [];
        foreach ($selectedSubMenu as $row) {
            $submenus[] = [
                'UserID' => $newUserID,
                'SubMenuID' => $row
            ];
        }
        SubMenuPermission::insert($submenus);
    }

//    public function userExist($staffID,$userTypeID){
//        if (User::where('StaffID', $staffID)->where('UserTypeID','=',$userTypeID)->exists()) {
//            throw new \Exception('User already exists.');
//        }
//    }

    public function generateUserID($userTypeID,$staffID){

        if ($userTypeID==='3'){
            $user =  User::where('UserTypeID','=',$userTypeID)->orderby('UserID','DESC')->first();
            $prev = $user->UserID;
            $nextUserID = 'SR'.(explode('SR',$prev)[1]+1);
        }else{
            $nextUserID = $staffID;
        }

        return $nextUserID;
    }


    public function updateUser($request, $userTypeID)
    {
        return User::where('UserID', $request->userID)->where('UserTypeID','=',$userTypeID)->update([
            'StaffID'   => $request->staffId,
            'Name'   => $request->staffName,
            'Email'   => $request->email,
            'PhoneNo' => $request->mobile,
            'Status'  => $request->status,
            'UserTypeID' => $userTypeID,
        ]);
    }
    public function deleteUserLocation($userID)
    {
        UserLocation::where('UserID', $userID)->delete();
    }
    public function deleteUserSubmenu($userID)
    {
        SubMenuPermission::where('UserID', $userID)->delete();
    }
}