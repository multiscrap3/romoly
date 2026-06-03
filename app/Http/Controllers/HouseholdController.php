<?php

namespace App\Http\Controllers;

use App\Mail\HouseholdInvitationMail;
use App\Models\Household;
use App\Models\HouseholdInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HouseholdController extends Controller
{
    /**
     * Display household info
     */
    public function index()
    {
        $household = auth()->user()->household;
        $members = User::where('household_id', $household->id)->get();
        $invitations = HouseholdInvitation::where('household_id', $household->id)
            ->where('status', 'pending')
            ->get();

        return view('household.index', compact('household', 'members', 'invitations'));
    }

    /**
     * Dedicated members management page
     */
    public function members()
    {
        $household = auth()->user()->household;
        $members = User::where('household_id', $household->id)->get();
        $invitations = HouseholdInvitation::where('household_id', $household->id)
            ->where('status', 'pending')
            ->get();

        return view('household.members', compact('household', 'members', 'invitations'));
    }

    /**
     * Update household info
     */
    public function update(Request $request, Household $household)
    {
        if (!auth()->user()->hasPermissionTo('manage household settings')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah informasi household');
        }

        $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        try {
            $household->update(['nama' => $request->nama]);

            return back()->with('success', 'Informasi household berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui household: ' . $e->getMessage());
        }
    }

    /**
     * Masukkan email undangan ke antrian. Mengembalikan true jika berhasil di-queue.
     * Gagal queue tidak boleh menggagalkan alur undangan (link manual tetap tersedia).
     */
    private function queueInvitationEmail(HouseholdInvitation $invitation): bool
    {
        try {
            Mail::to($invitation->email)->queue(new HouseholdInvitationMail($invitation));
            return true;
        } catch (\Throwable $e) {
            Log::error('Gagal queue email undangan household: ' . $e->getMessage(), [
                'invitation_id' => $invitation->id,
                'email'         => $invitation->email,
            ]);
            return false;
        }
    }

    /**
     * Invite member to household
     */
    public function invite(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => 'nullable|in:admin,analyst,member,viewer',
        ]);

        $household = auth()->user()->household;

        if (!auth()->user()->hasPermissionTo('manage members')) {
            abort(403, 'Anda tidak memiliki izin untuk mengundang anggota');
        }

        try {
            // Cek apakah sudah ada undangan pending ke email ini
            $existingInvitation = HouseholdInvitation::where('household_id', $household->id)
                ->where('email', $request->email)
                ->where('status', 'pending')
                ->first();

            if ($existingInvitation) {
                // Kirim ulang email undangan yang sudah ada
                $this->queueInvitationEmail($existingInvitation);

                $link = route('register', ['token' => $existingInvitation->token]);
                return back()->with('invite_link', $link)
                             ->with('info', 'Undangan untuk ' . $request->email . ' sudah ada — email dikirim ulang. Link cadangan tersedia di bawah.');
            }

            // Cek jika user sudah ada dan sudah di household lain
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser && $existingUser->household_id) {
                return back()->with('error', 'User sudah tergabung dalam household lain');
            }

            // Buat undangan (berlaku untuk email terdaftar maupun belum)
            $invitation = HouseholdInvitation::create([
                'household_id' => $household->id,
                'email'        => $request->email,
                'role'         => $request->role ?? 'member',
                'token'        => Str::random(32),
                'status'       => 'pending',
                'invited_by'   => auth()->id(),
                'expires_at'   => now()->addDays(7),
            ]);

            // Kirim notifikasi in-app jika user sudah punya akun
            if ($existingUser) {
                \App\Models\Notifikasi::create([
                    'household_id' => $household->id,
                    'user_id'      => $existingUser->id,
                    'judul'        => 'Undangan Household',
                    'pesan'        => "Anda diundang untuk bergabung dengan household '{$household->nama}'",
                    'jenis'        => 'sistem',
                    'is_read'      => false,
                ]);
            }

            // Kirim email undangan (masuk antrian, diproses cron /cron/process-mail)
            $emailSent = $this->queueInvitationEmail($invitation);

            $link = route('register', ['token' => $invitation->token]);
            $msg  = $emailSent
                ? 'Undangan telah dikirim ke ' . $request->email . '. Link cadangan tersedia di bawah untuk dibagikan manual.'
                : 'Undangan berhasil dibuat. Salin link di bawah dan bagikan ke ' . $request->email;

            return back()->with('invite_link', $link)->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim undangan: ' . $e->getMessage());
        }
    }

    /**
     * Join household via token (form submission from household.index)
     */
    public function join(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $invitation = HouseholdInvitation::where('token', $request->token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return back()->with('error', 'Token undangan tidak valid atau sudah digunakan');
        }

        if ($invitation->expires_at < now()) {
            $invitation->update(['status' => 'expired']);
            return back()->with('error', 'Undangan sudah kadaluarsa');
        }

        try {
            auth()->user()->update(['household_id' => $invitation->household_id]);
            $invitation->update(['status' => 'accepted']);

            return redirect()->route('dashboard')->with('success', 'Berhasil bergabung dengan household');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal bergabung: ' . $e->getMessage());
        }
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(Request $request, $token)
    {
        $invitation = HouseholdInvitation::where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return redirect()->route('dashboard')->with('error', 'Undangan tidak valid');
        }

        if ($invitation->expires_at < now()) {
            $invitation->update(['status' => 'expired']);
            return redirect()->route('dashboard')->with('error', 'Undangan sudah kadaluarsa');
        }

        try {
            // Update user household
            auth()->user()->update(['household_id' => $invitation->household_id]);

            // Update invitation status
            $invitation->update(['status' => 'accepted']);

            return redirect()->route('dashboard')->with('success', 'Berhasil bergabung dengan household');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Gagal bergabung: ' . $e->getMessage());
        }
    }

    /**
     * Cancel (revoke) a pending invitation
     */
    public function cancelInvitation(HouseholdInvitation $invitation)
    {
        $household = auth()->user()->household;

        if (!auth()->user()->hasPermissionTo('manage members')) {
            abort(403);
        }

        if ($invitation->household_id !== $household->id) {
            abort(403);
        }

        $invitation->update(['status' => 'cancelled']);

        return back()->with('success', 'Undangan berhasil dibatalkan');
    }

    /**
     * Reject invitation
     */
    public function rejectInvitation($token)
    {
        $invitation = HouseholdInvitation::where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return back()->with('error', 'Undangan tidak valid');
        }

        $invitation->update(['status' => 'rejected']);

        return back()->with('success', 'Undangan ditolak');
    }

    /**
     * Update member role
     */
    public function updateRole(Request $request, User $user)
    {
        $household = auth()->user()->household;

        if (!auth()->user()->hasPermissionTo('manage roles')) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah role anggota');
        }

        if ($user->hasRole('owner')) {
            return back()->with('error', 'Tidak dapat mengubah role owner');
        }

        if ($user->household_id !== $household->id) {
            return back()->with('error', 'User bukan anggota household ini');
        }

        $request->validate([
            'role' => 'required|in:admin,analyst,member,viewer',
        ]);

        $user->syncRoles([$request->role]);

        return back()->with('success', 'Role berhasil diperbarui');
    }

    /**
     * Remove member from household
     */
    public function removeMember(User $user)
    {
        $household = auth()->user()->household;

        if (!auth()->user()->hasPermissionTo('manage members')) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus anggota');
        }

        // Can't remove owner
        if ($user->hasRole('owner')) {
            return back()->with('error', 'Tidak dapat menghapus owner household');
        }

        // Can't remove user from different household
        if ($user->household_id !== $household->id) {
            return back()->with('error', 'User bukan anggota household ini');
        }

        try {
            // Create new household for removed user
            $newHousehold = Household::create([
                'nama' => $user->name . "'s Household",
                'owner_id' => $user->id,
            ]);

            // Move user to new household
            $user->update(['household_id' => $newHousehold->id]);

            return back()->with('success', 'Member berhasil dihapus dari household');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus member: ' . $e->getMessage());
        }
    }

    /**
     * Leave household
     */
    public function leave()
    {
        $user = auth()->user();
        $household = $user->household;

        // Owner can't leave, must transfer ownership first
        if ($household->owner_id === $user->id) {
            return back()->with('error', 'Owner tidak dapat keluar. Transfer ownership terlebih dahulu');
        }

        try {
            // Create new household for user
            $newHousehold = Household::create([
                'nama' => $user->name . "'s Household",
                'owner_id' => $user->id,
            ]);

            // Move user to new household
            $user->update(['household_id' => $newHousehold->id]);

            return redirect()->route('dashboard')->with('success', 'Berhasil keluar dari household');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal keluar dari household: ' . $e->getMessage());
        }
    }
}
