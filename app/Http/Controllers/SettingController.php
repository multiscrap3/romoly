<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\SumberTransaksi;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    /**
     * Display settings
     */
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'mata_uang' => 'nullable|string|max:10',
            'format_tanggal' => 'nullable|string|max:20',
            'zona_waktu' => 'nullable|string|max:50',
            'bahasa' => 'nullable|string|max:10',
            'notifikasi_email' => 'nullable|boolean',
            'notifikasi_push' => 'nullable|boolean',
            'notifikasi_anggaran' => 'nullable|boolean',
            'notifikasi_tabungan' => 'nullable|boolean',
            'notifikasi_hutang' => 'nullable|boolean',
            'tema' => 'nullable|in:light,dark,auto',
        ]);

        try {
            $household_id = auth()->user()->household_id;

            foreach ($request->except('_token', '_method') as $key => $value) {
                Setting::updateOrCreate(
                    [
                        'household_id' => $household_id,
                        'key' => $key,
                    ],
                    [
                        'value' => $value ?? '0',
                    ]
                );
            }

            return back()->with('success', 'Pengaturan berhasil disimpan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan pengaturan: ' . $e->getMessage());
        }
    }

    /**
     * Reset settings to default
     */
    public function reset()
    {
        try {
            $household_id = auth()->user()->household_id;

            // Delete all settings for this household
            Setting::where('household_id', $household_id)->delete();

            // Recreate default settings
            $defaults = [
                'mata_uang' => 'IDR',
                'format_tanggal' => 'd/m/Y',
                'zona_waktu' => 'Asia/Jakarta',
                'bahasa' => 'id',
                'notifikasi_email' => '1',
                'notifikasi_push' => '1',
                'notifikasi_anggaran' => '1',
                'notifikasi_tabungan' => '1',
                'notifikasi_hutang' => '1',
                'tema' => 'light',
            ];

            foreach ($defaults as $key => $value) {
                Setting::create([
                    'household_id' => $household_id,
                    'key' => $key,
                    'value' => $value,
                ]);
            }

            return back()->with('success', 'Pengaturan berhasil direset ke default');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal reset pengaturan: ' . $e->getMessage());
        }
    }

    /**
     * Update preferensi tampilan & PDP (AI opt-out)
     */
    public function updatePreferences(Request $request)
    {
        $supported = \App\Http\Middleware\LocaleMiddleware::SUPPORTED_LOCALES;

        $request->validate([
            'theme'           => 'nullable|in:light,dark,system',
            'language'        => 'nullable|string|in:' . implode(',', $supported),
            'ai_opt_out'      => 'nullable|boolean',
            'ai_ocr_opt_out'  => 'nullable|boolean',
        ]);

        try {
            $householdId = auth()->user()->household_id;
            $language    = $request->input('language', 'id');
            $data = [
                'theme'          => $request->input('theme', 'light'),
                'language'       => $language,
                'ai_opt_out'     => $request->boolean('ai_opt_out') ? '1' : '0',
                'ai_ocr_opt_out' => $request->boolean('ai_ocr_opt_out') ? '1' : '0',
            ];

            foreach ($data as $key => $value) {
                \App\Models\Setting::updateOrCreate(
                    ['household_id' => $householdId, 'key' => $key],
                    ['value' => $value]
                );
            }

            // Apply locale immediately for this response & store in session
            app()->setLocale($language);
            session(['locale' => $language]);

            return back()->with('success', __('settings.save') . ' — ' . __('messages.success'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan preferensi: ' . $e->getMessage());
        }
    }

    /**
     * Update preferensi notifikasi email per-user (opt-in / opt-out).
     */
    public function updateNotifications(Request $request)
    {
        $request->validate([
            'email_notifications' => 'nullable|boolean',
        ]);

        try {
            $user = $request->user();
            $user->email_notifications = $request->boolean('email_notifications');
            $user->save();

            return back()->with('success', __('settings.save') . ' — ' . __('messages.success'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan preferensi notifikasi: ' . $e->getMessage());
        }
    }

    /**
     * Reset all transaction data for the current household.
     * Permanently deletes every Transaksi record (including soft-deleted)
     * and zeroes out saldo_saat_ini on all SumberTransaksi so the
     * dashboard shows a clean 0 balance after the reset.
     */
    public function resetTransaksiData(Request $request)
    {
        $request->validate([
            'confirm_word' => 'required|string',
        ]);

        if ($request->input('confirm_word') !== __('settings.reset_data_confirm_word')) {
            return back()->with('error', __('messages.error'));
        }

        try {
            $householdId = auth()->user()->household_id;

            DB::transaction(function () use ($householdId) {
                // Hard-delete all transaksi (including soft-deleted rows)
                Transaksi::withTrashed()
                    ->where('household_id', $householdId)
                    ->forceDelete();

                SumberTransaksi::withTrashed()
                    ->where('household_id', $householdId)
                    ->update(['saldo_saat_ini' => 0]);
            });

            return redirect()->route('settings.index', ['tab' => 'privasi'])
                ->with('success', __('settings.reset_data_success'));
        } catch (\Exception $e) {
            return back()->with('error', __('messages.error') . ': ' . $e->getMessage());
        }
    }

    /**
     * Get setting value
     */
    public static function get($key, $default = null)
    {
        if (!auth()->check()) {
            return $default;
        }

        $setting = Setting::where('household_id', auth()->user()->household_id)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value
     */
    public static function set($key, $value)
    {
        if (!auth()->check()) {
            return false;
        }

        return Setting::updateOrCreate(
            [
                'household_id' => auth()->user()->household_id,
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );
    }
}
