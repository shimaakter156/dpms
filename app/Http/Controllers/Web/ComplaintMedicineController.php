<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplaintMedicineController extends Controller
{
    public function suggest(Request $req)
    {
        $complaints = $req->input('complaints', []);
        $doctorId   = $req->input('doctorId');
        if (empty($complaints) || !is_array($complaints)) {
            return response()->json(['data' => []]);
        }

        // 1) Resolve complaint NAMES → IDs (your form passes strings).
        $complaintIds = DB::table('tbl_ChiefComplaint')
            ->whereIn('ComplaintName', $complaints)
            ->where('IsActive', 1)
            ->pluck('ComplaintID')
            ->toArray();

        if (empty($complaintIds)) return response()->json(['data' => []]);

        // 2) Fetch mapped medicines.
        // Doctor's own preferences first, then global ones.
        $rows = DB::table('tbl_ComplaintMedicine as cm')
            ->join('tbl_Medicine as m', 'm.MedicineID', '=', 'cm.MedicineID')
            ->leftJoin('tbl_DosageForm as df', 'df.DosageFormID', '=', 'm.DosageFormID')
            ->whereIn('cm.ComplaintID', $complaintIds)
            ->where('cm.IsActive', 1)
            ->where('m.IsActive', 1)
            ->where(function ($w) use ($doctorId) {
                $w->whereNull('cm.DoctorID')   // global
                ->orWhere('cm.DoctorID', $doctorId); // this doctor's
            })
            ->select(
                'cm.ComplaintID',
                'm.MedicineID',
                'm.MedicineName as Name',
                'm.Strength',
                'df.FormName as Form',
                'cm.DefaultDosage',
                'cm.DefaultDuration',
                'cm.DefaultInstructions',
                'cm.Priority',
                'cm.DoctorID'
            )
            // Doctor-specific (non-null DoctorID) appears first
            ->orderByRaw('CASE WHEN cm.DoctorID IS NULL THEN 1 ELSE 0 END')
            ->orderBy('cm.Priority')
            ->orderBy('m.MedicineName')
            ->get();

        // 3) De-duplicate by MedicineID (doctor's choice wins over global).
        $seen = [];
        $deduped = [];
        foreach ($rows as $r) {
            if (isset($seen[$r->MedicineID])) continue;
            $seen[$r->MedicineID] = true;
            $deduped[] = [
                'MedicineID'          => $r->MedicineID,
                'Name'                => $r->Name,
                'Strength'            => $r->Strength,
                'Form'                => $r->Form,
                'label'               => trim(($r->Form ?? '') . ' ' . $r->Name . ' ' . ($r->Strength ?? '')),
                'DefaultDosage'       => $r->DefaultDosage,
                'DefaultDuration'     => $r->DefaultDuration,
                'DefaultInstructions' => $r->DefaultInstructions,
            ];
        }

        return response()->json(['data' => $deduped]);
    }


    public function store(Request $req)
    {
        $data = $req->validate([
            'doctorId'     => 'required|integer',
            'complaintId'  => 'required|integer',
            'medicineId'   => 'required|integer',
            'dosage'       => 'nullable|string',
            'duration'     => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        // Upsert: avoid duplicates
        $existing = DB::table('tbl_ComplaintMedicine')
            ->where('DoctorID',    $data['doctorId'])
            ->where('ComplaintID', $data['complaintId'])
            ->where('MedicineID',  $data['medicineId'])
            ->first();

        if ($existing) {
            DB::table('tbl_ComplaintMedicine')
                ->where('ComplaintMedicineID', $existing->ComplaintMedicineID)
                ->update([
                    'DefaultDosage'       => $data['dosage']       ?? $existing->DefaultDosage,
                    'DefaultDuration'     => $data['duration']     ?? $existing->DefaultDuration,
                    'DefaultInstructions' => $data['instructions'] ?? $existing->DefaultInstructions,
                    'IsActive'            => 1,
                ]);
        } else {
            DB::table('tbl_ComplaintMedicine')->insert([
                'DoctorID'            => $data['doctorId'],
                'ComplaintID'         => $data['complaintId'],
                'MedicineID'          => $data['medicineId'],
                'DefaultDosage'       => $data['dosage']       ?? '',
                'DefaultDuration'     => $data['duration']     ?? '',
                'DefaultInstructions' => $data['instructions'] ?? '',
                'Priority'            => 1,
                'IsActive'            => 1,
            ]);
        }
        return response()->json(['message' => 'Saved.']);
    }
}
