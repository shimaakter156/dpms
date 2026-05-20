<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\DeclareDeclare;

class MedicineController extends Controller
{
    public function modal()
    {
        return response()->json([
            'unit' => $this->lookupRows(
                ['tbl_Unit'],
                'UnitID',
                'UnitName',
                ['UnitID', 'UnitName','UnitCode','IsActive']
            ),
            'strength' => $this->lookupRows(
                ['tbl_MedicineStrength'],
                'StrengthID',
                'StrengthLabel',
                ['StrengthID', 'StrengthValue','StrengthLabel','UnitID','IsActive']
            ),
            'generics' => $this->lookupRows(
                ['tbl_Generic'],
                'GenericID',
                'GenericName',
                ['GenericID', 'GenericName','IsActive']
            ),
            'dosageForms' => $this->lookupRows(
                ['tbl_DosageForm'],
                'DosageFormID',
                'FormName',
                ['DosageFormID', 'FormName','Abbreviation','IsActive']
            ),
            'categories' => $this->lookupRows(
                ['tbl_MedicineCategory'],
                'MedicineCategoryID',
                'CategoryName',
                ['MedicineCategoryID', 'CategoryName','IsActive']
            ),
            'manufacturers' => $this->lookupRows(
                ['tbl_Manufacturer'],
                'ManufacturerID',
                'ManufacturerName',
                ['ManufacturerID', 'ManufacturerName','IsActive']
            ),
        ]);
    }
    private function lookupRows(array $tables, string $idColumn, string $labelColumn, array $columns)
    {
        $table = $this->firstExistingTable($tables);
        if (!$table) return [];

        $select = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $select[] = $column;
            }
        }

        if (!Schema::hasColumn($table, $idColumn) || !Schema::hasColumn($table, $labelColumn)) {
            return [];
        }

        $query = DB::table($table)->select($select ?: [$idColumn, $labelColumn]);

        if (Schema::hasColumn($table, 'IsActive')) {
            $query->where('IsActive', 1);
        }
        if (Schema::hasColumn($table, 'IsDeleted')) {
            $query->where('IsDeleted', 0);
        }

        return $query->orderBy($labelColumn)->get();
    }
    public function addFromPrescription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'BrandName' => 'required|string|max:255',
            'GenericID' => 'nullable',
            'GenericName' => 'nullable|string|max:255',
            'DosageFormID' => 'nullable',
            'MedicineCategoryID' => 'nullable',
            'ManufacturerID' => 'nullable',
            'Strength' => 'required|string|max:100',
            'Unit' => 'nullable|string|max:100',
            'UnitPrice' => 'nullable|numeric|min:0',
            'DoctorID' => 'nullable',
            'HospitalID' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $ownerDoctorID = $request->filled('DoctorID') ? $request->input('DoctorID') : null;
        $ownerHospitalID = $request->filled('HospitalID') ? $request->input('HospitalID') : null;

        if ($ownerHospitalID) {
            $ownerDoctorID = null;
        }
        $medicineTable = 'tbl_Medicine';
        $genericID = $request->input('GenericID');
        $genericName = trim((string) $request->input('GenericName', ''));


        if (!$genericID && $genericName !== '') {

            $genericID = $this->findOrCreateGeneric($genericName ,$ownerDoctorID,$ownerHospitalID);
        }


        $payload =  [
            'BrandName' => $request->input('BrandName'),
            'GenericName' => $genericName ?: null,
            'GenericID' => $genericID,
//            'UnitID' => $unitID,
//            'StrengthID' => $strengthID,
            'DosageFormID' => $request->input('DosageFormID'),
            'MedicineCategoryID' => $request->input('MedicineCategoryID'),
            'ManufacturerID' => $request->input('ManufacturerID'),
            'Strength' => $request->input('Strength'),
            'Unit' => $request->input('Unit'),
            'UnitPrice' => $request->input('UnitPrice'),
            'IsSystemMedicine' => 0,
            'DoctorID' => $ownerDoctorID,
            'HospitalID' => $ownerHospitalID,
            'IsActive' => 1,
            'IsDeleted' => 0,
            'CreatedBy' => $request->user()->id ?? null,
            'CreatedDate' => now(),
        ];

        $medicineID = DB::table($medicineTable)->insertGetId($payload);
        $medicine = (array) DB::table($medicineTable)
            ->where($this->primaryKeyFor($medicineTable, 'MedicineID'), $medicineID)
            ->first();

        $medicine['MedicineID'] = $medicine['MedicineID'] ?? $medicineID;
        $medicine['BrandName'] = $medicine['BrandName'] ?? $request->input('BrandName');
        $medicine['GenericName'] = $medicine['GenericName'] ?? $genericName;
        $medicine['Strength'] = $medicine['Strength'] ?? $request->input('Strength');
        $medicine['MedicineName'] = trim($medicine['BrandName'] . ' (' . $medicine['Strength'] . ')');

        return response()->json([
            'message' => 'Medicine added.',
            'data' => $medicine,
            'medicine' => $medicine,
        ]);
    }



    private function findOrCreateGeneric(string $genericName ,$ownerDoctorID,$ownerHospitalID)
    {
        $table = 'tbl_Generic';

        $existing = DB::table($table)
            ->whereRaw('LOWER(GenericName) = ?', [strtolower($genericName)])
            ->where('IsActive','=',1)
            ->first();


        if ($existing) {
            return $existing->GenericID ?? null;
        }
        $payload = $this->filterColumns($table, [
            'GenericName' => $genericName,
            'IsActive' => 1,
            'DoctorID' => $ownerDoctorID?Auth::user()->UserID:null,
            'HospitalID' => $ownerHospitalID?Auth::user()->UserID:null,
        ]);

        return DB::table($table)->insertGetId($payload);
    }

    private function firstExistingTable(array $tables)
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function filterColumns(string $table, array $payload)
    {
        return collect($payload)
            ->filter(function ($value, $column) use ($table) {
                return Schema::hasColumn($table, $column);
            })
            ->all();
    }

    private function primaryKeyFor(string $table, string $preferred)
    {
        if (Schema::hasColumn($table, $preferred)) {
            return $preferred;
        }

        return Schema::hasColumn($table, 'id') ? 'id' : $preferred;
    }
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
