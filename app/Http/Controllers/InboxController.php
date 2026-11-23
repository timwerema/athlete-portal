<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    public function index(Request $request): Response
    {
        $messages = $request->user()->profileOrCreate()->messages()->get();

        return Inertia::render('inbox/index', [
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'sender_name' => $m->sender_name,
                'sender_school' => $m->sender_school,
                'sender_role' => $m->sender_role,
                'excerpt' => str($m->body)->squish()->limit(110)->toString(),
                'received_at' => $m->created_at->toIso8601String(),
                'read' => $m->read_at !== null,
            ]),
        ]);
    }

    public function show(Request $request, int $message): Response
    {
        $m = $request->user()->profileOrCreate()->messages()->findOrFail($message);

        if ($m->read_at === null) {
            $m->forceFill(['read_at' => now()])->save();
        }

        return Inertia::render('inbox/show', [
            'message' => [
                'id' => $m->id,
                'sender_name' => $m->sender_name,
                'sender_school' => $m->sender_school,
                'sender_role' => $m->sender_role,
                'sender_email' => $m->sender_email,
                'body' => $m->body,
                'received_at' => $m->created_at->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Request $request, int $message): RedirectResponse
    {
        $request->user()->profileOrCreate()->messages()->findOrFail($message)->delete();

        return to_route('inbox.index')->with('status', 'Message deleted.');
    }
}
