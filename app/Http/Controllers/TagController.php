<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\TransaksiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function __construct(private readonly TransaksiService $transaksiService) {}

    public function index()
    {
        $tags         = Tag::withCount('transaksi')->orderBy('nama')->get();
        $summaryByTag = $this->transaksiService->getSummaryByTag();

        return view('tags.index', compact('tags', 'summaryByTag'));
    }

    /**
     * Store a newly created tag
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:50|unique:tags,nama,NULL,id,household_id,' . auth()->user()->household_id,
            'warna' => 'nullable|string|max:7',
        ]);

        try {
            Tag::create([
                'household_id' => auth()->user()->household_id,
                'nama'         => $request->nama,
                'slug'         => Str::slug($request->nama),
                'warna'        => $request->warna ?? '#6c757d',
            ]);

            return back()->with('success', 'Tag berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan tag: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified tag
     */
    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'nama' => 'required|string|max:50|unique:tags,nama,' . $tag->id . ',id,household_id,' . auth()->user()->household_id,
            'warna' => 'nullable|string|max:7',
        ]);

        try {
            $tag->update([
                'nama'  => $request->nama,
                'slug'  => Str::slug($request->nama),
                'warna' => $request->warna ?? $tag->warna,
            ]);

            return back()->with('success', 'Tag berhasil diperbarui');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui tag: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified tag
     */
    public function destroy(Tag $tag)
    {
        try {
            // Detach from all transaksi first
            $tag->transaksi()->detach();
            
            $tag->delete();

            return back()->with('success', 'Tag berhasil dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus tag: ' . $e->getMessage());
        }
    }

    /**
     * Search tags (AJAX)
     */
    public function search(Request $request)
    {
        $query = Tag::query();

        if ($request->filled('q')) {
            $query->where('nama', 'like', '%' . $request->q . '%');
        }

        $tags = $query->orderBy('nama')->limit(10)->get();

        return response()->json($tags);
    }
}
