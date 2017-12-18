<?php
namespace Pepijnolivier\Poloniex;

use Illuminate\Support\ServiceProvider;

class PoloniexServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/poloniex.php' => base_path('config/poloniex.php'),
        ], 'config');
        
        $file = base_path('config/poloniex.php');

        if(!file_exists($file))
        {
            $file = __DIR__ . '/../config/poloniex.php';
        }

        config()
            ->set('poloniex', require realpath($file));
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('poloniex', function() {
            return new PoloniexManager;
        });
    }
}