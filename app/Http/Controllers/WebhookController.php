<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Pass the necessary data to the process order method
     * 
     * @param  Request $request
     * @return JsonResponse
     */


public function __invoke(Request $request): JsonResponse
{
    \Log::info('Incoming webhook request data: ' . json_encode($request->all()));

    try {
        $data = $request->validate([
            'order_id' => 'required|string',
            'subtotal_price' => 'required|numeric',
            'merchant_domain' => 'required|string',
            'discount_code' => 'nullable|string',
            'customer_email' => 'nullable|string|email',
            'customer_name' => 'nullable|string',
        ]);

        \Log::info('Validated webhook request data: ' . json_encode($data));

        app(OrderService::class)->processOrder($data);


        \Log::info('Order processed successfully for order_id: ' . $data['order_id']);

        return response()->json(['message' => 'Order processed successfully.'], 200);
    } catch (\Throwable $e) {

        \Log::error('Error processing webhook: ' . $e->getMessage(), [
            'exception' => $e,
            'request_data' => $request->all(),
        ]);

        return response()->json(['error' => 'Failed to process order.'], 500);
    }
}





}
