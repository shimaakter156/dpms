<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complaint\ChiefComplaint;
use App\Models\Complaint\ComplaintDiagnosis;
use App\Models\Patient\PatientMedicalHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PrescriptionController extends Controller
{
    public function save(Request $req, $id = null)
    {
        $data = $req->validate([
            'patientID'      => 'required|integer',
            'DoctorID'       => 'required|integer',
            'date'           => 'required|date',
            'complaints'     => 'nullable|string',
            'history'        => 'nullable|string',
            'examFindings'   => 'nullable|string',
            'diagnosis'      => 'nullable|string',
            'investigations' => 'nullable|string',
            'advice'         => 'nullable|string',
            'referral'       => 'nullable|string',
            'nextVisit'      => 'nullable|date',
            'vitals'         => 'nullable|array',
            'medicines'      => 'nullable|array',
            'medicines.*.name'         => 'nullable|string',
            'medicines.*.medicineID'   => 'nullable|integer',
            'medicines.*.dosage'       => 'nullable|string',
            'medicines.*.duration'     => 'nullable|string',
            'medicines.*.instructions' => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($data, $id) {

                $payload = [
                    'PatientID'        => $data['patientID'],
                    'DoctorID'         => $data['DoctorID'],
                    'PrescriptionDate' => $data['date'],
                    'ChiefComplaints'  => $data['complaints']     ?? '',
                    'History'          => $data['history']        ?? '',
                    'ExamFindings'     => $data['examFindings']   ?? '',
                    'Diagnosis'        => $data['diagnosis']      ?? '',
                    'Investigations'   => $data['investigations'] ?? '',
                    'Advice'           => $data['advice']         ?? '',
                    'Referral'         => $data['referral']       ?? '',
                    'NextVisit'        => $data['nextVisit']      ?? null,
                    'BP'               => $data['vitals']['bp']     ?? null,
                    'Pulse'            => $data['vitals']['pulse']  ?? null,
                    'Temp'             => $data['vitals']['temp']   ?? null,
                    'Weight'           => $data['vitals']['weight'] ?? null,
                    'SpO2'             => $data['vitals']['spo2']   ?? null,
                    'RBS'              => $data['vitals']['rbs']    ?? null,
                    'UpdatedAt'        => now(),
                ];

                if ($id) {
                    DB::table('tbl_Prescription')->where('PrescriptionID', $id)->update($payload);
                    // wipe old child rows
                    DB::table('tbl_ComplaintMedicine')->where('PrescriptionID', $id)->delete();
                } else {
                    $payload['CreatedAt'] = now();
                    $id = DB::table('tbl_Prescription')->insertGetId($payload, 'PrescriptionID');
                }

                // Insert medicines
                $rows = [];
                foreach (($data['medicines'] ?? []) as $idx => $m) {
                    if (empty(trim($m['name'] ?? ''))) continue;
                    $rows[] = [
                        'PrescriptionID' => $id,
                        'MedicineID'     => $m['medicineID'] ?? null,
                        'MedicineName'   => $m['name'] ?? '',
                        'Dosage'         => $m['dosage'] ?? '',
                        'Duration'       => $m['duration'] ?? '',
                        'Instructions'   => $m['instructions'] ?? '',
                        'SortOrder'      => $idx + 1,
                    ];
                }
                if ($rows) DB::table('tbl_ComplaintMedicine')->insert($rows);

                return response()->json([
                    'id'      => $id,
                    'message' => 'Prescription saved successfully.',
                ]);
            });
        } catch (Throwable $e) {
            return response()->json(['message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $rx = DB::table('tbl_Prescription')->where('PatientID', $id)->first();
        if (!$rx) return response()->json(['message' => 'Not found'], 404);

        $medicines = DB::table('tbl_ComplaintMedicine')
            ->where('PrescriptionID', $id)
            ->orderBy('SortOrder')
            ->get()
            ->map(function ($m) {
                return [
                    'medicineID' => $m->MedicineID,
                    'name' => $m->MedicineName,
                    'dosage' => $m->Dosage,
                    'duration' => $m->Duration,
                    'instructions' => $m->Instructions,
                ];
            });

        return response()->json(['data' => [
            'patientID'      => $rx->PatientID,
            'date'           => $rx->PrescriptionDate,
            'complaints'     => $rx->ChiefComplaints,
            'history'        => $rx->History,
            'examFindings'   => $rx->ExamFindings,
            'diagnosis'      => $rx->Diagnosis,
            'investigations' => $rx->Investigations,
            'advice'         => $rx->Advice,
            'referral'       => $rx->Referral,
            'nextVisit'      => $rx->NextVisit,
            'vitals'         => [
                'bp'     => $rx->BP,
                'pulse'  => $rx->Pulse,
                'temp'   => $rx->Temp,
                'weight' => $rx->Weight,
                'spo2'   => $rx->SpO2,
                'rbs'    => $rx->RBS,
            ],
            'medicines' => $medicines,
        ]]);
    }

    /** GET /api/prescriptions/last-by-patient/{patientId} */
    public function lastByPatient($patientId)
    {
        $rx = DB::table('tbl_Prescription')
            ->where('PatientID', $patientId)
            ->orderByDesc('CreatedDate')
            ->first();

        if (!$rx) return response()->json(['data' => null]);

        $medicines = DB::table('tbl_ComplaintMedicine')
            ->where('PrescriptionID', $rx->PrescriptionID)
            ->orderBy('SortOrder')
            ->get()
            ->map(function ($m) {
                return [
                    'medicineID' => $m->MedicineID,
                    'name' => $m->MedicineName,
                    'dosage' => $m->Dosage,
                    'duration' => $m->Duration,
                    'instructions' => $m->Instructions,
                ];
            });

        return response()->json(['data' => [
            'complaints'     => $rx->ChiefComplaints,
            'diagnosis'      => $rx->Diagnosis,
            'investigations' => $rx->Investigations,
            'advice'         => $rx->Advice,
            'medicines'      => $medicines,
            'date'           => $rx->PrescriptionDate,
        ]]);
    }
}
