<?php
namespace ThinkSayDo\EnvTenant;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('tenant', function($app)
        {
            return new TenantResolver($app);
        });
    }

    public function boot(TenantResolver $resolver)
    {
        $resolver->resolveTenant();
    }

}