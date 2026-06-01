<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Guided tour / user guide — menyimpan progress tour per-user
 * di kolom JSON users.tour_progress. Dipanggil via AJAX dari tour-core.js.
 */
class TourController extends Controller
{
    /**
     * Tandai tour sebuah halaman sudah dilihat/dilewati.
     */
    public function seen(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100'],
        ]);

        $request->user()->markTourSeen($validated['key']);

        return response()->json(['success' => true]);
    }

    /**
     * Tandai tour global "welcome" sudah selesai.
     */
    public function welcome(Request $request): JsonResponse
    {
        $request->user()->markWelcomeTourCompleted();

        return response()->json(['success' => true]);
    }

    /**
     * Reset seluruh progress tour (fitur "Putar ulang panduan").
     */
    public function reset(Request $request): JsonResponse
    {
        $request->user()->resetTour();

        return response()->json(['success' => true]);
    }
}
