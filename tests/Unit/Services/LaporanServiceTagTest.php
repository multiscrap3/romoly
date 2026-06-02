<?php

namespace Tests\Unit\Services;

use App\Models\Household;
use App\Models\Kategori;
use App\Models\Plan;
use App\Models\SumberTransaksi;
use App\Models\Tag;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\LaporanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanServiceTagTest extends TestCase
{
    use RefreshDatabase;

    private LaporanService $service;
    private User $user;
    private int $householdId;
    private int $kategoriId;
    private int $sumberId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LaporanService();

        $plan      = Plan::create(['nama' => 'Free', 'slug' => 'free']);
        $household = Household::create(['nama' => 'Test Family', 'slug' => 'test-family', 'plan_id' => $plan->id]);
        $this->user        = User::factory()->create(['household_id' => $household->id]);
        $this->householdId = $household->id;

        $kategori = Kategori::create([
            'household_id' => $this->householdId,
            'nama'         => 'Lain-lain',
            'jenis'        => 'pengeluaran',
        ]);
        $this->kategoriId = $kategori->id;

        $sumber = SumberTransaksi::create([
            'household_id' => $this->householdId,
            'nama'         => 'Kas Test',
            'jenis'        => 'cash',
        ]);
        $this->sumberId = $sumber->id;

        $this->actingAs($this->user);
    }

    private function makeTransaksi(array $overrides = []): Transaksi
    {
        return Transaksi::create(array_merge([
            'household_id'        => $this->householdId,
            'user_id'             => $this->user->id,
            'kategori_id'         => $this->kategoriId,
            'sumber_transaksi_id' => $this->sumberId,
            'jenis'               => 'pengeluaran',
            'jumlah'              => 100000,
            'tanggal'             => Carbon::now()->format('Y-m-d'),
        ], $overrides));
    }

    public function test_getByTag_returns_correct_summary(): void
    {
        $tag = Tag::create(['household_id' => $this->householdId, 'nama' => 'Suami', 'slug' => 'suami', 'warna' => '#3b82f6']);

        $dari   = Carbon::now()->startOfMonth()->format('Y-m-d');
        $sampai = Carbon::now()->endOfMonth()->format('Y-m-d');

        $t1 = $this->makeTransaksi(['jenis' => 'pengeluaran', 'jumlah' => 300000]);
        $t2 = $this->makeTransaksi(['jenis' => 'pemasukan',   'jumlah' => 1000000]);
        $t1->tags()->attach($tag->id);
        $t2->tags()->attach($tag->id);

        $result = $this->service->getByTag($tag, $dari, $sampai);

        $this->assertEquals($tag->id, $result['tag']->id);
        $this->assertEquals(300000, $result['total_pengeluaran']);
        $this->assertEquals(1000000, $result['total_pemasukan']);
        $this->assertEquals(700000, $result['cashflow']);
        $this->assertEquals(2, $result['summary']['total_transaksi']);
    }

    public function test_getByTag_excludes_transaksi_outside_date_range(): void
    {
        $tag = Tag::create(['household_id' => $this->householdId, 'nama' => 'Proyek', 'slug' => 'proyek', 'warna' => '#10b981']);

        $dari   = Carbon::now()->startOfMonth()->format('Y-m-d');
        $sampai = Carbon::now()->endOfMonth()->format('Y-m-d');

        $dalam = $this->makeTransaksi(['jenis' => 'pengeluaran', 'jumlah' => 100000, 'tanggal' => Carbon::now()->format('Y-m-d')]);
        $luar  = $this->makeTransaksi(['jenis' => 'pengeluaran', 'jumlah' => 999000, 'tanggal' => Carbon::now()->subMonths(3)->format('Y-m-d')]);
        $dalam->tags()->attach($tag->id);
        $luar->tags()->attach($tag->id);

        $result = $this->service->getByTag($tag, $dari, $sampai);

        $this->assertEquals(100000, $result['total_pengeluaran']);
        $this->assertEquals(1, $result['summary']['total_transaksi']);
    }

    public function test_getByTag_returns_empty_result_for_tag_with_no_transaksi(): void
    {
        $tag = Tag::create(['household_id' => $this->householdId, 'nama' => 'Kosong', 'slug' => 'kosong', 'warna' => '#6c757d']);

        $dari   = Carbon::now()->startOfMonth()->format('Y-m-d');
        $sampai = Carbon::now()->endOfMonth()->format('Y-m-d');

        $result = $this->service->getByTag($tag, $dari, $sampai);

        $this->assertEquals(0, $result['total_pengeluaran']);
        $this->assertEquals(0, $result['total_pemasukan']);
        $this->assertEquals(0, $result['summary']['total_transaksi']);
        $this->assertEmpty($result['transaksi']);
    }
}
