<?php

namespace App\Http\Controllers;

use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    /** Halaman pricing. */
    public function index()
    {
        $user      = auth()->user();
        $household = $user->household;
        $plans     = Plan::where('is_active', true)->orderBy('harga')->get();
        $current   = $household?->plan;

        $daysLeft   = null;
        $expirySoon = false;
        if ($household?->subscription_end) {
            $daysLeft   = (int) today()->diffInDays($household->subscription_end, false);
            $expirySoon = $daysLeft <= 7 && $daysLeft >= 0;
        }

        return view('billing.index', compact('plans', 'current', 'household', 'daysLeft', 'expirySoon'));
    }

    /** Konfirmasi sebelum bayar. */
    public function checkout(Plan $plan, Request $request)
    {
        $period = $request->query('period', 'monthly');
        abort_unless(in_array($period, ['monthly', 'yearly'], true), 404);

        $totalHarga = (int) $plan->harga * ($period === 'yearly' ? 10 : 1);

        return view('billing.checkout', compact('plan', 'period', 'totalHarga'));
    }

    /** Buat transaksi & redirect ke halaman bayar gateway. */
    public function pay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan'   => 'required|string|exists:plans,slug',
            'period' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::where('slug', $data['plan'])->firstOrFail();

        if ((float) $plan->harga <= 0) {
            return back()->with('error', 'Paket ini gratis, tidak perlu pembayaran.');
        }

        $payment = $this->paymentService->checkout(auth()->user(), $plan, $data['period']);

        if (empty($payment->checkout_url)) {
            return back()->with('error', 'Gagal membuat transaksi pembayaran. Coba lagi.');
        }

        return redirect()->away($payment->checkout_url);
    }

    /** Halaman kembali setelah bayar (hanya membaca; polling via JS ke status()). */
    public function return(Request $request)
    {
        $payment = PaymentHistory::where('merchant_ref', $request->query('ref'))
            ->where('household_id', auth()->user()->household_id)
            ->with('plan')
            ->firstOrFail();

        return view('billing.return', compact('payment'));
    }

    /** Cek & sinkronkan status (polling dari halaman return). Aktivasi plan jika sudah paid. */
    public function status(string $ref): JsonResponse
    {
        $payment = PaymentHistory::where('merchant_ref', $ref)
            ->where('household_id', auth()->user()->household_id)
            ->firstOrFail();

        $status = $this->paymentService->syncStatus($payment);

        return response()->json(['status' => $status]);
    }

    /** Riwayat pembayaran household. */
    public function history()
    {
        $payments = PaymentHistory::where('household_id', auth()->user()->household_id)
            ->with('plan')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('billing.history', compact('payments'));
    }

    /** Webhook publik dari gateway. Signature divalidasi di verifyCallback(). */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $gateway = app(\App\Contracts\PaymentGateway::class);
            $result  = $gateway->verifyCallback($request);

            $payment = PaymentHistory::where('merchant_ref', $result->merchantRef)->first();
            if (! $payment) {
                return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
            }

            if ($result->paid) {
                $this->paymentService->applyPayment($payment, $result->rawAmount, $result->payload);
            }

            return response()->json(['message' => 'OK']);
        } catch (\Throwable $e) {
            Log::error('Webhook billing gagal: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid'], 400);
        }
    }
}
