<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DoctorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{
    protected  $doctorService;
    public function __construct(DoctorService $doctorService){
        $this->doctorService = $doctorService;
    }
    public function doctorInfo(){
        $userName = Auth::user()->Username;
        $info = $this->doctorService->doctorInfoByID($userName);
        return response()->json($info);
    }
}
