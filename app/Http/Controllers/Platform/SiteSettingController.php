<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function edit()
    {
        abort_unless(auth()->user()?->hasPermission('appearance'), 403, 'غير مصرح لك بتعديل مظهر ونصوص الموقع.');
        $settings = SiteSetting::getAll();
        return view('platform.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()?->hasPermission('appearance'), 403, 'غير مصرح لك بتعديل مظهر ونصوص الموقع.');
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:150'],
            'site_icon' => ['nullable', 'string', 'max:50'],
            'hero_badge' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'hero_cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'site_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'site_favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,webp', 'max:2048'],
        ]);

        // حفظ النصوص والإعدادات العامة
        foreach ($validated as $key => $value) {
            if (!in_array($key, ['hero_cover_image', 'site_logo', 'site_favicon'], true)) {
                $group = str_starts_with($key, 'hero_') ? 'hero' : (str_starts_with($key, 'meta_') ? 'seo' : 'general');
                SiteSetting::set($key, $value, $group);
            }
        }

        // معالجة صورة الغلاف
        if ($request->boolean('delete_hero_cover')) {
            $current = SiteSetting::get('hero_cover_image');
            if ($current) {
                Storage::disk('public')->delete($current);
                SiteSetting::set('hero_cover_image', null, 'hero');
            }
        } elseif ($request->hasFile('hero_cover_image')) {
            $current = SiteSetting::get('hero_cover_image');
            if ($current) {
                Storage::disk('public')->delete($current);
            }
            $path = $request->file('hero_cover_image')->store('settings', 'public');
            SiteSetting::set('hero_cover_image', $path, 'hero');
        }

        // معالجة الشعار
        if ($request->boolean('delete_site_logo')) {
            $current = SiteSetting::get('site_logo');
            if ($current) {
                Storage::disk('public')->delete($current);
                SiteSetting::set('site_logo', null, 'general');
            }
        } elseif ($request->hasFile('site_logo')) {
            $current = SiteSetting::get('site_logo');
            if ($current) {
                Storage::disk('public')->delete($current);
            }
            $path = $request->file('site_logo')->store('settings', 'public');
            SiteSetting::set('site_logo', $path, 'general');
        }

        // معالجة الأيقونة المفضلة Favicon
        if ($request->hasFile('site_favicon')) {
            $current = SiteSetting::get('site_favicon');
            if ($current) {
                Storage::disk('public')->delete($current);
            }
            $path = $request->file('site_favicon')->store('settings', 'public');
            SiteSetting::set('site_favicon', $path, 'general');
        }

        return redirect()->route('platform.settings.edit')->with('status', 'تم حفظ إعدادات الموقع ونصوص الواجهة وصورة الغلاف بنجاح.');
    }
}
