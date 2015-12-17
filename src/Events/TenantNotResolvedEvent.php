<?php
namespace ThinkSayDo\EnvTenant\Events;

use ThinkSayDo\EnvTenant\TenantResolver;

class TenantNotResolvedEvent extends TenantEvent
{
    public $resolver;

    public function __construct(TenantResolver $resolver)
    {
        $this->resolver = $resolver;
    }
}