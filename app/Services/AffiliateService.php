<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    




public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
{
    try {
        \DB::beginTransaction();

        $existingMerchant = Merchant::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();

        if ($existingMerchant) {
       
            throw new AffiliateCreateException('The provided email is already associated with a merchant.');
        }


        $existingAffiliate = Affiliate::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->first();

        if ($existingAffiliate) {
       
            throw new AffiliateCreateException('The provided email is already associated with an affiliate.');
        }


        $user = User::where('email', $email)->first();
        if (!$user) {
            
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(\Str::random(12)),
                'type' => User::TYPE_AFFILIATE,
            ]);
            Log::info('User created successfully.', ['user_id' => $user->id, 'email' => $user->email]);
        } else {
            Log::info('User already exists. Using existing user.', ['user_id' => $user->id, 'email' => $user->email]);
       
        }

        
       
        $discountCodeResponse = $this->apiService->createDiscountCode($merchant);
       
        
        if (!is_array($discountCodeResponse) || empty($discountCodeResponse['code'])) {
       
            throw new \RuntimeException('Failed to generate discount code.');
        }

        $discountCode = $discountCodeResponse['code'];
       
       
        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode,
        ]);
        Log::info('Affiliate created successfully.', ['affiliate_id' => $affiliate->id]);

       
       
        Mail::to($email)->send(new AffiliateCreated($affiliate));

       
        \DB::commit();
       
        return $affiliate;
    } catch (\Exception $e) {
        \DB::rollBack();
       
        throw new AffiliateCreateException('Failed to create affiliate: ' . $e->getMessage(), 0, $e);
    }
}


}

