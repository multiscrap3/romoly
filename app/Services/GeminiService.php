<?php

namespace App\Services;

use App\Exceptions\GeminiException;
use App\Exceptions\GeminiLimitException;
use App\Models\AiUsageLog;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;

    private string $model;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) (env('GEMINI_API_KEY') ?: $this->getSetting('gemini_api_key', ''));
        $this->model = (string) (env('GEMINI_MODEL') ?: $this->getSetting('gemini_model', 'gemini-2.0-flash'));
        $this->baseUrl = (string) (env('GEMINI_BASE_URL') ?: $this->getSetting('gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'));
    }

    public function ocrAndExtract(string $base64Image, string $mimeType): array
    {
        $cacheKey = 'gemini_ocr_' . md5($base64Image);

        return Cache::remember($cacheKey, now()->addDay(), function () use ($base64Image, $mimeType): array {
            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Kamu adalah asisten ekstraksi data struk belanja. Ekstrak data dari struk/screenshot ini dalam format JSON: {tanggal, nama_toko, tipe_toko, tipe_transaksi(income/outcome), items:[{nama_item,qty,harga_satuan,subtotal}], total, metode_bayar, catatan}. Jika tidak ditemukan, isi dengan null. Tanggal format: YYYY-MM-DD. Semua angka tanpa titik/koma pemisah. PENTING untuk tipe_toko: jika item yang dibeli adalah rokok atau produk tembakau (termasuk merk seperti Gudang Garam, Sampoerna, Dji Sam Soe, Marlboro, Camel, Dunhill, Djarum, A Mild, U Mild, Surya, Star Mild, Class Mild, La Mild, Wismilak, dll), isi tipe_toko dengan "rokok". Jika nama item mengandung kata rokok, kretek, sigaret, atau tembakau, tipe_toko juga harus "rokok". Balas hanya dengan JSON tanpa markdown.',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:' . $mimeType . ';base64,' . $base64Image,
                                ],
                            ],
                        ],
                    ],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ];

            $response = $this->callAPI($payload, 'ocr');
            $text = $this->extractText($response);

            return $this->parseJsonResponse($text);
        });
    }

    public function suggestDetail(string $nama_toko, float $total, array $history): array
    {
        $cacheKey = 'gemini_suggest_' . md5($nama_toko . $total . json_encode($history));

        return Cache::remember($cacheKey, now()->addDay(), function () use ($nama_toko, $total, $history): array {
            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => sprintf(
                            'Pengguna beli di %s total Rp %s. Berdasarkan history %d transaksi sebelumnya: %s. Suggest breakdown detail dalam JSON array items dengan field nama_item, qty, harga_satuan, subtotal. Balas hanya dengan JSON tanpa markdown.',
                            $nama_toko,
                            number_format($total, 0, ',', '.'),
                            count($history),
                            json_encode($history, JSON_UNESCAPED_UNICODE)
                        ),
                    ],
                ],
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
            ];

            try {
                $response = $this->callAPI($payload, 'suggest_detail');
                $parsed = $this->parseJsonResponse($this->extractText($response));

                return array_is_list($parsed) ? $parsed : ($parsed['items'] ?? []);
            } catch (GeminiException $exception) {
                Log::warning('Gemini suggest detail gagal', ['message' => $exception->getMessage()]);

                return [];
            }
        });
    }

    public function generateInsight(array $data): string
    {
        $cacheKey = 'gemini_insight_' . md5(json_encode($data));

        return Cache::remember($cacheKey, now()->addDay(), function () use ($data): string {
            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => sprintf(
                            'Analisis data keuangan keluarga bulan %s %s. Data: %s. Berikan insight dalam Bahasa Indonesia yang ramah, 3-5 poin penting, dan 2-3 saran actionable. Maksimal 300 kata.',
                            $data['bulan'] ?? '-',
                            $data['tahun'] ?? '-',
                            json_encode($data, JSON_UNESCAPED_UNICODE)
                        ),
                    ],
                ],
                'temperature' => 0.4,
            ];

            $response = $this->callAPI($payload, 'generate_insight');

            return $this->extractText($response);
        });
    }

    public function detectAnomaly(array $transaksi, array $rata_rata): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => sprintf(
                        'Transaksi baru: %s. Rata-rata historis: %s. Apakah ini anomali? Response JSON: {is_anomaly, alasan, severity(low/mid/high)}. Balas hanya dengan JSON tanpa markdown.',
                        json_encode($transaksi, JSON_UNESCAPED_UNICODE),
                        json_encode($rata_rata, JSON_UNESCAPED_UNICODE)
                    ),
                ],
            ],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
        ];

        $response = $this->callAPI($payload, 'detect_anomaly');
        $parsed = $this->parseJsonResponse($this->extractText($response));

        return [
            'is_anomaly' => (bool) ($parsed['is_anomaly'] ?? false),
            'alasan' => (string) ($parsed['alasan'] ?? ''),
            'severity' => in_array(($parsed['severity'] ?? 'low'), ['low', 'mid', 'high'], true)
                ? $parsed['severity']
                : 'low',
        ];
    }

    private function callAPI(array $payload, string $action = 'unknown'): array
    {
        if ($this->apiKey === '') {
            throw new GeminiException('GEMINI_API_KEY (OpenRouter) belum dikonfigurasi.');
        }

        $this->checkDailyLimit();

        $response = Http::timeout(30)
            ->retry(2, 500)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => config('app.url', 'https://romoly.com'),
                'X-Title' => config('app.name', 'Romoly'),
            ])
            ->post($this->baseUrl, $payload);

        $json             = is_array($response->json()) ? $response->json() : [];
        $promptTokens     = data_get($json, 'usage.prompt_tokens');
        $completionTokens = data_get($json, 'usage.completion_tokens');
        $totalTokens      = data_get($json, 'usage.total_tokens');

        $this->logUsage(
            action: $action,
            statusCode: $response->status(),
            success: $response->successful(),
            promptTokens: is_numeric($promptTokens) ? (int) $promptTokens : null,
            completionTokens: is_numeric($completionTokens) ? (int) $completionTokens : null,
            totalTokens: is_numeric($totalTokens) ? (int) $totalTokens : null,
        );

        if ($response->failed()) {
            throw new GeminiException('OpenRouter API error: ' . $response->body(), $response->status());
        }

        $this->incrementDailyUsage();

        if (empty($json)) {
            throw new GeminiException('Response OpenRouter tidak valid.');
        }

        return $json;
    }

    private function checkDailyLimit(): void
    {
        $today = Carbon::today()->toDateString();
        $resetDate = (string) $this->getSetting('gemini_reset_date', '');
        $limit = (int) $this->getSetting('ocr_daily_limit', 500);

        if ($resetDate !== $today) {
            $this->setSetting('gemini_reset_date', $today, 'string');
            $this->setSetting('gemini_ocr_used_today', 0, 'integer');

            return;
        }

        $used = (int) $this->getSetting('gemini_ocr_used_today', 0);

        if ($limit > 0 && $used >= $limit) {
            throw new GeminiLimitException('Limit harian OCR sudah tercapai.');
        }
    }

    private function parseJsonResponse(string $text): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            throw new GeminiException('Response bukan JSON valid: ' . mb_substr($clean, 0, 500));
        }

        return $decoded;
    }

    private function extractText(array $response): string
    {
        $text = data_get($response, 'choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            throw new GeminiException('OpenRouter tidak mengembalikan teks yang valid.');
        }

        return trim($text);
    }

    private function incrementDailyUsage(): void
    {
        $used = (int) $this->getSetting('gemini_ocr_used_today', 0);
        $this->setSetting('gemini_ocr_used_today', $used + 1, 'integer');
    }

    private function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()->where('key', $key)->first();

        return $setting?->value ?? $default;
    }

    private function setSetting(string $key, mixed $value, string $type = 'string'): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key, 'household_id' => null],
            ['value' => $value, 'type' => $type]
        );
    }

    private function logUsage(
        string $action,
        int $statusCode,
        bool $success,
        ?int $promptTokens,
        ?int $completionTokens,
        ?int $totalTokens,
    ): void {
        try {
            AiUsageLog::create([
                'user_id'          => Auth::id(),
                'household_id'     => session('active_household_id'),
                'action'           => $action,
                'model'            => $this->model,
                'status_code'      => $statusCode,
                'success'          => $success,
                'prompt_tokens'    => $promptTokens,
                'completion_tokens'=> $completionTokens,
                'total_tokens'     => $totalTokens,
                'ip_address'       => request()?->ip(),
                'user_agent'       => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat AI usage log', ['message' => $e->getMessage()]);
        }
    }
}
