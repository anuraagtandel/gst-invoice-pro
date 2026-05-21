<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Paginator::useTailwind();

        Blade::directive('inr', function ($amount) {
            return "<?php echo '₹' . preg_replace('/(\d+?)(?=(\d\d)+(\d)(?!\d))(\.\d+)?/i', \"$1,\", number_format($amount, 2)); ?>";
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Try-catch in case settings table isn't migrated yet during setup
        try {
            $firmSettings = Setting::all()->pluck('value', 'key')->toArray();
            \Illuminate\Support\Facades\View::share('firm', $firmSettings);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\View::share('firm', []);
        }
    }
}
