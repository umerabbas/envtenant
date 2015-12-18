<?php
namespace ThinkSayDo\EnvTenant;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Illuminate\Console\Application as Artisan;
use ThinkSayDo\EnvTenant\Events\SetActiveTenantEvent;
use ThinkSayDo\EnvTenant\Events\TenantActivated;
use ThinkSayDo\EnvTenant\Events\TenantResolvedEvent;
use ThinkSayDo\EnvTenant\Events\TenantNotResolvedEvent;
use ThinkSayDo\EnvTenant\Events\TenantNotResolvedException;

class TenantResolver
{
    protected $app = null;
    protected $request = null;
    protected $activeTenant = null;
    protected $consoleDispatcher = false;
    protected $defaultConnection = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->defaultConnection = $this->app['db']->getDefaultConnection();
    }

    public function setActiveTenant(Tenant $activeTenant)
    {
        $this->activeTenant = $activeTenant;
        $this->setDefaultConnection($activeTenant);

        event(new TenantActivatedEvent($activeTenant));
    }

    public function getActiveTenant()
    {
        return $this->activeTenant;
    }

    public function setDefaultConnection(Tenant $activeTenant)
    {
        $connection = $activeTenant->connection ?: $this->defaultConnection;
        $prefix = ($activeTenant->connection && $activeTenant->subdomain) ? $activeTenant->subdomain . '_' : '';

        config()->set('database.default', $connection);
        config()->set('database.connections.' . $connection . '.prefix', $prefix);

        $this->app['db']->purge($this->defaultConnection);
        $this->app['db']->setDefaultConnection($connection);
    }

    public function resolveTenant()
    {
        $this->registerTenantConsoleArgument();
        $this->registerConsoleStartEvent();
        $this->registerConsoleTerminateEvent();
        $this->resolveRequest();
    }

    public function isResolved()
    {
        return ! is_null($this->getActiveTenant());
    }

    public function purgeTenantConnection()
    {
        $this->app['db']->setDefaultConnection($this->defaultConnection);
    }

    public function reconnectTenantConnection()
    {
        $this->app['db']->setDefaultConnection($this->getActiveTenant()->connection);
    }

    protected function resolveRequest()
    {
        if ($this->app->runningInConsole())
        {
            $domain = (new ArgvInput())->getParameterOption('--tenant', null);

            $model = new Tenant();
            $tenant = $model
                ->where('subdomain', '=', $domain)
                ->orWhere('alias_domain', '=', $domain)
                ->orWhere('id', '=', $domain)
                ->first();
        }
        else
        {
            $this->request = $this->app->make(Request::class);
            $domain = $this->request->getHost();
            $subdomain = explode('.', $domain)[0];

            $model = new Tenant();
            $tenant = $model
                ->where('subdomain', '=', $subdomain)
                ->orWhere('alias_domain', '=', $domain)
                ->first();
        }

        if ($tenant instanceof Tenant)
        {
            $this->setActiveTenant($tenant);

            event(new TenantResolvedEvent($tenant));

            return;
        }

        event(new TenantNotResolvedEvent($domain));

        throw new TenantNotResolvedException($domain);

        return;
    }

    private function getConsoleDispatcher()
    {
        if (!$this->consoleDispatcher)
        {
            $this->consoleDispatcher = app(EventDispatcher::class);
        }

        return $this->consoleDispatcher;
    }

    private function registerTenantConsoleArgument()
    {
        $this->app['events']->listen('artisan.start', function(Artisan $app)
        {
            $definition = $app->getDefinition();

            $definition->addOption(
                new InputOption('--tenant', null, InputOption::VALUE_OPTIONAL, 'The tenant subdomain or alias domain the command should be run for. Use * or all for every tenant.')
            );

            $app->setDefinition($definition);
            $app->setDispatcher($this->getConsoleDispatcher());
        });
    }

    private function registerConsoleStartEvent()
    {
        $this->getConsoleDispatcher()->addListener(ConsoleEvents::COMMAND, function(ConsoleCommandEvent $event)
        {
            $tenant = $event->getInput()->getParameterOption('--tenant', null);

            if ( ! is_null($tenant))
            {
                if ($tenant == '*' || $tenant == 'all')
                {
                    $event->disableCommand();
                }
                else
                {
                    if ($this->isResolved())
                    {
                        $event->getOutput()->writeln('<info>Running command for ' . $this->getActiveTenant()->name . '</info>');
                    }
                    else
                    {
                        $event->getOutput()->writeln('<error>Failed to resolve tenant</error>');
                        $event->disableCommand();
                    }
                }
            }
        });
    }

    private function registerConsoleTerminateEvent()
    {
        $this->getConsoleDispatcher()->addListener(ConsoleEvents::TERMINATE, function(ConsoleTerminateEvent $event)
        {
            $tenant = $event->getInput()->getParameterOption('--tenant', null);

            if( ! is_null($tenant))
            {
                if ($tenant == '*' || $tenant == 'all')
                {
                    $command = $event->getCommand();
                    $input = $event->getInput();
                    $output = $event->getOutput();
                    $exitCode = $event->getExitCode();

                    $tenants = Tenant::all();

                    foreach($tenants as $tenant)
                    {
                        $this->setActiveTenant($tenant);

                        $event->getOutput()->writeln('<info>Running command for ' . $this->getActiveTenant()->name . '</info>');

                        try
                        {
                            $exitCode = $command->run($input, $output);
                        }
                        catch (\Exception $e)
                        {
                            $event = new ConsoleExceptionEvent($command, $input, $output, $e, $e->getCode());

                            $this->getConsoleDispatcher()->dispatch(ConsoleEvents::EXCEPTION, $event);

                            $e = $event->getException();

                            throw $e;
                        }
                    }

                    $event->setExitCode($exitCode);
                }
            }
        });
    }
}
