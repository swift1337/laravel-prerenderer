# Laravel prerender.io integration 
Based on [nutsweb/laravel-prerender](https://github.com/jeroennoten/Laravel-Prerender) package

## Installation
1) `composer require swift1337/laravel-prerenderer`
2) `php artisn vendor:publish --provider="Swift1337\Prerender\PrerenderServiceProvider"`
3) Fill `.env` credentials described in `config/prerender.php`

## Usage
Middleware can be accessed by alias `prerender` like:
```php
class PageController {
    public function __construct() {
        $this->middleware('prerender'); 
    }
}
```

Or you can use anywhere in Kernel.php by full classname `Swift1337\Prerender\Middleware\PrerenderPage` 
```php
// Kernel.php
protected $middleware = [
    \App\Http\Middleware\CheckForMaintenanceMode::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    \App\Http\Middleware\TrustProxies::class,
    \Swift1337\Prerender\Middleware\PrerenderPage::class, // ✔︎
];
```
 
