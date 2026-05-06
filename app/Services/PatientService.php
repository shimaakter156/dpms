<?php

namespace App\Services;



use App\Models\Doctor\vw_DoctorInfo;
use App\Models\Patient\vw_PatientSummary;
use PhpParser\Node\Stmt\DeclareDeclare;

class PatientService
{
    public function patientListByDoctorID($DoctorID){

        return vw_PatientSummary::where('RegisteredByDoctor','=',$DoctorID)->where('IsActive','=',1)->where('IsDeleted','=',0)->get();
    }
    public function patientInfoByID($PatientCode){

        return vw_PatientSummary::where('PatientCode','=',$PatientCode)->where('IsActive','=',1)->where('IsDeleted','=',0)->first();
    }

}