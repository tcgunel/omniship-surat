<?php

declare(strict_types=1);

use Omniship\Common\Enum\ShipmentStatus;
use Omniship\Surat\Message\GetTrackingStatusResponse;

use function Omniship\Surat\Tests\createMockRequest;

it('parses successful delivered response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => 'Teslim Edildi|2026-02-22 14:30:00|Ankara Cankaya Sube',
    ]);

    expect($response->isSuccessful())->toBeTrue();

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->carrier)->toBe('Sürat Kargo')
        ->and($info->events)->not->toBeEmpty();
});

it('parses in-transit response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => 'Aktarma Merkezinde|2026-02-21 08:00:00|Istanbul Aktarma',
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::IN_TRANSIT);
});

it('parses out for delivery response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => 'Dagitima Verildi|2026-02-22 09:00:00|Ankara Cankaya Sube',
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::OUT_FOR_DELIVERY);
});

it('parses picked up / accepted response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => 'Kargo Kabul|2026-02-20 10:00:00|Istanbul Kadikoy Sube',
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::PICKED_UP);
});

it('parses returned response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => 'Iade Edildi|2026-02-25 11:00:00|Istanbul Merkez',
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::RETURNED);
});

it('handles unknown status gracefully', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => 'Bilinmeyen Durum|2026-02-20 10:00:00|Sube',
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::UNKNOWN);
});

it('handles empty/null result as error', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => '',
    ]);

    expect($response->isSuccessful())->toBeFalse();
});

it('handles null result as error', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => null,
    ]);

    expect($response->isSuccessful())->toBeFalse();
});

it('parses multi-line tracking events', function () {
    $events = implode("\n", [
        'Kargo Kabul|2026-02-20 10:00:00|Istanbul Kadikoy Sube',
        'Aktarma Merkezinde|2026-02-21 08:00:00|Istanbul Aktarma',
        'Dagitima Verildi|2026-02-22 09:00:00|Ankara Cankaya Sube',
        'Teslim Edildi|2026-02-22 14:30:00|Ankara Cankaya Sube',
    ]);

    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'KargoTakipHareketDetayliV2Result' => $events,
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->events)->toHaveCount(4);

    expect($info->events[0]->status)->toBe(ShipmentStatus::PICKED_UP)
        ->and($info->events[0]->description)->toBe('Kargo Kabul')
        ->and($info->events[0]->location)->toBe('Istanbul Kadikoy Sube');

    expect($info->events[3]->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->events[3]->description)->toBe('Teslim Edildi');
});

it('maps all known status strings', function () {
    expect(GetTrackingStatusResponse::mapStatus('Kargo Kabul'))->toBe(ShipmentStatus::PICKED_UP)
        ->and(GetTrackingStatusResponse::mapStatus('Aktarma Merkezinde'))->toBe(ShipmentStatus::IN_TRANSIT)
        ->and(GetTrackingStatusResponse::mapStatus('Dagitima Verildi'))->toBe(ShipmentStatus::OUT_FOR_DELIVERY)
        ->and(GetTrackingStatusResponse::mapStatus('Teslim Edildi'))->toBe(ShipmentStatus::DELIVERED)
        ->and(GetTrackingStatusResponse::mapStatus('Iade Edildi'))->toBe(ShipmentStatus::RETURNED)
        ->and(GetTrackingStatusResponse::mapStatus('Bilinmeyen'))->toBe(ShipmentStatus::UNKNOWN);
});

it('returns raw data', function () {
    $data = (object) ['KargoTakipHareketDetayliV2Result' => 'Teslim Edildi|2026-02-22 14:30:00|Sube'];
    $response = new GetTrackingStatusResponse(createMockRequest(), $data);

    expect($response->getData())->toBe($data);
});
