<?php
namespace ThinkSayDo\EnvTenant\Events;

use ThinkSayDo\EnvTenant\Resolver;

class TenantNotResolvedEvent extends TenantEvent
{
    public $resolver;

    public function __construct(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }
}