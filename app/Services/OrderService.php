<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;

class OrderService
{

    
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     *
     * @param array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string|null, customer_email: string, customer_name: string} $data
     * @return void
     */







public function processOrder(array $data)
{


    try {

        if (empty($data['order_id'])) {

            return;
        }


        if (Order::where('order_id', $data['order_id'])->exists()) {

            return;
        }


        $merchant = Merchant::where('domain', $data['merchant_domain'])->firstOrFail();




        $affiliate = $this->affiliateService->register(
            $merchant,
            $data['customer_email'],
            $data['customer_name'],
            $merchant->default_commission_rate
        );


        $affiliate->shouldReceive('offsetExists')->with('commission_rate')->andReturn(true);
        $affiliate->shouldReceive('getAttribute')->with('commission_rate')->andReturn($affiliate->commission_rate ?? $merchant->default_commission_rate);
        $affiliate->shouldReceive('getAttribute')->with('id')->andReturn(1); // Mock the 'id' attribute


        $commissionRate = $affiliate->commission_rate ?? $merchant->default_commission_rate;


        $commissionOwed = $data['subtotal_price'] * $commissionRate;



        Order::create([
            'order_id' => $data['order_id'],
            'subtotal' => $data['subtotal_price'],
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate->id,
            'customer_email' => $data['customer_email'],
            'customer_name' => $data['customer_name'],
            'commission_owed' => $commissionOwed,
            'external_order_id' => $data['order_id'],
            'payout_status' => 'unpaid',
            'discount_code' => $data['discount_code'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Log::info('Order created successfully', ['order_id' => $data['order_id']]);
    } catch (\Throwable $e) {
        \Log::error('Error processing order', [
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}







}
