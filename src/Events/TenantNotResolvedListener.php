<?php
namespace ThinkSayDo\EnvTenant\Events;

use ThinkSayDo\EnvTenant\TenantNotResolvedException;

class TenantNotResolvedListener
{
    public function handle(TenantNotResolvedEvent $event)
    {
        throw new TenantNotResolvedException($event);
    }
}