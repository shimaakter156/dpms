<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Patient\vw_PatientSummary;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function listForDoctor(Request $req, $doctorId)
    {
        $workplaceId = $req->query('workplaceId');

        $q = DB::table('tbl_Patient as p')
            ->select(
                'p.PatientID',
                'p.PatientCode',
                'p.PatientName as FullName',
                'p.Phone',
                'p.Age',
                'p.Gender',
                'p.BloodGroup',
                'p.WorkplaceID'
            )
            ->where('p.IsActive', 1);

        if ($workplaceId) {
            // Patients tagged to this workplace
            $q->where('p.WorkplaceID', $workplaceId);
        } else {
            // All patients across all workplaces of this doctor
            $q->whereIn('p.WorkplaceID', function ($sub) use ($doctorId) {
                $sub->select('WorkplaceID')
                    ->from('tbl_DoctorWorkplace')
                    ->where('DoctorID', $doctorId)
                    ->where('IsActive', 1);
            });
        }

        $rows = $q->orderBy('p.PatientName')->get();
        return response()->json(['data' => $rows]);
    }

    /** GET /api/patients/search?q=xxx&workplaceId=123 */
    public function search(Request $req)
    {
        $q           = trim($req->query('q', ''));
        $workplaceId = $req->query('workplaceId');
        $limit       = min((int) $req->query('limit', 20), 100);

        $rows = DB::table('tbl_Patient')
            ->select('PatientID', 'PatientCode', 'PatientName as FullName',
                'Phone', 'Age', 'Gender', 'WorkplaceID')
            ->where('IsActive', 1)
            ->when($workplaceId, fn($w) => $w->where('WorkplaceID', $workplaceId))
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($q2) use ($q) {
                    $q2->where('PatientName', 'like', "%$q%")
                        ->orWhere('Phone', 'like', "%$q%")
                        ->orWhere('PatientCode', 'like', "%$q%");
                });
            })
            ->orderBy('PatientName')
            ->limit($limit)
            ->get();

        return response()->json(['data' => $rows]);
    }

    /** GET /api/patients/{id} — full details for auto-fill */
    public function show($id)
    {
        $p = DB::table('tbl_Patient')->where('PatientID', $id)->first();
        if (!$p) return response()->json(['message' => 'Patient not found'], 404);

        $allergies = DB::table('tbl_PatientAllergy')
            ->where('PatientID', $id)->where('IsActive', 1)
            ->pluck('AllergyName')->implode(', ');

        $chronic = DB::table('tbl_PatientMedicalHistory')
            ->where('PatientID', $id)
            ->pluck('ConditionName')->implode(', ');

        $lastVitals = DB::table('tbl_Prescription')
            ->where('PatientID', $id)
            ->orderByDesc('PrescriptionDate')
            ->selectRaw("
                    CONCAT(BPSystolic, '/', BPDiastolic) as BP, 
                    PulseRate as Pulse, 
                    Weight, 
                    VisitDate as PrescriptionDate
                ")
            ->first();

        return response()->json(['data' => [
            'PatientID'         => $p->PatientID,
            'PatientCode'       => $p->PatientCode,
            'FullName'          => $p->FullName,
            'Phone'             => $p->Phone,
            'Email'             => $p->Email ?? null,
            'Age'               => $p->Age,
            'Gender'            => $p->Gender,
            'DOB'               => $p->DOB ?? null,
            'Address'           => $p->Address ?? null,
            'BloodGroup'        => $p->BloodGroup ?? null,
            'Occupation'        => $p->Occupation ?? null,
            'MaritalStatus'     => $p->MaritalStatus ?? null,
            'EmergencyContact'  => $p->EmergencyContact ?? null,
            'WorkplaceID'       => $p->WorkplaceID ?? null,
            'Allergies'         => $allergies,
            'ChronicConditions' => $chronic,
            'LastVitals'        => $lastVitals,
        ]]);
    }

}
