<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:management-access');
    }

    public function index()
    {
        $title = 'Setting';
        $breadcrumbs = ['Setting'];
        $setting = Setting::asObject();
        $activeTab = (string) request()->query('tab', 'general');
        if (!in_array($activeTab, ['general', 'renewal_notice'], true)) {
            $activeTab = 'general';
        }

        return view('setting.index', compact('title', 'breadcrumbs', 'setting', 'activeTab'));
    }

    function store(Request $request)
    {
        $tab = (string) $request->input('tab', $request->query('tab', 'general'));
        if (!in_array($tab, ['general', 'renewal_notice'], true)) {
            $tab = 'general';
        }

        $rules = $tab === 'renewal_notice'
            ? [
                'renewal_notice_club_name' => 'required|string',
                'renewal_notice_bank_account' => 'required|string',
                'renewal_notice_admin_phone' => 'required|string',
                'renewal_notice_body_template' => 'required|string',
            ]
            : [
                'name' => 'required|string',
                'ucapan' => 'required|string',
                'deskripsi' => 'required|string',
                'logo' => 'nullable|mimes:jpg,jpeg,png,gif',
                'ppn' => 'required|numeric',
                'member_suspend_before_days' => 'required|integer|min:1',
                'member_suspend_after_days' => 'required|integer|min:0',
                'ticket_code_mode' => 'required|in:shared,unique',
                'ticket_print_orientation' => 'required|in:with_summary,without_summary',
                'ticket_valid_days' => 'required|integer|min:0',
                'ticket_scan_limit' => 'required|integer|min:0',
                'dashboard_metric_mode' => 'required|in:amount,count',
                'whatsapp_enabled' => 'nullable|boolean',
            ];

        $attr = $request->validate($rules);

        try {
            DB::beginTransaction();

            if ($tab === 'renewal_notice') {
                Setting::putMany($attr);
            } else {
                $setting = Setting::asObject();
                $logo = $request->file('logo');
                $logoUrl = null;
                $attr['use_logo'] = $request->has('use_logo') ? 1 : (int) ($setting->use_logo ?? 0);
                $attr['whatsapp_enabled'] = $request->has('whatsapp_enabled') ? 1 : 0;

                if ($logo) {
                    $disk = Storage::disk('public');
                    if (!empty($setting->logo) && $disk->exists($setting->logo)) {
                        $disk->delete($setting->logo);
                    }
                    $logoUrl = $logo->storeAs(
                        'logo',
                        now('Asia/Jakarta')->format('ymdHis') . rand(100, 990) . '.' . $logo->extension(),
                        'public'
                    );
                    $attr['use_logo'] = 1;
                } else {
                    $logoUrl = $setting->logo ?? null;
                }

                $attr['logo'] = $logoUrl;
                Setting::putMany($attr);
            }

            DB::commit();
            return redirect()
                ->route('setting.index', ['tab' => $tab])
                ->with('success', "Setting successfully saved");
        } catch (\Throwable $th) {
            DB::rollBack();
            return back()->with('error', $th->getMessage());
        }
    }
}
