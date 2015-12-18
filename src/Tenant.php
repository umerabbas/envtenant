<?php
namespace ThinkSayDo\EnvTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Tenant extends Model
{
    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'email',
        'subdomain',
        'alias_domain',
        'connection',
        'meta'
    ];

    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'subdomain' => 'string',
        'alias_domain' => 'string',
        'connection' => 'string',
        'meta' => 'array'
    ];

    public function __construct(array $attributes = [])
    {
        $this->setConnection(config('database.default'));

        parent::__construct($attributes);
    }

    public function setSubdomainAttribute($value)
    {
        $this->attributes['subdomain'] = $this->alphaOnly($value);
    }

    public function setAliasDomainAttribute($value)
    {
        $this->attributes['alias_domain'] = $this->alphaOnly($value);
    }

    protected function alphaOnly($value)
    {
        return preg_replace('/[^[:alnum:]\-\.]/u', '', $value);
    }
}