<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getMarketRate($region, $date)
    {
        $products = DB::table('WholeSaleMarketRate as pr')
            ->join('Location as l', 'l.LocationCode', '=', 'pr.LocationCode')
            ->join('product as p', 'p.ProductCode', '=', 'pr.ProductCode')
            ->where('pr.LocationCode', $region)
            ->whereDate('pr.EntryDate', $date)
            ->select(
                'p.ProductName',
                'l.LocationName as RegionName',
                'p.CompanyRate as CRate',
                'pr.MarketPrice as MRate',
                DB::raw('(p.CompanyRate - pr.MarketPrice) as Diff'),
                DB::raw('ROUND(((p.CompanyRate - pr.MarketPrice) / p.CompanyRate) * 100, 2) as PercentURate')
            )
            ->orderBy('p.ProductName')
            ->get();

        $avg = [
            'CRate'        => round($products->avg('CRate'), 2),
            'MRate'        => round($products->avg('MRate'), 2),
            'Diff'         => round($products->avg('Diff'), 2),
            'PercentURate' => round($products->avg('PercentURate'), 2),
        ];

        return [
            'products' => $products,
            'average'  => $avg
        ];
    }

    public function getWholesaleRate($date){
        $query = DB::table('Product as p')
            ->leftJoin('WholeSaleMarketRate as w', function ($join) use($date){
                $join->on('p.ProductCode', '=', 'w.ProductCode')
                    ->where('w.EntryDate','=', $date);
            })
            ->leftjoin('location as l', 'l.LocationCode', '=', 'w.LocationCode')

            ->select(
                'p.ProductCode',
                'p.ProductName',
                'p.CompanyRate',
                'w.LocationCode',
                'l.LocationName',
                'w.MarketPrice as MarketRate',
                DB::raw('ROUND(p.CompanyRate - w.MarketPrice, 2) as Diff'),
                DB::raw('ROUND(((p.CompanyRate - w.MarketPrice) / p.CompanyRate) * 100, 2) as PercentURate')
            )
            ->orderBy('p.ProductName')
            ->get();

         return $query;
    }

    public function getPivotProduct($data){

        $products = [];
        foreach ($data as $row){
            if(!isset($products[$row->ProductCode])){
                $products[$row->ProductCode] =[
                  'ProductCode'=>$row->ProductCode,
                  'ProductName'=>$row->ProductName,
                  'CompanyRate'=>$row->CompanyRate,
                  'Locations'=>[],
                ];
            }

            $products[$row->ProductCode]['Locations'][$row->LocationCode]= [
                'LocationCode' => $row->LocationCode,
                'LocationName' => $row->LocationName,
                'MarketRate'   => $row->MarketRate,
                'Diff'         => $row->Diff,
                'PercentURate' => $row->PercentURate,
            ];
        }
        return $products;
    }
}