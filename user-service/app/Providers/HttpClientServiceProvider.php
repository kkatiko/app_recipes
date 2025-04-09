// Создайте файл app/Providers/HttpClientServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class HttpClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('http.client', function() {
            return Http::withOptions([
                'base_uri' => env('INGREDIENT_SERVICE_URL', 'http://ingredient-service'),
                'timeout' => 5,
            ]);
        });
    }
}