<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // $setting sob storefront ar auth view e pabe
        View::composer([
            'layouts.*', 'home', 'products.*', 'partials.*', 'components.*',
            'shop.*', 'cart.*', 'checkout.*', 'orders.*', 'account.*', 'auth.*', 'pages.*',
        ], function ($view) {
            $view->with('setting', SiteSetting::current());
        });

        // Header / footer / mobile menu er category list
        View::composer('layouts.app', function ($view) {
            $view->with('menuCategories', Category::active()->inMenu()->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->active()->inMenu()->ordered()])
                ->ordered()->get())
                ->with('footerPages', Page::active()->where('show_in_footer', true)->ordered()->get(['id', 'title', 'slug']))
                ->with('headerPages', Page::active()->where('show_in_header', true)->ordered()->get(['id', 'title', 'slug']));
        });
    }
}
