<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient\vw_PatientSummary;
use App\Services\PatientService;
use Illuminate\Http\Request;

class PatientController extends Controller
{

    protected $patientService;
    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }
    public function list($doctorID){



        return response()->json($this->patientService->patientListByDoctorID($doctorID));

    }
    public function patientInfoByID($PatientCode){
        return response()->json($this->patientService->patientInfoByID($PatientCode));
    }

}
