<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        $setting = SiteSetting::current();

        return view('admin.settings.edit', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $hex = 'regex:/^#[0-9A-Fa-f]{6}$/';

        $data = $request->validate([
            // General
            'site_name'    => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'footer_text'  => 'nullable|string|max:500',
            'site_logo'    => 'nullable|file|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'site_favicon' => 'nullable|file|mimes:png,ico,svg|max:1024',
            'remove_logo'    => 'nullable|boolean',
            'remove_favicon' => 'nullable|boolean',

            // Theme
            'primary_color'   => ['required', $hex],
            'secondary_color' => ['required', $hex],
            'header_bg_color' => ['required', $hex],
            'footer_bg_color' => ['required', $hex],
            'text_color'      => ['required', $hex],

            // Contact
            'primary_email'   => 'nullable|email|max:100',
            'phone_number'    => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'address'         => 'nullable|string|max:1000',
            'business_hours'  => 'nullable|string|max:255',

            // Social
            'facebook_url'  => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'youtube_url'   => 'nullable|url|max:255',
            'twitter_url'   => 'nullable|url|max:255',
            'linkedin_url'  => 'nullable|url|max:255',
            'tiktok_url'    => 'nullable|url|max:255',

            // Store
            'currency_symbol'               => 'required|string|max:10',
            'currency_code'                 => 'required|string|size:3',
            'currency_position'             => 'required|in:before,after',
            'delivery_charge_inside_dhaka'  => 'required|numeric|min:0',
            'delivery_charge_outside_dhaka' => 'required|numeric|min:0',
            'free_shipping_min'             => 'nullable|numeric|min:0',

            // SEO & tracking
            'meta_title'          => 'nullable|string|max:70',
            'meta_description'    => 'nullable|string|max:160',
            'meta_keywords'       => 'nullable|string|max:255',
            'google_analytics_id' => 'nullable|string|max:50',
            'facebook_pixel_id'   => 'nullable|string|max:50',
        ]);

        $setting = SiteSetting::current();
        $disk    = config('filesystems.default');

        // Uploads / removals are handled separately from the plain fields
        foreach (['logo' => 'site_logo', 'favicon' => 'site_favicon'] as $key => $column) {
            if ($request->hasFile($column)) {
                $this->deleteFile($setting->{$column}, $disk);
                $data[$column] = $request->file($column)->store('settings', $disk);
            } elseif ($request->boolean("remove_{$key}")) {
                $this->deleteFile($setting->{$column}, $disk);
                $data[$column] = null;
            } else {
                unset($data[$column]);
            }
            unset($data["remove_{$key}"]);
        }

        $data['currency_code'] = strtoupper($data['currency_code']);

        $setting->update($data);

        return back()->with('success', 'Site settings updated.');
    }

    private function deleteFile(?string $path, string $disk): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
