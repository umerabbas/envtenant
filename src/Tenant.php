<?php
namespace ThinkSayDo\EnvTenant;

use Illuminate\Database\Eloquent\Model;
use ThinkSayDo\EnvTenant\Contracts\TenantContract;

class Tenant extends Model implements TenantContract {
	protected $table = 'tenants';
	protected $connection = 'envtenant';

	protected $fillable = [
		'name',
		'email',
		'subdomain',
		// 'alias_domain',
		'connection',
		'is_active',
		// 'meta'
	];

	protected $casts = [
		'name' => 'string',
		'email' => 'string',
		'subdomain' => 'string',
		// 'alias_domain' => 'string',
		'connection' => 'string',
		'is_active' => 'boolean',
		// 'meta' => 'array'
	];

	public function __construct(array $attributes = []) {
		$this->setConnection(config('database.default'));

		parent::__construct($attributes);
	}

	public function getNameAttribute() {
		return $this->attributes['name'];
	}

	public function setNameAttribute($value) {
		$this->attributes['name'] = trim($value);
	}

	public function getEmailAttribute() {
		return $this->attributes['email'];
	}

	public function setEmailAttribute($value) {
		$this->attributes['email'] = trim($value);
	}

	public function getSubdomainAttribute() {
		return $this->attributes['subdomain'];
	}

	public function setSubdomainAttribute($value) {
		$this->attributes['subdomain'] = mb_strtolower($this->_alphaOnly($value));
	}

	public function getConnectionAttribute() {
		return $this->attributes['connection'];
	}

	public function setConnectionAttribute($value) {
		$this->attributes['connection'] = strtolower(trim($value));
	}

	protected function _alphaOnly($value) {
		return preg_replace('/[^[:alnum:]\-\.]/u', '', $value);
	}
}