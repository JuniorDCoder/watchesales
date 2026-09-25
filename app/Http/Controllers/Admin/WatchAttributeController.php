<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Watch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WatchAttributeController extends Controller
{
    /**
     * Quickly change a watch's status, visibility or featured flag from the list.
     */
    public function update(Request $request, Watch $watch): RedirectResponse
    {
        $watch->update($request->validate([
            'status' => ['sometimes', Rule::enum(WatchStatus::class)],
            'is_featured' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
        ]));

        return back();
    }
}
