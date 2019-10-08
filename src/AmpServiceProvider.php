<?php
namespace Jerichen\Amp;

use Illuminate\Support\ServiceProvider;

class AmpServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPublishables();
    }

    private function registerPublishables()
    {
        $this->app->singleton('amp', function () {
            return new AmpHelper;
        });

        $basePath = __DIR__;

        $arrPublishable = [
            'amp-migrations' => [
                "$basePath/publishable/databases/migrations/amp" => database_path('migrations'),
            ],
            'migrations' => [
                "$basePath/publishable/databases/migrations" => database_path('migrations'),
            ],
            'seeds' => [
                "$basePath/publishable/databases/seeds" => database_path('seeds'),
            ],
            'models' => [
                "$basePath/app/models/Article.php" => app_path('Models/Entities/Article.php'),
            ],
        ];

        foreach ($arrPublishable as $group => $paths) {
            $this->publishes($paths, $group);
        }
    }
}
