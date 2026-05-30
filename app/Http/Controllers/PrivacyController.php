<?php

namespace App\Http\Controllers;

use App\Models\ConsentLog;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrivacyController extends Controller
{
    const POLICY_VERSION = '1.0';

    public function policy()
    {
        return view('privacy.policy', ['version' => self::POLICY_VERSION]);
    }

    public function terms()
    {
        return view('privacy.terms');
    }

    public function dataExport()
    {
        $user = auth()->user();
        $consentLogs = ConsentLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('privacy.data-export', compact('user', 'consentLogs'));
    }

    public function downloadData()
    {
        $user = auth()->user()->load([
            'household',
        ]);

        $data = [
            'exported_at' => now()->toIso8601String(),
            'policy_version' => self::POLICY_VERSION,
            'profil' => [
                'nama'             => $user->name,
                'email'            => $user->email,
                'telepon'          => $user->phone,
                'tanggal_lahir'    => $user->tanggal_lahir,
                'peran'            => $user->getRoleNames()->implode(', '),
                'bergabung'        => $user->created_at?->toIso8601String(),
                'login_terakhir'   => $user->last_login_at?->toIso8601String(),
                'consent_diberikan' => $user->consent_given_at?->toIso8601String(),
                'versi_kebijakan'  => $user->privacy_policy_version,
            ],
            'household' => $user->household ? [
                'nama'   => $user->household->nama,
                'status' => $user->household->status,
            ] : null,
            'transaksi' => DB::table('transaksi')
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->select('jenis', 'jumlah', 'keterangan', 'tanggal', 'created_at')
                ->orderByDesc('tanggal')
                ->get(),
            'pengaturan' => DB::table('settings')
                ->where('household_id', $user->household_id)
                ->select('key', 'value', 'type')
                ->get(),
            'consent_logs' => ConsentLog::where('user_id', $user->id)
                ->select('type', 'policy_version', 'ip_address', 'created_at')
                ->orderByDesc('created_at')
                ->get(),
        ];

        // G3: log setiap kali user export data pribadi
        SecurityLog::record('data_export', 'low', ['user_id' => $user->id]);

        $filename = 'romoly-data-pribadi-' . now()->format('Ymd-His') . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
