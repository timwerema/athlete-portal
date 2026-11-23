<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\ShareLink;
use App\Services\ProfilePresenter;
use App\Services\ViewCounter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SharingController extends Controller
{
    public function index(Request $request): Response
    {
        $profile = $request->user()->profileOrCreate();
        $link = $profile->currentLink();

        return Inertia::render('sharing/index', [
            'link' => $link ? ['url' => $link->url(), 'created_at' => $link->created_at->toDateString()] : null,
            'views' => ViewCounter::summary($link),
            'hideable' => Profile::HIDEABLE,
            'hiddenSections' => array_values($profile->hidden_sections ?? []),
        ]);
    }

    /** Create the link, or replace it: the old address stops working straight away. */
    public function store(Request $request): RedirectResponse
    {
        $profile = $request->user()->profileOrCreate();

        DB::transaction(function () use ($profile) {
            $profile->shareLinks()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            ShareLink::issueFor($profile);
        });

        return back()->with('status', 'Your new link is ready. Any older link has stopped working.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->profileOrCreate()->shareLinks()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        return back()->with('status', 'Link switched off. Nobody can open your profile now.');
    }

    public function visibility(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hidden_sections' => ['present', 'array'],
            'hidden_sections.*' => ['string', 'distinct', Rule::in(array_keys(Profile::HIDEABLE))],
        ]);

        $request->user()->profileOrCreate()->update(['hidden_sections' => array_values($data['hidden_sections'])]);

        return back()->with('status', 'Saved.');
    }

    /** The athlete's own look at exactly what a coach will see. */
    public function preview(Request $request): Response
    {
        $profile = $request->user()->profileOrCreate();

        return Inertia::render('share/show', [
            'profile' => ProfilePresenter::forCoach($profile, route('profile.photo')),
            'mode' => 'preview',
            'contact' => null,
        ]);
    }
}
