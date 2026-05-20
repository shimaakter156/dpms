<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\AssignOp\Concat;

class LookupController extends Controller
{
    private $tables = [
        'complaints'     => ['table' => 'tbl_ChiefComplaint',     'id' => 'ComplaintID',     'label' => 'ComplaintName'],
        'diagnosis'      => ['table' => 'tbl_DiagnosisMaster',    'id' => 'DiagnosisID',     'label' => 'DiagnosisName'],
        'history'        => ['table' => 'tbl_PatientMedicalHistory',    'id' => 'HistoryID',     'label' => 'ConditionName'],
        'investigations' => ['table' => 'tbl_InvestigationSetup', 'id' => 'InvestigationID', 'label' => 'InvestigationName'],
        'examFindings' => ['table' => 'tbl_ExaminationSetup', 'id' => 'ExaminationSetupID', 'label' => 'FindingName'],
        'referral' => ['table' => 'tbl_ReferralSetup', 'id' => 'ReferralSetupID', 'label' => 'ReferralText'],
        'advice'         => ['table' => 'tbl_AdviceTemplate',       'id' => 'AdviceID',        'label' => 'AdviceText'],
        'dosage'         => ['table' => 'tbl_DosagePattern',      'id' => 'DosageID',        'label' => 'PatternText'],
        'duration'       => ['table' => 'tbl_DurationPattern',    'id' => 'DurationID',      'label' => 'DurationText'],
        'instruction'    => ['table' => 'tbl_InstructionPattern', 'id' => 'InstructionID',   'label' => 'InstructionText'],
        'medicine'       => ['table' => 'tbl_Medicine',             'id' => 'MedicineID',     'label' => 'BrandName']
    ];
    public function all()
    {
        $data = [];

        foreach ($this->tables as $key => $config) {

            $data[$key] = DB::table($config['table'])
                ->where('IsActive', 1)
//                ->orderBy($config['label'])
                ->Orderby($config['label'])
                ->get();
//                ->pluck($config['label']);
        }

        return response()->json($data);
    }
    private function getTable($type)
    {
        return $this->tables[$type] ?? null;
    }




    public function search(Request $request, $type)
    {
        $config = $this->getTable($type);

        if (!$config) {
            return response()->json([
                'message' => 'Invalid lookup type'
            ], 404);
        }

        $q = $request->q;

        $data = DB::table($config['table'])
            ->select(
                $config['id'] . ' as id',
                $config['label'] . ' as label'
            )
            ->where('IsActive', 1)
            ->when($q, function ($query) use ($q, $config) {
                $query->where($config['label'], 'like', "%{$q}%");
            })
            ->orderBy($config['label'])
            ->limit(20)
            ->get();

        return response()->json($data);
    }

    public function quickAdd(Request $request, $type)
    {
        $config = $this->getTable($type);

        if (!$config) {
            return response()->json([
                'message' => 'Invalid lookup type'
            ], 404);
        }

        $label = trim($request->label);

        if (!$label) {
            return response()->json([
                'message' => 'Label is required'
            ], 422);
        }

        // Check duplicate
        $exists = DB::table($config['table'])
            ->where($config['label'], $label)
            ->first();

        if ($exists) {
            return response()->json([
                'id' => $exists->{$config['id']},
                'label' => $exists->{$config['label']}
            ]);
        }

        // Insert new
        $id = DB::table($config['table'])->insertGetId([
            $config['label'] => $label,
            'IsActive' => 1,
            'CreatedDate' => now()
        ]);

        return response()->json([
            'id' => $id,
            'label' => $label
        ]);
    }
}
