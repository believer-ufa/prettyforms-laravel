<?php

namespace PrettyFormsLaravel;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider {

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        // Подключим парочку нужных нам функций, используемых компонентом
        include __DIR__ . '/../functions.php';
        
		$configFile = __DIR__ . '/../config/prettyforms.php';

		$this->mergeConfigFrom($configFile, 'prettyforms');

		$this->publishes([
			$configFile => config_path('prettyforms.php')
		]);

        $this->loadViewsFrom(__DIR__ . '/../views', 'prettyforms');
        
        $this->loadTranslationsFrom(__DIR__.'/../translations', 'prettyforms');
    }

    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register() {
        
    }

}
