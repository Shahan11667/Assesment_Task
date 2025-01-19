<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * You don't need to do anything here. This is just to help
 */
class ApiService
{
    /**
     * Create a new discount code for an affiliate
     *
     * @param Merchant $merchant
     *
     * @return array{id: int, code: string}
     */
    public function createDiscountCode(Merchant $merchant): array
    {
        return [
            'id' => rand(0, 100000),
            'code' => Str::uuid()
        ];
    }

    /**
     * Send a payout to an email
     *
     * @param  string $email
     * @param  float $amount
     * @return void
     * @throws RuntimeException
     */
    
     public function sendPayout(string $email, float $amount): array
     {
        
        
         if (app()->environment('testing')) {
        
             throw new \RuntimeException('Simulated API failure');
         }
     
        
         if ($amount <= 0) {
             throw new \RuntimeException('Invalid payout amount.');
         }
     
         return [
             'status' => 'success',
             'transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
         ];
     }
     
    


}
