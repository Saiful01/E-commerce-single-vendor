<?php
// App\Providers\AppServiceProvider::boot() er bhitore boshan
use App\Models\Category;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;

View::composer(['layouts.*', 'home', 'products.*', 'partials.*', 'components.*'], function ($view) {
    $view->with('setting', SiteSetting::current());
});

View::composer('layouts.app', function ($view) {
    $view->with('menuCategories', Category::active()->inMenu()->whereNull('parent_id')
        ->with(['children' => fn ($q) => $q->active()->inMenu()->ordered()])
        ->ordered()->get());
});
