<?php

use App\Enums\InquiryChannel;
use App\Models\Inquiry;
use App\Models\Watch;

test('an inquiry about a watch is recorded', function () {
    $watch = Watch::factory()->create();

    $response = $this->postJson(route('inquiries.store'), [
        'channel' => 'whatsapp',
        'watch' => $watch->slug,
    ]);

    $response->assertNoContent();
    $inquiry = Inquiry::query()->sole();
    expect($inquiry->watch_id)->toBe($watch->id)
        ->and($inquiry->channel)->toBe(InquiryChannel::Whatsapp);
});

test('a general inquiry is recorded without a watch', function () {
    $this->postJson(route('inquiries.store'), ['channel' => 'email'])->assertNoContent();

    expect(Inquiry::query()->sole()->watch_id)->toBeNull();
});

test('inquiries must use a known channel', function () {
    $response = $this->postJson(route('inquiries.store'), ['channel' => 'carrier pigeon']);

    $response->assertUnprocessable()->assertJsonValidationErrors('channel');
    expect(Inquiry::query()->count())->toBe(0);
});

test('inquiries cannot reference unpublished watches', function () {
    $watch = Watch::factory()->unpublished()->create();

    $response = $this->postJson(route('inquiries.store'), ['channel' => 'email', 'watch' => $watch->slug]);

    $response->assertJsonValidationErrors('watch');
});
