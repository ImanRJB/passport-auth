<?php

namespace PassportAuth;

use Illuminate\Support\ServiceProvider;
use PassportAuth\Console\Commands\Purge;
use Illuminate\Database\Connection;

class PassportAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(Connection::class, function() {
            return $this->app['db.connection'];
        });

        if (preg_match('/5\.[678]\.\d+/', $this->app->version())) {
            $this->app->singleton(\Illuminate\Hashing\HashManager::class, function ($app) {
                return new \Illuminate\Hashing\HashManager($app);
            });
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Purge::class
            ]);
        }
    }

    public function register()
    {
        // For load config files
//        if (file_exists(__DIR__ . '/../src/config/bitcoind.php')) {
//            $this->mergeConfigFrom(__DIR__ . '/../src/config/bitcoind.php', 'bitcoind');
//        }
        $this->app->register(Laravel\Passport\PassportServiceProvider::class);
    }
}
