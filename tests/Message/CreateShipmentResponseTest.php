<?php

declare(strict_types=1);

use Omniship\Surat\Message\CreateShipmentResponse;

use function Omniship\Surat\Tests\createMockRequest;

it('parses successful response', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResult' => (object) [
            'isError' => false,
            'Message' => 'Islem basarili',
            'Barcode' => '12345678901234',
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getBarcode())->toBe('12345678901234')
        ->and($response->getMessage())->toBe('Islem basarili')
        ->and($response->getLabel())->toBeNull()
        ->and($response->getTotalCharge())->toBeNull()
        ->and($response->getCurrency())->toBeNull();
});

it('parses flat successful response', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'isError' => false,
        'Message' => 'Basarili',
        'Barcode' => '98765432109876',
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getBarcode())->toBe('98765432109876')
        ->and($response->getMessage())->toBe('Basarili');
});

it('parses error response', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResult' => (object) [
            'isError' => true,
            'Message' => 'Alici adresi bos olamaz',
            'Barcode' => '',
        ],
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->getMessage())->toBe('Alici adresi bos olamaz')
        ->and($response->getBarcode())->toBeNull();
});

it('returns null barcode when empty', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'isError' => false,
        'Message' => 'OK',
        'Barcode' => '',
    ]);

    expect($response->getBarcode())->toBeNull();
});

it('returns shipment id as null (Surat does not return a separate shipment ID)', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'isError' => false,
        'Message' => 'OK',
        'Barcode' => '12345678901234',
    ]);

    expect($response->getShipmentId())->toBeNull();
});

it('returns raw data', function () {
    $data = (object) [
        'isError' => false,
        'Message' => 'OK',
        'Barcode' => '12345678901234',
    ];
    $response = new CreateShipmentResponse(createMockRequest(), $data);

    expect($response->getData())->toBe($data);
});

it('handles tracking number from request', function () {
    $request = createMockRequest();
    $request->initialize(['trackingNumber' => 'CUSTOM-TRACK']);

    $response = new CreateShipmentResponse($request, (object) [
        'isError' => false,
        'Message' => 'OK',
        'Barcode' => '12345678901234',
    ]);

    expect($response->getTrackingNumber())->toBe('CUSTOM-TRACK');
});
