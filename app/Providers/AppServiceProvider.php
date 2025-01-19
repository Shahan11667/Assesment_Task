<?php

namespace App\Providers;

use App\Services\MerchantService;
use App\Interfaces\MerchantServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    // public function register()
    // {
    
    // }

    public function register()
    {
        $this->app->singleton(ApiService::class, function ($app) {
            return new ApiService();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
