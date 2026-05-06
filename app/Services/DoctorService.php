<?php

namespace App\Services;



use App\Models\Doctor\vw_DoctorInfo;
use PhpParser\Node\Stmt\DeclareDeclare;

class DoctorService
{
    public function doctorInfoByID($userName){
        return vw_DoctorInfo::where('Username','=',$userName)->where('IsActive','=',1)->where('IsDeleted','=',0)->first();
    }

}