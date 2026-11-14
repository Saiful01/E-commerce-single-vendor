<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::active()->where('slug', $slug)->firstOrFail();

        return view('pages.show', [
            'page'   => $page,
            'others' => Page::active()->where('id', '!=', $page->id)->where('show_in_footer', true)->ordered()->get(['id', 'title', 'slug']),
        ]);
    }
}
