## EnvTenant

The Laravel EnvTenant package is designed to enable multi-tenancy based database connections on the fly without having
to access the database ```::connection('name')``` in every database call. It also allows Artisan commands to accept
a --tenant option for multi-tenant migrations. This library was forked from Tenantable, but removes the database
configuration from the tables, instead relying on ENV configuration of databases and optional table prefixing.


## Installation

Just place require new package for your laravel installation via composer.json

```
composer require thinksaydo/envtenant:2.*
```

Then hit composer dump-autoload

After updating composer, add the service provider to the providers array in config/app.php.
You should ideally have this inserted into the array after the Database and
Error Handler Laravel service providers.

### Laravel 5.2:

```php
ThinkSayDo\EnvTenant\TenantServiceProvider::class,
```

Run migrations to install the "tenants" table
```php 
artisan migrate --path /vendor/thinksaydo/envtenant/migrations
```

Then in your workflow create tenants the Eloquent way:

```php
$tenant = new \ThinkSayDo\EnvTenant\Tenant();
$tenant->name = 'ACME Inc.';
$tenant->email = 'person@acmeinc.com';
$tenant->subdomain = 'acme';
$tenant->alias_domain = 'acmeinc.com';
$tenant->connection = 'db1';
$tenant->meta = ['phone' => '123-123-1234'];
$tenant->save();
```

And that's it! Whenever your app is visited via http://acme.domain.com
the default database connection will be set with the above details.

## Compatibility

The EnvTenant package has been developed with Laravel 5.2.

## Introduction

The package simply resolves the correct connection details via the subdomain
or domain accessed via the connection name saved in the database.

Once resolved it sets the default database connection with the saved value and
optionally sets the table prefix to the subdomain value.

This prevents the need to keep switching, or accessing the right connection depending on the tenant being accessed.

This means all of your routes, models, etc will run on the active tenant database
(unless explicitly stated via ```::connection('name')```)

## Lifecycle

This is how things work during a HTTP request:

- EnvTenant copies the name of the default database connection.
- EnvTenant gets the host string via the ```Http\Request::getHost()``` method.
- EnvTenant gets the subdomain string via the ```Http\Request::getHost()``` method, getting the first portion of the domain.
- EnvTenant looks for a tenant in the database that matches this host or subdomain.
- Then the default database connection is changed to 'connection' and the old connection purged (disconnected/reconnected).
- If a match isn't found a TenantNotResolved event is fired and no config changes happen.

This is how it works during an artisan console request:

- EnvTenant copies the name of the default database connection.
- EnvTenant registers a console option of ```--tenant``` where you can supply the tenant record id, subdomain, domain, or */all to run for all tenants.
- EnvTenant checks to see if the tenant option is provided, if it isn't no tenant is resolved. The command runs normally.
- If a match is found it resolves the tenant (settings the tenant database connection) before executing the command.
- If you provide ```--tenant``` with either a ```*``` or the string ```all``` EnvTenant will run the command foreach tenant found in the database, setting the active tenant before running each time.

## The TenantResolver Class

The ```\ThinkSayDo\EnvTenant\TenantResolver``` class is responsible for resolving
and managing the active tenant during http and console access.

The ```TenantServiceProvider``` registers this class as a singleton for use anywhere in your app via method injection,
or by using the ```app('ThinkSayDo\EnvTenant\TenantResolver')``` helper function.

This class provides you with methods to access or alter the active tenant:

```php
// fetch the resolver class either via the app() function or by injecting
$resolver = app('ThinkSayDo\EnvTenant\TenantResolver');

// check if a tenant was resolved
$resolver->isResolved(); // returns bool

// get the active tenant model
$tenant = $resolver->getActiveTenant(); // returns instance of \ThinkSayDo\EnvTenant\Tenant or null

// set the active tenant
// fires a \ThinkSayDo\EnvTenant\Events\TenantActivatedEvent event
$resolver->setActiveTenant(\ThinkSayDo\EnvTenant\Tenant $tenant);

// reconnect default connection
$resolver->reconnectDefaultConnection();

// reconnect tenant connection
$resolver->reconnectTenantConnection();
```

## The Tenant Model

The ```\ThinkSayDo\EnvTenant\Tenant``` class is a very simple Eloquent model,
and a meta attribute which is cast to an array when accessed.

The model can be used in any way other Eloquent models are to create/read/update/delete:

```php
// create by mass assignment
\ThinkSayDo\EnvTenant\Tenant::create([
    'name' => 'Acme Inc.'
    ....
]);

// call then save
$tenant = \ThinkSayDo\EnvTenant\Tenant();
$tenant->name = 'Acme Inc.';
...
$tenant->save();

// fetch all tenants
$tenant = \ThinkSayDo\EnvTenant\Tenant::all();

// fetch by subdomain
$tenant = \ThinkSayDo\EnvTenant\Tenant::where('subdomain', 'acme')->first();
```

## Events

The EnvTenant packages produces a few events which can be consumed in your application

```\ThinkSayDo\EnvTenant\Events\TenantActivatedEvent(\ThinkSayDo\EnvTenant\Tenant $tenant)```

This event is fired when a tenant is set as the active tenant and has a public ```$tenant``` property
containing the ```\ThinkSayDo\EnvTenant\Tenant``` instance.

**Note** this may not be as a result of the resolver but is also fired when a tenant is set to active manually.

```\ThinkSayDo\EnvTenant\Events\TenantResolvedEvent(\ThinkSayDo\EnvTenant\Tenant $tenant)```

This event is fired when a tenant is resolved by the resolver and has a public ```$tenant``` property
containing the ```\ThinkSayDo\EnvTenant\Tenant``` instance.

**Note** this is only fired once per request as the resolver is responsible for this event.

```\ThinkSayDo\EnvTenant\Events\TenantNotResolvedEvent(\ThinkSayDo\EnvTenant\Resolver $resolver)```

This event is fired when by the resolver when it cannot resolve a tenant and has a public ```$resolver``` property
containing the ```\ThinkSayDo\EnvTenant\Resolver``` instance.

**Note** this is only fired once per request as the resolver is responsible for this event.

#### Notes on using Artisan::call();

Using the ```Artisan``` Facade to run a command provides no access to alter the applications active tenant
before running (unlike console artisan access).

Because of this the currently active tenant will be used.

To run the command foreach tenant you will need to fetch all tenants using ```Tenant::all()``` and
run the ```Artisan::call()``` method inside a foreach after setting the active tenant like so:

```php
// fetch the resolver class either via the app() function or by injecting
$resolver = app('ThinkSayDo\EnvTenant\Resolver');

// store the current tenant
$resolvedTenant = $resolver->getActiveTenant();

// fetch all tenants and loop / call command for each
$tenants = \ThinkSayDo\EnvTenant\Tenant::all();
foreach ($tenants as $tenant)
{
    $resolver->setActiveTenant($tenant);
    $result = \Artisan::call('commandname', ['array' => 'of', 'the' => 'arguments']);
}

// restore the correct tenant
$resolver->setActiveTenant($resolvedTenant);
```

If you need to run the Artisan facade on the original default connection (ie not the tenant connection)
simply call the ```TenantResolver::reconnectDefaultConnection()``` function first:

```php
// fetch the resolver class either via the app() function or by injecting
$resolver = app('ThinkSayDo\EnvTenant\Resolver');

// store the current tenant
$resolvedTenant = $resolver->getActiveTenant();

// purge the tenant from the default connection
$resolver->reconnectDefaultConnection();

// call the command
$result = \Artisan::call('commandname', ['array' => 'of', 'the' => 'arguments']);

// restore the tenant connection as the default
$resolver->reconnectTenantConnection();
```
