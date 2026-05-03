<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WholeSaleMarketRate;
use App\Traits\APIResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProductService
{
    use APIResponseTrait;
    public function productIndex(Request $request){
        $take = $request->take;
        $search = $request->search;
        $query = Product::where('Status','=','Y')
            ->where(function ($q) use ($search){
               $q->where('ProductName','like','%'.$search.'%');
               $q->where('ProductCode','like','%'.$search.'%');
            })
        ->orderBy('ProductName','asc')->paginate($take);
        return response()->json($query);
    }

    public function getMarketPriceIndex(Request $request){
        $take = $request->take;
        $search = $request->search;
        $filters = $request->filters;
        $dateRange = $filters[0]['value'];

        $query = $this->wholesaleMarketQuery($search,$dateRange)
            ->select('WholeSaleMarketRate.EntryDate as Date','p.ProductName','l.LocationName as Region',
                'WholeSaleMarketRate.MarketPrice as MarketRate','WholeSaleMarketRate.CompanyPrice as CompanyRate')
            ->paginate($take);
        return response()->json($query);
    }
    public function getWholesaleMarketPriceIndex(Request $request){
        $take = $request->take;
        $search = $request->search;
        $filters = $request->filters;
        $dateRange = $filters[0]['value'];
        $query = $this->wholesaleMarketQuery($search,$dateRange)
            ->select(
                'p.ProductName',
                'l.LocationName as RegionName',
                'p.CompanyRate as CompanyRate',
                'WholeSaleMarketRate.MarketPrice as MarketRate',
                DB::raw('(p.CompanyRate - WholeSaleMarketRate.MarketPrice) as Difference'),
                DB::raw('ROUND(((p.CompanyRate - WholeSaleMarketRate.MarketPrice) / p.CompanyRate) * 100, 2) as PercentURate')
            )
            ->paginate($take);
        return response()->json($query);
    }
    public function wholesaleMarketQuery($search, $dateRange){

        $from = $dateRange[0] ?? null;
        $to   = $dateRange[1] ?? null;

        $query = WholeSaleMarketRate::join('Product as p','p.ProductCode','=','WholeSaleMarketRate.ProductCode')
            ->join('Location as l','l.LocationCode','=','WholeSaleMarketRate.LocationCode')
            ->where(function ($q) use ($search){
                $q->orWhere('p.ProductName','like','%'.$search.'%');
                $q->orWhere('p.ProductCode','like','%'.$search.'%');
            })
            ->when($from && $to, function($q) use($from, $to){
                $q->whereBetween('WholeSaleMarketRate.EntryDate',[
                    Carbon::createFromFormat('d-m-Y', $from)->format('Y-m-d'),
                    Carbon::createFromFormat('d-m-Y', $to)->format('Y-m-d')
                ]);
            })
            ->orderBy('p.ProductName','asc');

        return $query;
    }
    public function productInfoByID($code){
        return  Product::where('ProductCode','=',$code);
    }
    public function product()
    {
        try {
          $data = Product::where('Status','=','Y')->get();

           return $this->successResponse($data,'');

        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage());
        }
    }
    public function createProduct($data)
    {
        return Product::create([
            'ProductName' => $data['productName'],
            'ProductCode' => $data['productCode'],
            'CompanyRate' => $data['companyRate'],
            'Status'       => 'Y',
        ]);
    }

    public function updateProduct($id, $data)
    {
        $prod = $this->productInfoByID($id)->first();
        $prod->update([
            'ProductName' => $data['productName'],
            'ProductCode' => $data['productCode'],
            'CompanyRate' => $data['companyRate'],
            'Status'       => $data['status'] ?? 'Y',
        ]);
        return $prod;
    }

}