<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkplaceController extends Controller
{
    public function forDoctor($doctorId)
    {
        // Chamber workplaces
        $chambers = DB::table('tbl_DoctorWorkplace as w')
            ->join('tbl_DoctorChamber as c', 'c.DoctorChamberID', '=', 'w.ChamberID')
            ->where('w.DoctorID', $doctorId)
            ->where('w.WorkplaceType', 'chamber')
            ->where('w.IsActive', 1)
            ->select(
                'w.WorkplaceID',
                DB::raw("'chamber' as Type"),
                'c.ChamberName as Name',
                'c.Address',
                'c.Phone',
                'c.Email',
                'w.Fee',
                'w.VisitDays',
                'w.VisitTime',
                'w.IsDefault'
            );

        // Hospital workplaces (union)
        $hospitals = DB::table('tbl_DoctorWorkplace as w')
            ->join('tbl_Hospital as h', 'h.HospitalID', '=', 'w.HospitalID')
            ->where('w.DoctorID', $doctorId)
            ->where('w.WorkplaceType', 'hospital')
            ->where('w.IsActive', 1)
            ->select(
                'w.WorkplaceID',
                DB::raw("'hospital' as Type"),
                'h.HospitalName as Name',
                'h.Address',
                'h.Phone',
                'h.Email',
                'w.Fee',
                'w.VisitDays',
                'w.VisitTime',
                'w.IsDefault'
            );

        $all = $chambers->union($hospitals)
            ->orderByDesc('IsDefault')
            ->orderBy('Name')
            ->get();

        return response()->json(['data' => $all]);
    }

    public function show($workplaceId)
    {
        $w = DB::table('tbl_DoctorWorkplace')->where('WorkplaceID', $workplaceId)->first();
        if (!$w) return response()->json(['message' => 'Workplace not found'], 404);

        $details = null;
        if ($w->WorkplaceType === 'chamber') {
            $details = DB::table('tbl_DoctorChamber')
                ->where('DoctorChamberID', $w->ChamberID)->first();
            $name = $details->ChamberName ?? '';
        } else {
            $details = DB::table('tbl_Hospital')
                ->where('HospitalID', $w->HospitalID)->first();
            $name = $details->HospitalName ?? '';
        }

        return response()->json(['data' => [
            'WorkplaceID' => $w->WorkplaceID,
            'Type'        => $w->WorkplaceType,
            'Name'        => $name,
            'Address'     => $details->Address ?? null,
            'Phone'       => $details->Phone ?? null,
            'Email'       => $details->Email ?? null,
            'Fee'         => $w->Fee,
            'VisitDays'   => $w->VisitDays,
            'VisitTime'   => $w->VisitTime,
            'IsDefault'   => (bool) $w->IsDefault,
        ]]);
    }

    public function setDefault(Request $req, $workplaceId)
    {
        $w = DB::table('tbl_DoctorWorkplace')->where('WorkplaceID', $workplaceId)->first();
        if (!$w) return response()->json(['message' => 'Not found'], 404);

        DB::transaction(function () use ($w, $workplaceId) {
            // Clear default flag for this doctor
            DB::table('tbl_DoctorWorkplace')
                ->where('DoctorID', $w->DoctorID)
                ->update(['IsDefault' => 0]);
            // Set new default
            DB::table('tbl_DoctorWorkplace')
                ->where('WorkplaceID', $workplaceId)
                ->update(['IsDefault' => 1]);
        });
        return response()->json(['message' => 'Default workplace updated.']);
    }
}
