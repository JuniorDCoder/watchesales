<?php

namespace App\Http\Controllers;

use App\Enums\InquiryChannel;
use App\Models\Inquiry;
use App\Models\Watch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class InquiryController extends Controller
{
    /**
     * Record that a visitor reached out, so the dashboard can report on demand.
     */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::enum(InquiryChannel::class)],
            'watch' => ['nullable', 'string', Rule::exists('watches', 'slug')->where('is_published', true)],
        ]);

        Inquiry::query()->create([
            'channel' => $validated['channel'],
            'watch_id' => isset($validated['watch'])
                ? Watch::query()->where('slug', $validated['watch'])->value('id')
                : null,
        ]);

        return response()->noContent();
    }
}
