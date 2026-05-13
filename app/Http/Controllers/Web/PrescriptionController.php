<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Complaint\ChiefComplaint;
use App\Models\Complaint\ComplaintDiagnosis;
use App\Models\Patient\PatientMedicalHistory;
use App\Models\Prescription\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PrescriptionController extends Controller
{

    public function generatePrescriptionNo()
    {
        $date = date('Ymd');

        $lastPrescription = Prescription::whereDate('CreatedDate', today())
            ->latest('PrescriptionID')
            ->first();

        $nextNumber = 1;

        if ($lastPrescription && preg_match('/(\d+)$/', $lastPrescription->prescription_no, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        return 'RX-' . $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
    public function save(Request $req, $id = null)
    {
        $data = $req->validate([
            'patientID'      => 'required|integer',
            'DoctorID'       => 'required|integer',
            'date'           => 'required|date',
            'complaints'     => 'nullable|array',
            'complaints.*'   => 'nullable|string',
            'history'        => 'nullable|string',
            'examFindings'   => 'nullable|string',
            'diagnosis'      => 'nullable|array',
            'diagnosis.*'    => 'nullable|string',
            'investigations' => 'nullable|array',
            'investigations.*' => 'nullable|string',
            'advice'         => 'nullable|array',
            'advice.*'       => 'nullable|string',
            'referral'       => 'nullable|string',
            'nextVisit'      => 'nullable|date',
            'vitals'         => 'nullable|array',
            'medicines'      => 'nullable|array',
            'medicines.*.name'         => 'nullable|string',
            'medicines.*.medicineID'   => 'nullable',
            'medicines.*.dosage'       => 'nullable|string',
            'medicines.*.duration'     => 'nullable|string',
            'medicines.*.instructions' => 'nullable|string',
        ]);

        $prescriptionNo = $this->generatePrescriptionNo();

        $complaintsArr   = $this->toArray($data['complaints']   ?? []);
        $diagnosisArr    = $this->toArray($data['diagnosis']    ?? []);
        $investigationsArr = $this->toArray($data['investigations'] ?? []);
        $adviceArr       = $this->toArray($data['advice']       ?? []);

        $bp        = $data['vitals']['bp'] ?? '';
        $bpParts   = explode('/', $bp);
        $systolic  = isset($bpParts[0]) ? trim($bpParts[0]) : null;
        $diastolic = isset($bpParts[1]) ? trim($bpParts[1]) : null;

        try {
            return DB::transaction(function () use (
                $data, $id,$prescriptionNo,
                $complaintsArr, $diagnosisArr, $investigationsArr, $adviceArr,
                $systolic, $diastolic
            ) {
                $prescPayload = [
                    'PrescriptionNo'         => $prescriptionNo,
                    'DoctorID'         => $data['DoctorID'],
                    'PatientID'        => $data['patientID'],
                    'VisitDate'        => $data['date'],
                    'HistoryOfIllness' => $data['history']  ?? null,
                    'ExaminationNotes' => $data['examFindings'] ?? null,
                    'ReferralNotes'    => $data['referral']  ?? null,
                    'NextVisitDate'    => $data['nextVisit'] ?? null,
                    'BPSystolic'       => $systolic,
                    'BPDiastolic'      => $diastolic,
                    'PulseRate'        => $data['vitals']['pulse']  ?? null,
                    'Temperature'      => $data['vitals']['temp']   ?? null,
                    'Weight'           => $data['vitals']['weight'] ?? null,
                    'SpO2'             => $data['vitals']['spo2']   ?? null,
                    'RBS'              => $data['vitals']['rbs']    ?? null,
                    'Status'           => 'Active',
                    'IsDeleted'        => 0,
                    'UpdatedBy'        => auth()->id() ?? 1,
                    'UpdatedDate'      => now(),
                ];

                if ($id) {
                    DB::table('tbl_Prescription')
                        ->where('PrescriptionID', $id)
                        ->update($prescPayload);
                } else {
                    $prescPayload['CreatedBy']   = auth()->id() ?? 1;
                    $prescPayload['CreatedDate'] = now();
                    $id = DB::table('tbl_Prescription')
                        ->insertGetId($prescPayload, 'PrescriptionID');
                }
                $isDeleted = ['IsDeleted'=>0];

                DB::table('tbl_PrescriptionComplaint')   ->where('PrescriptionID', $id)->update($isDeleted);
                DB::table('tbl_PrescriptionDiagnosis')   ->where('PrescriptionID', $id)->update($isDeleted);
                DB::table('tbl_PrescriptionInvestigation')->where('PrescriptionID', $id)->update($isDeleted);
                DB::table('tbl_PrescriptionAdvice')      ->where('PrescriptionID', $id)->update($isDeleted);
                DB::table('tbl_PrescriptionMedicine')    ->where('PrescriptionID', $id)->update($isDeleted);

                $userId = auth()->id() ?? null;
                $now    = now();

                $complaintRows = [];
                foreach ($complaintsArr as $idx => $complaint) {
                    $complaint = trim($complaint);
                    if (!$complaint) continue;

                    $lookup = DB::table('tbl_ChiefComplaint')
                        ->whereRaw('LOWER(ComplaintName) = ?', [strtolower($complaint)])
                        ->first();

                    $complaintRows[] = [
                        'PrescriptionID' => $id,
                        'ComplaintID'    => $lookup->ComplaintID ?? null,
                        'DurationValue'  => null,
                        'DurationType'   => null,
                        'Severity'       => null,
                        'Notes'          => $complaint,
                    ];
                }
                if ($complaintRows) {
                    DB::table('tbl_PrescriptionComplaint')->insert($complaintRows);
                }

                // ── 4. tbl_PrescriptionDiagnosis ─────────────────────────────
                $diagRows = [];
                foreach ($diagnosisArr as $idx => $diag) {
                    $diag = trim($diag);
                    if (!$diag) continue;

                    $lookup = DB::table('tbl_DiagnosisSetup')
                        ->whereRaw('LOWER(DiagnosisName) = ?', [strtolower($diag)])
                        ->first();

                    $diagRows[] = [
                        'PrescriptionID'   => $id,
                        'DiagnosisSetupID' => $lookup->DiagnosisSetupID ?? null,
                        'DiagnosisText'    => $diag,
                        'IsPrimary'        => $idx === 0 ? 1 : 0,
                        'SortOrder'        => $idx + 1,
                        'CreatedBy'        => $userId,
                        'CreatedDate'      => $now,
                    ];
                }
                if ($diagRows) {
                    DB::table('tbl_PrescriptionDiagnosis')->insert($diagRows);
                }

                // ── 5. tbl_PrescriptionInvestigation ─────────────────────────
                $invRows = [];
                foreach ($investigationsArr as $idx => $inv) {
                    $inv = trim($inv);
                    if (!$inv) continue;

                    $lookup = DB::table('tbl_InvestigationSetup')
                        ->whereRaw('LOWER(InvestigationName) = ?', [strtolower($inv)])
                        ->first();

                    $invRows[] = [
                        'PrescriptionID'      => $id,
                        'InvestigationSetupID'=> $lookup->InvestigationSetupID ?? null,
                        'InvestigationName'   => $inv,
                        'Notes'               => null,
                        'SortOrder'           => $idx + 1,
                        'CreatedBy'           => $userId,
                        'CreatedDate'         => $now,
                    ];
                }
                if ($invRows) {
                    DB::table('tbl_PrescriptionInvestigation')->insert($invRows);
                }

                // ── 6. tbl_PrescriptionAdvice ─────────────────────────────────
                $adviceRows = [];
                foreach ($adviceArr as $idx => $advice) {
                    $advice = trim($advice);
                    if (!$advice) continue;

                    $lookup = DB::table('tbl_AdviceTemplate')
                        ->whereRaw('LOWER(AdviceText) = ?', [strtolower($advice)])
                        ->first();

                    $adviceRows[] = [
                        'PrescriptionID'   => $id,
                        'AdviceTemplateID' => $lookup->AdviceTemplateID ?? null,
                        'AdviceText'       => $advice,
                        'SortOrder'        => $idx + 1,
                        'CreatedBy'        => $userId,
                        'CreatedDate'      => $now,
                    ];
                }
                if ($adviceRows) {
                    DB::table('tbl_PrescriptionAdvice')->insert($adviceRows);
                }

                // ── 7. tbl_PrescriptionMedicine ───────────────────────────────
                $medRows = [];
                foreach (($data['medicines'] ?? []) as $idx => $m) {
                    $name = trim($m['name'] ?? '');
                    if (!$name) continue;

                    // Fetch extra medicine details if MedicineID provided
                    $medDetail = null;
                    if (!empty($m['medicineID'])) {
                        $medDetail = DB::table('tbl_Medicine')
                            ->where('MedicineID', $m['medicineID'])
                            ->first();
                    }

                    $medRows[] = [
                        'PrescriptionID' => $id,
                        'MedicineID'     => !empty($m['medicineID']) ? $m['medicineID'] : null,
                        'MedicineName'   => $name,
                        'GenericName'    => $medDetail->GenericName  ?? null,
                        'DosageForm'     => $medDetail->DosageForm   ?? null,
                        'Strength'       => $medDetail->Strength     ?? null,
                        'Dosage'         => $m['dosage']       ?? '',
                        'Duration'       => $m['duration']     ?? '',
                        'Instructions'   => $m['instructions'] ?? '',
                        'Quantity'       => null,
                        'SortOrder'      => $idx + 1,
                        'CreatedBy'      => $userId,
                        'CreatedDate'    => $now,
                    ];
                }
                if ($medRows) {
                    DB::table('tbl_PrescriptionMedicine')->insert($medRows);
                }

                return response()->json([
                    'id'      => $id,
                    'message' => 'Prescription saved successfully.',
                ]);
            });

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Save failed: ' . $e->getMessage(),
            ], 500);
        }
    }

// ── Helper: accepts string (newline-separated) or array ──────────────────────
    private function toArray($value): array
    {
        if (is_array($value)) {
            return array_filter(array_map('trim', $value));
        }
        if (is_string($value) && strlen(trim($value))) {
            return array_filter(array_map('trim', explode("\n", $value)));
        }
        return [];
    }

    public function show($id)
    {
        $rx = DB::table('tbl_Prescription')->where('PrescriptionID', $id)->first();
        if (!$rx) return response()->json(['message' => 'Not found'], 404);

        $ChiefComplaints = DB::table('tbl_PrescriptionComplaint')   ->where('PrescriptionID', $id)->get();
       $Diagnosis=  DB::table('tbl_PrescriptionDiagnosis')   ->where('PrescriptionID', $id)->get();
       $Investigations=  DB::table('tbl_PrescriptionInvestigation')->where('PrescriptionID', $id)->get();
       $Advice= DB::table('tbl_PrescriptionAdvice')      ->where('PrescriptionID', $id)->get();

        $medicines = DB::table('tbl_PrescriptionMedicine')
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
//            'date'           => $rx->PrescriptionDate,
            'complaints'     => $ChiefComplaints,
//            'history'        => $History,
//            'examFindings'   => $rx->ExamFindings,
            'diagnosis'      => $Diagnosis,
            'investigations' => $Investigations,
            'advice'         => $Advice,
//            'referral'       => $rx->Referral,
//            'nextVisit'      => $rx->NextVisit,
            'vitals'         => [
                'bp'     => $rx->BPSystolic.'/'.$rx->BPDiastolic,
                'pulse'  => $rx->PulseRate,
                'temp'   => $rx->Temperature,
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
        $complaints = DB::table('tbl_PrescriptionComplaint')
            ->where('PrescriptionID', $rx->PrescriptionID)
            ->orderBy('PrescriptionComplaintID')
            ->get();
        $diagnosis = DB::table('tbl_PrescriptionDiagnosis')
            ->where('PrescriptionID', $rx->PrescriptionID)
            ->orderBy('PrescDiagID')
            ->get();
        $investigations = DB::table('tbl_PrescriptionInvestigation')
            ->where('PrescriptionID', $rx->PrescriptionID)
            ->orderBy('PrescInvID')
            ->get();
        $advice = DB::table('tbl_PrescriptionAdvice')
            ->where('PrescriptionID', $rx->PrescriptionID)
            ->orderBy('PrescAdviceID')
            ->get();




        $medicines = DB::table('tbl_PrescriptionMedicine')
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
                    'strength' => $m->Strength,
                ];
            });

        return response()->json(['data' =>  [
            'prescriptionID'     => $rx->PrescriptionID,
            'complaints'     => $complaints,
            'diagnosis'      => $diagnosis,
            'investigations' => $investigations,
            'advice'         => $advice,
            'medicines'      => $medicines,
        ]]);
    }
}
