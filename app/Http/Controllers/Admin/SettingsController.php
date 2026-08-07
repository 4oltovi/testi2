<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Саҳифаи танзимоти формулаҳо
     */
    public function formula(): View
    {
        $settings = Setting::where('group', 'formula')->orderBy('key')->get();
        return view('admin.settings.formula', compact('settings'));
    }

    /**
     * Саҳифаи танзимоти тест
     */
    public function test(): View
    {
        $settings = Setting::where('group', 'test')->orderBy('key')->get();
        return view('admin.settings.test', compact('settings'));
    }

    /**
     * Навсозии танзимот
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'required',
        ]);

        foreach ($request->input('settings') as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Танзимот бо муваффақият сабт шуд.');
    }

    /**
     * Ҳамаи танзимот (умумӣ)
     */
    public function index(): View
    {
        $formulaSettings = Setting::where('group', 'formula')->orderBy('key')->get();
        $testSettings = Setting::where('group', 'test')->orderBy('key')->get();
        $organizationSettings = Setting::where('group', 'organization')->orderBy('key')->get();

        return view('admin.settings.index', compact('formulaSettings', 'testSettings', 'organizationSettings'));
    }
    /**
     * Бор кардани логотипи муассиса
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $file = $request->file('logo');
        $name = 'logo.' . $file->getClientOriginalExtension();
        $file->move(public_path('images'), $name);

        Setting::updateOrCreate(
            ['key' => 'institution_logo'],
            [
                'value'        => 'images/' . $name,
                'type'         => 'string',
                'group'        => 'organization',
                'display_name' => 'Логотипи муассиса',
                'description'  => 'Дар барнома, ведомост ва транскрипт истифода мешавад',
                'is_public'    => 1,
            ]
        );

        return back()->with('success', 'Логотип бо муваффақият сабт шуд.');
    }
}
