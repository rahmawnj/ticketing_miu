<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SecretSettingController extends Controller
{
    public function index(Request $request)
    {
        $expectedKey = (string) env('SECRET_SETTING_KEY', '');

        if ($expectedKey === '') {
            return view('setting.secret-key', [
                'error' => 'SECRET_SETTING_KEY belum diatur di .env.',
                'hasKey' => false,
            ]);
        }

        if (!$this->isAuthorized($request, $expectedKey)) {
            return view('setting.secret-key', [
                'error' => (string) $request->session()->pull('secret_setting_error', ''),
                'hasKey' => true,
            ]);
        }

        $isActive = (int) Setting::valueOf('website_status', 1) === 1;

        return view('setting.secret', [
            'isActive' => $isActive,
        ]);
    }

    public function store(Request $request)
    {
        $expectedKey = (string) env('SECRET_SETTING_KEY', '');

        if ($expectedKey === '') {
            if ($request->has('website_status')) {
                return response()->json(['message' => 'SECRET_SETTING_KEY belum diatur.'], 500);
            }

            return back()->with('secret_setting_error', 'SECRET_SETTING_KEY belum diatur di .env.');
        }

        if ($request->has('website_status')) {
            if (!$this->isAuthorized($request, $expectedKey)) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            $data = $request->validate([
                'website_status' => 'required|in:0,1',
            ]);

            Setting::putMany([
                'website_status' => (int) $data['website_status'],
            ]);

            return response()->json([
                'message' => 'Status website tersimpan.',
                'website_status' => (int) $data['website_status'],
            ]);
        }

        $data = $request->validate([
            'key' => 'required|string',
        ]);

        if (!$this->isValidKey($data['key'], $expectedKey)) {
            return back()->with('secret_setting_error', 'PIN tidak valid.');
        }

        $request->session()->put('secret_setting_auth', $expectedKey);

        return redirect()->route('secret-setting.index');
    }

    private function isAuthorized(Request $request, string $expected): bool
    {
        $stored = (string) $request->session()->get('secret_setting_auth', '');

        if ($stored === '') {
            return false;
        }

        return hash_equals($expected, $stored);
    }

    private function isValidKey(string $provided, string $expected): bool
    {
        if ($provided === '' || $expected === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }
}
