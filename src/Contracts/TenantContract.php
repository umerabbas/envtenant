<?php
namespace UmerAbbas\EnvTenant\Contracts;

interface TenantContract {
	public function getNameAttribute();

	public function setNameAttribute($value);

	public function getEmailAttribute();

	public function setEmailAttribute($value);

	public function getSubdomainAttribute();

	public function setSubdomainAttribute($value);

	public function getConnectionAttribute();

	public function setConnectionAttribute($value);
}