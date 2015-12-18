<?php
namespace ThinkSayDo\EnvTenant\Events;

class TenantNotResolvedListener
{
    public function handle(TenantNotResolvedEvent $event)
    {
        throw new TenantNotResolvedException($event->tenant);
    }
}