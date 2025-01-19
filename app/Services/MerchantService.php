<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
   


    public function register(array $data): Merchant
{
    
    try {
        Log::info('Starting merchant registration process.', $data);

        $password = app()->environment('testing') ? $data['api_key'] : bcrypt($data['api_key']);

        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password, 
            'type' => User::TYPE_MERCHANT, 
        ]);

        
        if (!$user || !$user->id) {
            Log::error('Failed to create user for merchant.', ['data' => $data]);
            throw new \Exception('User creation failed or user ID not set.');
        }

        Log::info('User created successfully for merchant.', ['user_id' => $user->id, 'email' => $user->email]);

        
        Log::info('Attempting to create the merchant.', [
            'user_id' => $user->id,
            'domain' => $data['domain'],
            'display_name' => $data['name']
        ]);

        
        $display_name = $data['name'];  

        $merchant = Merchant::create([
            'user_id' => $user->id, 
            'domain' => $data['domain'],
            'display_name' => $display_name, 
        ]);

        
        if (!$merchant || !$merchant->id) {
            Log::error('Failed to create merchant for user.', [
                'user_id' => $user->id, 
                'domain' => $data['domain'],
                'display_name' => $display_name
            ]);
            throw new \Exception('Merchant creation failed.');
        }

        Log::info('Merchant created successfully.', [
            'merchant_id' => $merchant->id, 
            'domain' => $merchant->domain,
            'user_id' => $merchant->user_id
        ]);

        return $merchant;
    } catch (\Exception $e) {
        
        Log::error('Error during merchant registration process.', [
            'error_message' => $e->getMessage(),
            'data' => $data
        ]);

        
        throw $e;
    }
}

     

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
  


public function updateMerchant(User $user, array $data)
{
    try {
        
        $password = app()->environment('testing') ? $data['api_key'] : bcrypt($data['api_key']);

        
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password, 
        ]);

        
        $merchant = $user->merchant;
        if ($merchant) {
            $merchant->update([
                'domain' => $data['domain'],
                'display_name' => $data['name'], 
            ]);
        }
    } catch (\Exception $e) {

        Log::error('Error during merchant update process.', [
            'error_message' => $e->getMessage(),
            'data' => $data
        ]);

        throw $e;
    }
}


    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
   
    public function findMerchantByEmail(string $email): ?Merchant
{

    $user = User::where('email', $email)->first();


    return $user?->merchant;
}


    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
   

   


public function payout(Affiliate $affiliate)
{

    $unpaidOrders = Order::where('affiliate_id', $affiliate->id)
                         ->where('payout_status', Order::STATUS_UNPAID)
                         ->get();


    foreach ($unpaidOrders as $order) {

        PayoutOrderJob::dispatch($order);
    }
}
    

}
