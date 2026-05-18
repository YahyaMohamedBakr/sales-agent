<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\Setting\Models\Setting;
use App\Domains\Setting\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settings,
    ) {}

    public function index()
    {
        $all = Setting::all(['group', 'key', 'value', 'type']);
        $groups = $all->groupBy('group')->map(fn ($items) => $items->toArray());

        return response()->json($groups);
    }

    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $this->settings->set(
            $request->key,
            $request->value ?? '',
            $request->group ?? 'general',
        );

        return response()->json(['status' => 'saved']);
    }
}
