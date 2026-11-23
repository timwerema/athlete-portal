<?php

namespace App\Http\Controllers;

use App\Services\ProfileCompleteness;
use App\Services\ViewCounter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $profile = $request->user()->profileOrCreate();
        $link = $profile->currentLink();

        return Inertia::render('dashboard', [
            'displayName' => $profile->display_name,
            'completeness' => ProfileCompleteness::summary($profile),
            'link' => $link ? ['created_at' => $link->created_at->toDateString()] : null,
            'views' => ViewCounter::summary($link),
            'unreadMessages' => $profile->messages()->whereNull('read_at')->count(),
            'totalMessages' => $profile->messages()->count(),
        ]);
    }
}
