<?php

namespace App\Services;

use App\Models\Login\LoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginService
{

    public function handleFailedLogin($user)
    {

        $user->FailedLoginCount += 1;

        if ($user->FailedLoginCount >= 5) {
            $user->IsLocked = 1;
            $user->LockoutUntil = now()->addMinutes($this->lockMinutes);
        }

        $user->save();
    }
    public function resetLoginAttempts($user)
    {
        $user->FailedLoginCount = 0;
        $user->IsLocked = 0;
        $user->LockoutUntil = null;
        $user->LastLoginDate = now();
        $user->save();
    }
    public function lockCheck($user)
    {
        if ($user->IsLocked) {
            if ($user->LockoutUntil && now()->greaterThan($user->LockoutUntil)) {
                $user->IsLocked = 0;
                $user->FailedLoginCount = 0;
                $user->LockoutUntil = null;
                $user->save();
                return;
            }
            throw new \Exception(
                'Account locked until ' . $user->LockoutUntil->format('h:i A')
            );
        }
    }
    public function hashCheck($request,$user){
        return Hash::check($user->PasswordSalt.$request->password, $user->PasswordHash);

    }
    public function logFailure($request, $reason)
    {
        return LoginLog::insert([
            'UserName' => $request->username,
            'LoginDate' => now(),
            'IPAddress' => $request->ip(),
            'DeviceInfo' => $request->userAgent(),
            'IsSuccess' => 0,
            'FailReason' => $reason
        ]);
    }

    public function logSuccess($request, $user)
    {
        return LoginLog::insert([
            'UserID' => $user->UserID,
            'UserName' => $user->username,
            'LoginDate' => now(),
            'IPAddress' => $request->ip(),
            'DeviceInfo' => $request->userAgent(),
            'IsSuccess' => 1
        ]);
    }



    public function logLogout($userId)
    {

        $log = LoginLog::where('UserID', $userId)
            ->whereNull('LogoutDate')
            ->first();

        if ($log) {
            $log->LogoutDate = now();
            $log->SessionDuration = now()->diffInMinutes($log->LoginDate);
            $log->save();
        }
    }
    public function checkValidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid'], 400);
        }
    }

}