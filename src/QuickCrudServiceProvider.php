<?php

namespace mohamedelaraby\QuickCrud;

use Illuminate\Support\ServiceProvider;

class QuickCrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // تسجيل الكوماند
        if ($this->app->runningInConsole()) {
            $this->commands([
                \mohamedelaraby\QuickCrud\Console\Commands\GenerateCrud::class,
            ]);
        }

        // نشر الـ stubs إذا لزم
        $this->publishes([
            __DIR__.'/../resources/stubs' => resource_path('stubs/vendor/quickcrud'),
        ], 'stubs');
    }

    public function register()
    {
        // أي عمليات تسجيل إضافية
    }
}