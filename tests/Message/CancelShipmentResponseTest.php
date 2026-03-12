<?php

declare(strict_types=1);

use Omniship\Surat\Message\CancelShipmentResponse;

use function Omniship\Surat\Tests\createMockRequest;

it('parses successful cancel response', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'GonderiGeriCekResult' => 'Islem basarili',
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue()
        ->and($response->getMessage())->toBe('Islem basarili');
});

it('parses flat successful cancel response', function () {
    $response = new CancelShipmentResponse(createMockRequest(), 'Islem basarili');

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue();
});

it('parses error cancel response', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'GonderiGeriCekResult' => 'HATA: Gonderi bulunamadi',
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->isCancelled())->toBeFalse()
        ->and($response->getMessage())->toBe('HATA: Gonderi bulunamadi');
});

it('parses empty result as error', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'GonderiGeriCekResult' => '',
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->isCancelled())->toBeFalse();
});

it('returns raw data', function () {
    $data = (object) ['GonderiGeriCekResult' => 'Islem basarili'];
    $response = new CancelShipmentResponse(createMockRequest(), $data);

    expect($response->getData())->toBe($data);
});
