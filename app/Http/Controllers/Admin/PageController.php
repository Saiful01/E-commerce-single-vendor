<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $pages = Page::query()
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('title', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")))
            ->ordered()
            ->paginate(15)
            ->withQueryString();

        return view('admin.pages.index', ['pages' => $pages, 'q' => $q]);
    }

    public function create(): View
    {
        return view('admin.pages.form', [
            'page' => new Page([
                'template' => 'standard', 'is_active' => true, 'show_in_footer' => true, 'show_in_header' => false,
                'sort_order' => (int) Page::max('sort_order') + 1,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = Page::create($this->validated($request));

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return back()->with('success', 'Page saved.');
    }

    public function toggle(Page $page): RedirectResponse
    {
        $page->update(['is_active' => ! $page->is_active]);

        return back()->with('success', $page->is_active ? 'Page is now visible.' : 'Page is now hidden.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->is_system) {
            return back()->with('error', 'This page is linked from checkout or sign up, so it cannot be deleted. You can hide it instead.');
        }

        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    /* ------------------------------------------------------------------ */

    private function validated(Request $request, ?Page $page = null): array
    {
        // A page linked from checkout / sign up keeps its address
        $slug = $page?->is_system
            ? $page->slug
            : (Str::slug((string) ($request->input('slug') ?: $request->input('title'))) ?: 'page-' . Str::lower(Str::random(6)));

        $request->merge(['slug' => $slug]);

        $data = $request->validate([
            'title'            => ['required', 'string', 'max:150'],
            'slug'             => ['required', 'string', 'max:160', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'template'         => ['required', Rule::in(array_keys(Page::TEMPLATES))],
            'content'          => ['nullable', 'string', 'max:200000'],
            'faq'              => ['nullable', 'array', 'max:60'],
            'faq.*.q'          => ['nullable', 'string', 'max:300'],
            'faq.*.a'          => ['nullable', 'string', 'max:3000'],
            'meta_title'       => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $faq = collect($data['faq'] ?? [])
            ->map(fn ($row) => ['q' => trim((string) ($row['q'] ?? '')), 'a' => trim((string) ($row['a'] ?? ''))])
            ->filter(fn ($row) => $row['q'] !== '' && $row['a'] !== '')
            ->values()->all();

        return [
            'title'            => $data['title'],
            'slug'             => $data['slug'],
            'template'         => $data['template'],
            'content'          => HtmlSanitizer::clean($data['content'] ?? ''),
            'faq_items'        => $data['template'] === 'faq' ? $faq : null,
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'sort_order'       => (int) ($data['sort_order'] ?? 0),
            'is_active'        => $request->boolean('is_active'),
            'show_in_footer'   => $request->boolean('show_in_footer'),
            'show_in_header'   => $request->boolean('show_in_header'),
        ];
    }
}
