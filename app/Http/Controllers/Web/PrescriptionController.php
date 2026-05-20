<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Prescription\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionController extends Controller
{
    /* ───────────────────────── helpers ───────────────────────── */
    private function toText($value): string
    {
        if ($value === null) return '';

        if (is_string($value) || is_numeric($value)) {
            return trim((string) $value);
        }

        if (is_array($value)) {
            return trim(
                $value['label']
                ?? $value['name']
                ?? $value['MedicineName']
                ?? $value['ComplaintName']
                ?? $value['DiagnosisName']
                ?? $value['InvestigationName']
                ?? $value['AdviceText']
                ?? $value['ConditionName']
                ?? $value['DiseaseName']
                ?? $value['HistoryName']
                ?? $value['FindingName']
                ?? $value['FindingText']
                ?? $value['ReferralText']
                ?? ''
            );
        }

        if (is_object($value)) {
            return trim(
                $value->label
                ?? $value->name
                ?? $value->MedicineName
                ?? $value->ComplaintName
                ?? $value->DiagnosisName
                ?? $value->InvestigationName
                ?? $value->AdviceText
                ?? $value->ConditionName
                ?? $value->DiseaseName
                ?? $value->HistoryName
                ?? $value->FindingName
                ?? $value->FindingText
                ?? $value->ReferralText
                ?? ''
            );
        }

        return '';
    }

    private function generatePrescriptionNo(): string
    {
        $date = date('Ymd');
        $last = Prescription::whereDate('CreatedDate', today())
            ->latest('PrescriptionID')->first();

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last->PrescriptionNo ?? '', $m)) {
            $next = intval($m[1]) + 1;
        }
        return 'RX-' . $date . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /** Accepts array of strings OR array of objects; returns clean array of strings. */
    private function toArray($value): array
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $v) {
                $text = $this->toText($v);
                if ($text !== '') $out[] = $text;
            }
            return array_values(array_filter($out));
        }

        if (is_string($value) && trim($value) !== '') {
            return array_values(array_filter(array_map('trim', explode("\n", $value))));
        }

        return [];
    }

    /* ───────────────────────── fetch children ───────────────────────── */
    private function fetchChildren(int $prescriptionID): array
    {
        $prescription = DB::table('tbl_Prescription')
            ->where('PrescriptionID', $prescriptionID)
            ->first();

        $patientID = $prescription->PatientID ?? null;

        // History (patient-level)
        $history = [];
        if ($patientID) {
            $history = DB::table('tbl_PatientMedicalHistory')
                ->where('PatientID', $patientID)
                ->where(function ($q) {
                    $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
                })
                ->select('HistoryID', 'PatientID', 'ConditionName', 'DiseaseName')
                ->get()
                ->map(function ($row) {
                    return [
                        'HistoryID' => $row->HistoryID ?: ('tmp_h_' . uniqid()),
                        'ConditionName' => $row->ConditionName ?: $row->DiseaseName,
                    ];
                })
                ->filter(function ($r) {
                    return !empty($r['ConditionName']);
                })
                ->values()
                ->toArray();

        }

        // Complaints
        $complaints = DB::table('tbl_PrescriptionComplaint as pc')
            ->leftJoin('tbl_ChiefComplaint as c', 'c.ComplaintID', '=', 'pc.ComplaintID')
            ->where('pc.PrescriptionID', $prescriptionID)
            ->where(function ($q) { $q->whereNull('pc.IsDeleted')->orWhere('pc.IsDeleted', 0); })
            ->select(
                DB::raw('COALESCE(pc.ComplaintID, 0) as ComplaintID'),
                DB::raw('COALESCE(c.ComplaintName, pc.Notes) as ComplaintName')
            )
            ->get()
            ->toArray();

        // Diagnosis
        $diagnosis = DB::table('tbl_PrescriptionDiagnosis as pd')
            ->leftJoin('tbl_DiagnosisSetup as d', 'd.DiagnosisSetupID', '=', 'pd.DiagnosisSetupID')
            ->where('pd.PrescriptionID', $prescriptionID)
            ->where(function ($q) { $q->whereNull('pd.IsDeleted')->orWhere('pd.IsDeleted', 0); })
            ->orderBy('pd.SortOrder')
            ->select(
                DB::raw('COALESCE(pd.DiagnosisSetupID, 0) as DiagnosisID'),
                DB::raw('COALESCE(d.DiagnosisName, pd.DiagnosisText) as DiagnosisName')
            )
            ->get()
            ->toArray();

        // Investigations
        $investigations = DB::table('tbl_PrescriptionInvestigation as pi')
            ->leftJoin('tbl_InvestigationSetup as i', 'i.InvestigationSetupID', '=', 'pi.InvestigationSetupID')
            ->where('pi.PrescriptionID', $prescriptionID)
            ->where(function ($q) { $q->whereNull('pi.IsDeleted')->orWhere('pi.IsDeleted', 0); })
            ->orderBy('pi.SortOrder')
            ->select(
                DB::raw('COALESCE(pi.InvestigationSetupID, 0) as InvestigationSetupID'),
                DB::raw('COALESCE(i.InvestigationName, pi.InvestigationName) as InvestigationName')
            )
            ->get()
            ->toArray();

        // Advice
        $advice = DB::table('tbl_PrescriptionAdvice as pa')
            ->leftJoin('tbl_AdviceTemplate as a', 'a.AdviceTemplateID', '=', 'pa.AdviceTemplateID')
            ->where('pa.PrescriptionID', $prescriptionID)
            ->where(function ($q) { $q->whereNull('pa.IsDeleted')->orWhere('pa.IsDeleted', 0); })
            ->orderBy('pa.SortOrder')
            ->select(
                DB::raw('COALESCE(pa.AdviceTemplateID, 0) as AdviceTemplateID'),
                DB::raw('COALESCE(a.AdviceText, pa.AdviceText) as AdviceText')
            )
            ->get()
            ->toArray();

        // Examination findings (NEW)
        $examFindings = DB::table('tbl_PrescriptionExamination as pe')
            ->leftJoin('tbl_ExaminationSetup as e', 'e.ExaminationSetupID', '=', 'pe.ExaminationSetupID')
            ->where('pe.PrescriptionID', $prescriptionID)
            ->where(function ($q) { $q->whereNull('pe.IsDeleted')->orWhere('pe.IsDeleted', 0); })
            ->orderBy('pe.SortOrder')
            ->select(
                DB::raw('COALESCE(pe.ExaminationSetupID, 0) as ExaminationSetupID'),
                DB::raw('COALESCE(e.FindingName, pe.FindingText) as FindingName')
            )
            ->get()
            ->toArray();

        // Referrals (NEW)
        $referral = DB::table('tbl_PrescriptionReferral as pr')
            ->leftJoin('tbl_ReferralSetup as r', 'r.ReferralSetupID', '=', 'pr.ReferralSetupID')
            ->where('pr.PrescriptionID', $prescriptionID)
            ->where(function ($q) { $q->whereNull('pr.IsDeleted')->orWhere('pr.IsDeleted', 0); })
            ->orderBy('pr.SortOrder')
            ->select(
                DB::raw('COALESCE(pr.ReferralSetupID, 0) as ReferralSetupID'),
                DB::raw('COALESCE(r.ReferralText, pr.ReferralText) as ReferralText')
            )
            ->get()
            ->toArray();

        // Medicines
        $medicines = DB::table('tbl_PrescriptionMedicine')
            ->where('PrescriptionID', $prescriptionID)
            ->where(function ($q) { $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0); })
            ->orderBy('SortOrder')
            ->select(
                'MedicineID    as medicineID',
                'MedicineName  as name',
                'Dosage        as dosage',
                'Duration      as duration',
                'Instructions  as instructions'
            )
            ->get()
            ->toArray();

        return compact(
            'history', 'complaints', 'diagnosis', 'investigations',
            'advice', 'examFindings', 'referral', 'medicines'
        );
    }

    /* ───────────────────────── save ───────────────────────── */
    public function save(Request $req, $id = null)
    {
        $data = $req->validate([
            'patientID'      => 'required|integer',
            'DoctorID'       => 'required|integer',
            'date'           => 'required|date',
            'complaints'     => 'nullable|array',
            'history'        => 'nullable|array',
            'examFindings'   => 'nullable',            // can be array OR string
            'diagnosis'      => 'nullable|array',
            'investigations' => 'nullable|array',
            'advice'         => 'nullable|array',
            'referral'       => 'nullable',            // can be array OR string
            'nextVisit'      => 'nullable|date',
            'vitals'         => 'nullable|array',
            'medicines'      => 'nullable|array',
        ]);

        $complaintsArr     = $this->toArray($data['complaints']     ?? []);
        $diagnosisArr      = $this->toArray($data['diagnosis']      ?? []);
        $investigationsArr = $this->toArray($data['investigations'] ?? []);
        $adviceArr         = $this->toArray($data['advice']         ?? []);
        $historyArr        = $this->toArray($data['history']        ?? []);
        $examFindingsArr   = $this->toArray($data['examFindings']   ?? []);
        $referralArr       = $this->toArray($data['referral']       ?? []);

        $bpParts   = explode('/', $data['vitals']['bp'] ?? '');
        $systolic  = isset($bpParts[0]) ? trim($bpParts[0]) : null;
        $diastolic = isset($bpParts[1]) ? trim($bpParts[1]) : null;
        $doctorID= $data['DoctorID'];
        $patientID= $data['patientID'];

        try {
            return DB::transaction(function () use (
                $data, $id, $complaintsArr, $historyArr, $diagnosisArr,
                $investigationsArr, $adviceArr, $examFindingsArr, $referralArr,
                $systolic, $diastolic,$doctorID
            ) {
                $userId = auth()->id() ?? 1;
                $now    = now();

                $payload = [
                    'DoctorID'         => $doctorID,
                    'PatientID'        => $data['patientID'],
                    'VisitDate'        => $data['date'],
                    'HistoryOfIllness' => count($historyArr) ? implode("\n", $historyArr) : null,
                    // Keep a denormalized copy for backwards compatibility / quick display
                    'ExaminationNotes' => count($examFindingsArr) ? implode("\n", $examFindingsArr) : null,
                    'ReferralNotes'    => count($referralArr) ? implode("\n", $referralArr) : null,
                    'NextVisitDate'    => $data['nextVisit']    ?? null,
                    'BPSystolic'       => $systolic,
                    'BPDiastolic'      => $diastolic,
                    'PulseRate'        => $data['vitals']['pulse']  ?? null,
                    'Temperature'      => $data['vitals']['temp']   ?? null,
                    'Weight'           => $data['vitals']['weight'] ?? null,
                    'SpO2'             => $data['vitals']['spo2']   ?? null,
                    'RBS'              => $data['vitals']['rbs']    ?? null,
                    'Status'           => 'Active',
                    'IsDeleted'        => 0,
                    'UpdatedBy'        => $userId,
                    'UpdatedDate'      => $now,
                ];

                if ($id) {
                    DB::table('tbl_Prescription')->where('PrescriptionID', $id)->update($payload);
                } else {
                    $payload['PrescriptionNo'] = $this->generatePrescriptionNo();
                    $payload['CreatedBy']      = $userId;
                    $payload['CreatedDate']    = $now;
                    $id = DB::table('tbl_Prescription')->insertGetId($payload, 'PrescriptionID');
                }

                // Soft-delete previous child rows
                foreach ([
                             'tbl_PrescriptionComplaint',
                             'tbl_PrescriptionDiagnosis',
                             'tbl_PrescriptionInvestigation',
                             'tbl_PrescriptionAdvice',
                             'tbl_PrescriptionMedicine',
                             'tbl_PrescriptionExamination',
                             'tbl_PrescriptionReferral',
                         ] as $table) {
                    DB::table($table)->where('PrescriptionID', $id)->update(['IsDeleted' => 1]);
                }

                // Re-insert children
                $this->insertComplaints($id, $complaintsArr, $userId, $now);
                $this->insertDiagnosis($id, $diagnosisArr, $userId, $now,$doctorID);
                $this->insertInvestigations($id, $investigationsArr, $userId, $now);
                $this->insertAdvice($id, $adviceArr, $userId, $now);
                $this->insertExamFindings($id, $examFindingsArr, $userId, $now);
                $this->insertReferrals($id, $referralArr, $userId, $now);
                $this->insertMedicines($id, $data['medicines'] ?? [], $userId, $now);
                $this->insertHistory($data['patientID'], $historyArr, $userId, $now);

                return response()->json([
                    'id'      => $id,
                    'message' => 'Prescription saved successfully.',
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Prescription save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Save failed: ' . $e->getMessage()], 500);
        }
    }

    /* ───────────────────────── insert helpers ───────────────────────── */

    private function insertHistory($patientID, array $items, $userId, $now): void
    {
        foreach ($items as $v) {
            if (!$v) continue;

            // FIX: was checking tbl_PatientMedicalHistory but inserting into tbl_PatientHistory
            $exists = DB::table('tbl_PatientMedicalHistory')
                ->where('PatientID', $patientID)
                ->whereRaw('LOWER(ConditionName) = ?', [strtolower($v)])
                ->where(function ($q) {
                    $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
                })
                ->exists();

            if ($exists) continue;

            DB::table('tbl_PatientMedicalHistory')->insert([
                'PatientID'      => $patientID,
                'ConditionName'  => $v,
                'DiseaseName'    => $v,
                'DiagnosedYear'  => null,
                'DiagnosisDate'  => null,
                'Notes'          => null,
                'Status'         => 'Stable',
                'IsActive'       => 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => $userId,
                'CreatedDate'    => $now,
            ]);
        }
    }

    private function insertComplaints($id, array $items, $userId, $now): void
    {
        $rows = [];
        foreach ($items as $idx => $v) {
            if (!$v) continue;
            $lookup = DB::table('tbl_ChiefComplaint')
                ->whereRaw('LOWER(ComplaintName) = ?', [strtolower($v)])
                ->first();

            // Auto-create the lookup if missing (so future selects find it)
            if (!$lookup) {
                $newId = DB::table('tbl_ChiefComplaint')->insertGetId([
                    'ComplaintName' => $v,
                    'SeverityLevel' => 'Mild',
                    'IsActive'      => 1,
                    'IsDeleted'     => 0,
                    'CreatedBy'     => $userId,
                    'CreatedDate'   => $now,
                ], 'ComplaintID');
                $complaintID = $newId;
            } else {
                $complaintID = $lookup->ComplaintID;
            }

            $rows[] = [
                'PrescriptionID' => $id,
                'ComplaintID'    => $complaintID,
                'Notes'          => $v,
//                'SortOrder'      => $idx + 1,
                'IsDeleted'      => 0,
            ];
        }
        if ($rows) DB::table('tbl_PrescriptionComplaint')->insert($rows);
    }

    private function insertDiagnosis($id, array $items, $userId, $now,$doctorID): void
    {
        $rows = [];
        foreach ($items as $idx => $v) {
            if (!$v) continue;

            // FIX: removed dd($lookup) which was halting execution
            $lookup = DB::table('tbl_DiagnosisSetup')
                ->whereRaw('LOWER(DiagnosisName) = ?', [strtolower($v)])
                ->first();

            // Auto-create lookup if missing
            if (!$lookup) {
                $newId = DB::table('tbl_DiagnosisSetup')->insertGetId([
                    'DiagnosisName' => $v,
                    'IsActive'      => 1,
                    'DoctorID'      => $doctorID,
//                    'IsDeleted'     => 0,
                    'CreatedBy'     => $userId,
                    'CreatedDate'   => $now,
                ], 'DiagnosisSetupID');
                $diagnosisID = $newId;
            } else {
                $diagnosisID = $lookup->DiagnosisSetupID;
            }

            $rows[] = [
                'PrescriptionID'   => $id,
                'DiagnosisSetupID' => $diagnosisID,
                'DiagnosisText'    => $v,
                'IsPrimary'        => $idx === 0 ? 1 : 0,
                'SortOrder'        => $idx + 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userId,
                'CreatedDate'      => $now,
            ];
        }
        if ($rows) DB::table('tbl_PrescriptionDiagnosis')->insert($rows);
    }

    private function insertInvestigations($id, array $items, $userId, $now): void
    {
        $rows = [];
        foreach ($items as $idx => $v) {
            if (!$v) continue;

            $lookup = DB::table('tbl_InvestigationSetup')
                ->whereRaw('LOWER(InvestigationName) = ?', [strtolower($v)])
                ->first();

            if (!$lookup) {
                $newId = DB::table('tbl_InvestigationSetup')->insertGetId([
                    'InvestigationName' => $v,
                    'IsActive'          => 1,
                    'IsDeleted'         => 0,
                    'CreatedBy'         => $userId,
                    'CreatedDate'       => $now,
                ], 'InvestigationSetupID');
                $investigationID = $newId;
            } else {
                $investigationID = $lookup->InvestigationSetupID;
            }

            $rows[] = [
                'PrescriptionID'       => $id,
                'InvestigationSetupID' => $investigationID,
                'InvestigationName'    => $v,
                'SortOrder'            => $idx + 1,
                'IsDeleted'            => 0,
                'CreatedBy'            => $userId,
                'CreatedDate'          => $now,
            ];
        }
        if ($rows) DB::table('tbl_PrescriptionInvestigation')->insert($rows);
    }

    private function insertAdvice($id, array $items, $userId, $now): void
    {
        $rows = [];
        foreach ($items as $idx => $v) {
            if (!$v) continue;

            $lookup = DB::table('tbl_AdviceTemplate')
                ->whereRaw('LOWER(AdviceText) = ?', [strtolower($v)])
                ->first();

            if (!$lookup) {
                $newId = DB::table('tbl_AdviceTemplate')->insertGetId([
                    'AdviceText'  => $v,
                    'IsActive'    => 1,
                    'IsDeleted'   => 0,
                    'CreatedBy'   => $userId,
                    'CreatedDate' => $now,
                ], 'AdviceTemplateID');
                $adviceID = $newId;
            } else {
                $adviceID = $lookup->AdviceTemplateID;
            }

            $rows[] = [
                'PrescriptionID'   => $id,
                'AdviceTemplateID' => $adviceID,
                'AdviceText'       => $v,
                'SortOrder'        => $idx + 1,
                'IsDeleted'        => 0,
                'CreatedBy'        => $userId,
                'CreatedDate'      => $now,
            ];
        }
        if ($rows) DB::table('tbl_PrescriptionAdvice')->insert($rows);
    }

    /** NEW: insert examination findings into dedicated table */
    private function insertExamFindings($id, array $items, $userId, $now): void
    {
        $rows = [];
        foreach ($items as $idx => $v) {
            if (!$v) continue;

            $lookup = DB::table('tbl_ExaminationSetup')
                ->whereRaw('LOWER(FindingName) = ?', [strtolower($v)])
                ->first();

            if (!$lookup) {
                $newId = DB::table('tbl_ExaminationSetup')->insertGetId([
                    'FindingName' => $v,
                    'Category'    => 'General',
                    'IsActive'    => 1,
                    'IsDeleted'   => 0,
                    'CreatedBy'   => $userId,
                    'CreatedDate' => $now,
                ], 'ExaminationSetupID');
                $examID = $newId;
            } else {
                $examID = $lookup->ExaminationSetupID;
            }

            $rows[] = [
                'PrescriptionID'     => $id,
                'ExaminationSetupID' => $examID,
                'FindingText'        => $v,
                'SortOrder'          => $idx + 1,
                'IsDeleted'          => 0,
                'CreatedBy'          => $userId,
                'CreatedDate'        => $now,
            ];
        }
        if ($rows) DB::table('tbl_PrescriptionExamination')->insert($rows);
    }

    /** NEW: insert referrals into dedicated table */
    private function insertReferrals($id, array $items, $userId, $now): void
    {
        $rows = [];
        foreach ($items as $idx => $v) {
            if (!$v) continue;

            $lookup = DB::table('tbl_ReferralSetup')
                ->whereRaw('LOWER(ReferralText) = ?', [strtolower($v)])
                ->first();

            if (!$lookup) {
                $newId = DB::table('tbl_ReferralSetup')->insertGetId([
                    'ReferralText' => $v,
                    'IsActive'     => 1,
                    'IsDeleted'    => 0,
                    'CreatedBy'    => $userId,
                    'CreatedDate'  => $now,
                ], 'ReferralSetupID');
                $referralID = $newId;
            } else {
                $referralID = $lookup->ReferralSetupID;
            }

            $rows[] = [
                'PrescriptionID'  => $id,
                'ReferralSetupID' => $referralID,
                'ReferralText'    => $v,
                'SortOrder'       => $idx + 1,
                'IsDeleted'       => 0,
                'CreatedBy'       => $userId,
                'CreatedDate'     => $now,
            ];
        }
        if ($rows) DB::table('tbl_PrescriptionReferral')->insert($rows);
    }

    private function insertMedicines($id, array $items, $userId, $now): void
    {
        $rows = [];

        foreach ($items as $idx => $m) {
            $name = $this->toText($m['name'] ?? $m['selectedMedicine'] ?? '');
            if (!$name) continue;

            $medicineID = $m['medicineID'] ?? null;

            $detail = null;
            if (!empty($medicineID) && is_numeric($medicineID)) {
                $detail = DB::table('tbl_Medicine')->where('MedicineID', $medicineID)->first();
            }

            $rows[] = [
                'PrescriptionID' => $id,
                'MedicineID'     => (!empty($medicineID) && is_numeric($medicineID)) ? (int) $medicineID : null,
                'MedicineName'   => $name,
                'GenericName'    => $detail->GenericName ?? null,
                'DosageForm'     => $detail->DosageForm  ?? null,
                'Strength'       => $detail->Strength    ?? null,
                'Dosage'         => $this->toText($m['dosage']['DosagePatternID']?? ''),
                'Duration'       => $this->toText($m['duration']['DurationPatternID']?? ''),
                'Instructions'   => $this->toText($m['instructions']['InstructionPatternID'] ?? ''),
                'SortOrder'      => $idx + 1,
                'IsDeleted'      => 0,
                'CreatedBy'      => $userId,
                'CreatedDate'    => $now,
            ];
        }

        if ($rows) {
            DB::table('tbl_PrescriptionMedicine')->insert($rows);
        }
    }

    /* ───────────────────────── show ───────────────────────── */

    public function show($id)
    {
        $rx = DB::table('tbl_Prescription')->where('PrescriptionID', $id)->first();
        if (!$rx) return response()->json(['message' => 'Not found'], 404);

        $children = $this->fetchChildren((int) $id);

        return response()->json(['data' => [
            'prescriptionID' => $rx->PrescriptionID,
            'patientID'      => $rx->PatientID,
            'date'           => $rx->VisitDate,
            'nextVisit'      => $rx->NextVisitDate,
            'history'        => $children['history'],
            'examFindings'   => $children['examFindings'],
            'referral'       => $children['referral'],
            'vitals' => [
                'bp'     => trim(($rx->BPSystolic ?? '') . '/' . ($rx->BPDiastolic ?? ''), '/'),
                'pulse'  => $rx->PulseRate,
                'temp'   => $rx->Temperature,
                'weight' => $rx->Weight,
                'spo2'   => $rx->SpO2,
                'rbs'    => $rx->RBS,
            ],
            'complaints'     => $children['complaints'],
            'diagnosis'      => $children['diagnosis'],
            'investigations' => $children['investigations'],
            'advice'         => $children['advice'],
            'medicines'      => $children['medicines'],
        ]]);
    }

    /* ───────────────────── last by patient ───────────────────── */

    public function lastByPatient($patientId)
    {
        $rx = DB::table('tbl_Prescription')
            ->where('PatientID', $patientId)
            ->where(function ($q) { $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0); })
            ->orderByDesc('CreatedDate')
            ->first();

        if (!$rx) return response()->json(['data' => null]);

        $children = $this->fetchChildren((int) $rx->PrescriptionID);

        return response()->json(
            ['data' => array_merge(['prescriptionID' => $rx->PrescriptionID], $children,
            array_merge([    'vitals' => [
                'bp'     => trim(($rx->BPSystolic ?? '') . '/' . ($rx->BPDiastolic ?? ''), '/'),
                'pulse'  => $rx->PulseRate,
                'temp'   => $rx->Temperature,
                'weight' => $rx->Weight,
                'spo2'   => $rx->SpO2,
                'rbs'    => $rx->RBS,
            ]])

        )]);
    }

    public function suggestionsByComplaints(Request $req): \Illuminate\Http\JsonResponse
    {
        $complaintIDs = $req->input('complaintIDs', []);

        if (!is_array($complaintIDs) || empty($complaintIDs)) {
            return response()->json(['data' => [
                'diagnosis' => [],
                'investigations' => [],
                'advice' => [],
                'medicines' => [],
            ]]);
        }

        $complaintIDs = array_values(array_filter($complaintIDs, 'is_numeric'));

        $medicines = DB::table('tbl_ComplaintMedicine as cm')
            ->join('tbl_Medicine as m', 'm.MedicineID', '=', 'cm.MedicineID')
            ->whereIn('cm.ComplaintID', $complaintIDs)
            ->select(
                'm.MedicineID',
                DB::raw("CONCAT(m.BrandName, ' (', m.Strength, ')') as MedicineName"),
                'm.Strength',
                'cm.Dose',
                'cm.Duration',
                'cm.Instructions',
                'cm.Frequency'
            )
            ->get();

        $diagnosis = DB::table('tbl_ComplaintDiagnosis as cd')
            ->join('tbl_DiagnosisSetup as d', 'd.DiagnosisSetupID', '=', 'cd.DiagnosisSetupID')
            ->whereIn('cd.ComplaintID', $complaintIDs)
            ->select(
                'd.DiagnosisSetupID as DiagnosisID',
                'd.DiagnosisName'
            )
            ->get();

        $investigations = DB::table('tbl_ComplaintInvestigation as ci')
            ->join('tbl_InvestigationSetup as i', 'i.InvestigationSetupID', '=', 'ci.InvestigationID')
            ->whereIn('ci.ComplaintID', $complaintIDs)
            ->select(
                'i.InvestigationSetupID',
                'i.InvestigationName'
            )
            ->get();

        $advice = DB::table('tbl_ComplaintAdvice as ca')
            ->join('tbl_AdviceTemplate as a', 'a.AdviceTemplateID', '=', 'ca.AdviceTemplateID')
            ->whereIn('ca.ComplaintID', $complaintIDs)
            ->select(
                'a.AdviceTemplateID',
                'a.AdviceText'
            )
            ->get();

        return response()->json(['data' => compact(
            'diagnosis',
            'investigations',
            'advice',
            'medicines'
        )]);
    }


// ════════════════════════════════════════════════════════════════
// Add to your LookupController @ the lookup/all endpoint
// (wherever you currently return complaints/diagnosis/etc.)
// ════════════════════════════════════════════════════════════════

    public function all()
    {
        $complaints = DB::table('tbl_ChiefComplaint')
            ->where('IsActive', 1)
            ->where(function ($q) {
                $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
            })
            ->select('ComplaintID', 'ComplaintName', 'SeverityLevel')
            ->orderBy('ComplaintName')
            ->get();

        $diagnosis = DB::table('tbl_DiagnosisSetup')
            ->where(function ($q) {
                $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
            })
            ->select('DiagnosisSetupID as DiagnosisID', 'DiagnosisName')
            ->orderBy('DiagnosisName')
            ->get();

        $investigations = DB::table('tbl_InvestigationSetup')
            ->where(function ($q) {
                $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
            })
            ->select('InvestigationSetupID', 'InvestigationName')
            ->orderBy('InvestigationName')
            ->get();

        $advice = DB::table('tbl_AdviceTemplate')
            ->where(function ($q) {
                $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
            })
            ->select('AdviceTemplateID', 'AdviceText')
            ->orderBy('AdviceText')
            ->get();

        // ─── NEW: examination findings ───
        $examFindings = DB::table('tbl_ExaminationSetup')
            ->where(function ($q) {
                $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
            })
            ->select('ExaminationSetupID', 'FindingName', 'Category')
            ->orderBy('Category')
            ->orderBy('FindingName')
            ->get();

        // ─── NEW: referrals ───
        $referral = DB::table('tbl_ReferralSetup')
            ->where(function ($q) {
                $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
            })
            ->select('ReferralSetupID', 'ReferralText', 'Category')
            ->orderBy('ReferralText')
            ->get();

        $history = DB::table('tbl_PatientMedicalHistory')
            ->where(function ($q) {
                $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
            })
            ->select('HistoryID', 'ConditionName', 'DiseaseName')
            ->get();

        $dosage = DB::table('tbl_DosagePattern')->where('IsActive', 1)->get();
        $duration = DB::table('tbl_DurationPattern')->where('IsActive', 1)->get();
        $instruction = DB::table('tbl_InstructionPattern')->where('IsActive', 1)->get();

        $medicine = DB::table('tbl_Medicine')
            ->where('IsActive', 1)
            ->where(function ($q) {
                $q->whereNull('IsDeleted')->orWhere('IsDeleted', 0);
            })
            ->select('MedicineID', 'BrandName', 'GenericName', 'Strength', 'DosageForm')
            ->orderBy('BrandName')
            ->get();

        return response()->json(['data' => compact(
            'complaints',
            'diagnosis',
            'investigations',
            'advice',
            'examFindings',   // NEW
            'referral',       // NEW
            'history',
            'dosage',
            'duration',
            'instruction',
            'medicine'
        )]);
    }
}