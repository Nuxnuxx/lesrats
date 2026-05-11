<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvitationCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationCodeController extends Controller
{
    public function index(Request $request): View
    {
        $codes = InvitationCode::with(['creator:id,email', 'user:id,email'])
            ->orderByRaw('used_at IS NULL DESC')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.invitations.index', [
            'codes' => $codes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $request->user();

        $code = Str::upper(Str::random(16));

        InvitationCode::create([
            'code' => $code,
            'created_by' => $admin->id,
        ]);

        Log::info('Invitation code created via admin UI', [
            'admin_id' => $admin->id,
            'code_preview' => substr($code, 0, 4).'…',
        ]);

        return redirect()
            ->route('admin.invitations.index')
            ->with('new_invitation_code', $code);
    }

    public function destroy(Request $request, InvitationCode $invitation): RedirectResponse
    {
        if ($invitation->used_at !== null) {
            return redirect()
                ->route('admin.invitations.index')
                ->withErrors(['delete' => 'Impossible de supprimer un code déjà utilisé.']);
        }

        $invitation->delete();

        return redirect()
            ->route('admin.invitations.index')
            ->with('status', 'invitation-deleted');
    }
}
