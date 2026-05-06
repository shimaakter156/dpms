<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMarketPriceRequest;
use App\Models\Login\LoginLog;
use App\Models\User;
use App\Services\LoginService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected $loginService , $userService;

    public function __construct( LoginService  $loginService , UserService $userService){
        $this->loginService = $loginService ;
        $this->userService = $userService ;
    }
    public function login(Request $request)
    {
        $this->loginService->checkValidation($request);

        $username = $request->username;
        $validErr = 'Invalid credentials';

        $user = $this->userService->userExist($username);

        if (!$user) {
            $this->loginService->logFailure($request, $validErr);
            return response()->json(['message' => $validErr], 401);
        }

        $this->loginService->lockCheck($user);

        $hashedCheck = $this->loginService->hashCheck($request, $user);
        if (!$hashedCheck){
            $this->loginService->handleFailedLogin($user);
            $this->loginService->logFailure($request, 'Invalid credentials');

            return response()->json([
                'message' => 'Invalid credentials',
                'attempts_left' => 5 - $user->FailedLoginCount
            ], 401);
        }

        $token = JWTAuth::fromUser($user);

        if (!$token) {
            return response()->json(['message' => 'Could not create token'], 500);
        }

        $this->loginService->resetLoginAttempts($user);
        $this->loginService->logSuccess($request, $user);

        return $this->respondWithToken($token);
    }


    public function changePass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'oldPass' => 'required',
            'newPass' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid'], 400);
        }
        $authUser = JWTAuth::parseToken()->authenticate();

        $check = Hash::check($request->oldPass, $authUser->Password);
        if ($check) {
            $user = User::find($authUser->UserID);
            $user->Password = bcrypt($request->newPass);
            $user->save();
            return response()->json(['message' => "Password changed successfully"]);
        } else {
            return response()->json(['message' => "Your current password is Invalid"], 400);
        }
    }

    public function changeProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'mobile' => 'required',
            'image' => 'required',
            'isImageChange' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid'], 400);
        }
        $authUser = JWTAuth::parseToken()->authenticate();
        $user = User::find($authUser->UserID);
        $user->UserName = $request->name;
        $user->Email = $request->email;
        $user->Phone = $request->mobile;
        if ($request->isImageChange) {
            if ($authUser->Avatar !== 'default.png') {
                try {
                    unlink(public_path('uploads/') . $authUser->Avatar);
                } catch (\Exception $e) {
                }
            }
            $image = $request->image;
            $name = rand(0, 10000000) . time() . '.' . explode('/', explode(':', substr($image, 0, strpos($image, ';')))[1])[1];
            Image::make($image)->save(public_path('uploads/') . $name);
            $user->Avatar = $name;
        }
        $user->save();
        return response()->json(['message' => 'Profile updated successfully']);
    }


    public function register(StoreMarketPriceRequest $request)
    {

        try {
            $user = new User();
            $user->Name = $request->name;
            $user->Designation = $request->designation;
            $user->Email = $request->email;
            $user->Password = bcrypt($request->password);
            $user->Status = 'Y';
            $user->CreatedBy = 1;
            $user->UpdatedBy = 1;
            $user->UserType = 'default';
            $user->Avatar = 'default.png';
            $user->save();
            return response()->json(['message' => "success"]);

        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function me()
    {
       return response()->json( $this->userService->userExistByUserID(Auth::user()->UserID));
    }

    public function logout()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $this->loginService->logLogout($user->UserID);
           // UserLog::create(['UserId' => $user->ID, 'TransactionTime' => Carbon::now(), 'TransactionDetails' => "Logged Out"]);
            $this->guard()->logout();
        } catch (\Exception $exception) {

        }
        return response()->json(['message' => 'Successfully logged out']);
    }
//    public function logout(LoginLogService $logService)
//    {
//        $userId = Auth::id();
//
//        $logService->logLogout($userId);
//
//        Auth::logout();
//
//        return response()->json(['message' => 'Logged out']);
//    }


    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    protected function respondWithToken($token)
    {
        $data = $this->guard()->user();

        return response()->json([
            'status'=>1,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'data'=>$data
        ]);
    }

    public function guard()
    {
        return Auth::guard('api');
    }
}
