<?php

namespace Tests\Unit\Services;

use App\Models\Household;
use App\Models\Kategori;
use App\Models\Plan;
use App\Models\SumberTransaksi;
use App\Models\Tag;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MomentumService;
use App\Services\TransaksiService;
use App\Services\XpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiServiceTagTest extends TestCase
{
    use RefreshDatabase;

    private TransaksiService $service;
    private User $user;
    private int $householdId;
    private int $kategoriId;
    private int $sumberId;

    protected function setUp(): void
    {
        parent::setUp();

        $xp       = $this->mock(XpService::class);
        $momentum = $this->mock(MomentumService::class);
        $xp->shouldReceive('award')->andReturn(null)->byDefault();
        $momentum->shouldReceive('recordActivity')->andReturn(null)->byDefault();
        $this->service = new TransaksiService($xp, $momentum);

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
            'tanggal'             => now()->format('Y-m-d'),
        ], $overrides));
    }

    public function test_getSummaryByTag_returns_only_tags_with_transaksi(): void
    {
        $tagA = Tag::create(['household_id' => $this->householdId, 'nama' => 'Suami', 'slug' => 'suami', 'warna' => '#3b82f6']);
        Tag::create(['household_id' => $this->householdId, 'nama' => 'Istri', 'slug' => 'istri', 'warna' => '#ec4899']);

        $t = $this->makeTransaksi(['jenis' => 'pengeluaran', 'jumlah' => 100000]);
        $t->tags()->attach($tagA->id);

        $result = $this->service->getSummaryByTag();

        $this->assertCount(1, $result);
        $this->assertEquals($tagA->id, $result[0]['tag']->id);
        $this->assertEquals(100000.0, $result[0]['total_pengeluaran']);
        $this->assertEquals(0.0, $result[0]['total_pemasukan']);
        $this->assertEquals(1, $result[0]['jumlah_transaksi']);
    }

    public function test_getSummaryByTag_sums_pemasukan_and_pengeluaran_separately(): void
    {
        $tag = Tag::create(['household_id' => $this->householdId, 'nama' => 'Proyek', 'slug' => 'proyek', 'warna' => '#10b981']);

        $t1 = $this->makeTransaksi(['jenis' => 'pengeluaran', 'jumlah' => 200000]);
        $t2 = $this->makeTransaksi(['jenis' => 'pemasukan',   'jumlah' => 500000]);
        $t1->tags()->attach($tag->id);
        $t2->tags()->attach($tag->id);

        $result = $this->service->getSummaryByTag();

        $this->assertCount(1, $result);
        $this->assertEquals(500000.0, $result[0]['total_pemasukan']);
        $this->assertEquals(200000.0, $result[0]['total_pengeluaran']);
        $this->assertEquals(2, $result[0]['jumlah_transaksi']);
    }

    public function test_getSummaryByTag_sorted_by_pengeluaran_desc(): void
    {
        $tagA = Tag::create(['household_id' => $this->householdId, 'nama' => 'Anak-A', 'slug' => 'anak-a', 'warna' => '#f59e0b']);
        $tagB = Tag::create(['household_id' => $this->householdId, 'nama' => 'Anak-B', 'slug' => 'anak-b', 'warna' => '#8b5cf6']);

        $t1 = $this->makeTransaksi(['jenis' => 'pengeluaran', 'jumlah' => 100000]);
        $t2 = $this->makeTransaksi(['jenis' => 'pengeluaran', 'jumlah' => 500000]);
        $t1->tags()->attach($tagA->id);
        $t2->tags()->attach($tagB->id);

        $result = $this->service->getSummaryByTag();

        $this->assertEquals($tagB->id, $result[0]['tag']->id);
        $this->assertEquals($tagA->id, $result[1]['tag']->id);
    }

    public function test_getSummaryByTag_returns_empty_when_no_tags(): void
    {
        $result = $this->service->getSummaryByTag();
        $this->assertEmpty($result);
    }
}
