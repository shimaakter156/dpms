<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineController extends Controller
{
    public function search(Request $request)
    {
        $q = $request->q;

        // Empty search
        if (!$q) {
            return response()->json([]);
        }

        $medicines = DB::table('tbl_Medicine as m')
            ->leftJoin('tbl_DosageForm as df', 'df.DosageFormID', '=', 'm.DosageFormID')
            ->leftJoin('tbl_Manufacturer as mf', 'mf.ManufacturerID', '=', 'm.ManufacturerID')

            ->select(
                'm.MedicineID',
                'm.MedicineName as Name',
                'm.Strength',
                'm.GenericName',
                'df.FormName as Form',
                'mf.ManufacturerName as Manufacturer',
                'm.DefaultDosage',
                'm.DefaultDuration',
                'm.DefaultInstructions'
            )

            ->where('m.IsActive', 1)

            ->where(function ($query) use ($q) {
                $query->where('m.MedicineName', 'like', "%$q%")
                    ->orWhere('m.GenericName', 'like', "%$q%");
            })

            ->orderBy('m.MedicineName')
            ->limit(15)
            ->get();

        return response()->json($medicines);
    }
}
