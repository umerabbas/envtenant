<?php
namespace UmerAbbas\EnvTenant\Events;

class TenantNotResolvedException extends \Exception {
	public function getTenant() {
		return $this->getMessage();
	}
}