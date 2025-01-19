<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
 



public function orderStats(Request $request): JsonResponse
{
  
    // \Artisan::call('migrate:fresh'); 

    $merchant = auth()->user()->merchant; 

    
    $from = $request->input('from', Carbon::now()->subDays(1)->startOfDay());
    $to = $request->input('to', Carbon::now()->endOfDay());

    $orders = $merchant->orders()
        ->whereBetween('created_at', [$from, $to])
        ->get();

    $ordersWithAffiliate = $orders->where('affiliate_id', '!=', null);


    $count = $orders->count();
    $revenue = $orders->sum('subtotal');
    $commissionOwed = $ordersWithAffiliate->sum('commission_owed') ?? 0; 

    $response = [
        'count' => $count,
        'revenue' => $revenue,
        'commissions_owed' => $commissionOwed, 
    ];

    
    return response()->json($response);
}

    

}
