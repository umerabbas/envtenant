<?php
namespace UmerAbbas\EnvTenant\Events;

use Illuminate\Queue\SerializesModels;
use UmerAbbas\EnvTenant\Tenant;

class TenantEvent {
	use SerializesModels;

	public $tenant;

	public function __construct(Tenant $tenant) {
		$this->tenant = $tenant;
	}

}