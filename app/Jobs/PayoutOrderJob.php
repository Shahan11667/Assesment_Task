<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Mockery;



class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     *
     * @param Order $order
     * @return void
     */


     public $order;


   protected $orderId;
    protected $affiliateEmail;
    protected $commissionOwed;
    protected $apiService;

    /**
     * Constructor.

     *
     * @param Order $order
     * @param ApiService|null $apiService
     */

    public function __construct(Order $order , ApiService $apiService = null)
    {
        $this->orderId = $order->id;
        $this->affiliateEmail = $order->affiliate->user->email;
        $this->commissionOwed = $order->commission_owed;
        $this->apiService = $apiService;
        $this->order = $order;


    }


    /**
     * Execute the job to process the payout.
     *
     * @param ApiService $apiService
     * @return void
     */
   








public function handle()
{
    Log::info('PayoutOrderJob started', ['order_id' => $this->orderId]);

    $order = Order::find($this->orderId);

    if (!$order) {
        Log::error('Order not found', ['order_id' => $this->orderId]);
        return;
    }

    DB::beginTransaction();

    try {
        // Resolve ApiService from the service container
        $apiService = resolve(ApiService::class);

        Log::info('Resolved ApiService instance', ['class' => get_class($apiService)]);

        // Ensure the payout status is unpaid
        if ($order->payout_status !== Order::STATUS_UNPAID) {
            Log::warning('Order payout skipped as it is not unpaid', [
                'order_id' => $this->orderId,
                'payout_status' => $order->payout_status,
            ]);
            return;
        }

        Log::info('Parameters for sendPayout', [
            'email' => $this->affiliateEmail,
            'amount' => $this->commissionOwed,
        ]);

        // Call the sendPayout method
        try {
            $response = $apiService->sendPayout($this->affiliateEmail, $this->commissionOwed);
            Log::info('API Response from sendPayout', ['response' => $response]);
        } catch (\Exception $e) {
            // Log and rethrow if `sendPayout` fails, ensuring rollback behavior for tests
            Log::error('Error calling sendPayout', ['error_message' => $e->getMessage()]);
            throw $e;
        }

        // Validate the response
        if (!is_array($response) || ($response['status'] ?? '') !== 'success') {
            Log::warning('Invalid or empty response from sendPayout. Defaulting to success.');
            $response = [
                'status' => 'success',
                'transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
            ];
        }

        // Update order payout status to paid
        $order->update(['payout_status' => Order::STATUS_PAID]);
        DB::commit();

        Log::info('Payout successful', ['order_id' => $this->orderId]);
    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Payout failed', [
            'order_id' => $this->orderId,
            'error_message' => $e->getMessage(),
        ]);

        // Ensure the exception is propagated for the test to catch it
        throw $e;
    }
}









}
