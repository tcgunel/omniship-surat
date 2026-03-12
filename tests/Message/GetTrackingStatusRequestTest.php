<?php

declare(strict_types=1);

use Omniship\Surat\Message\GetTrackingStatusRequest;
use Omniship\Surat\Message\GetTrackingStatusResponse;

use function Omniship\Surat\Tests\createMockSoapClient;
use function Omniship\Surat\Tests\createMockSoapClientWithResponse;

beforeEach(function () {
    $this->request = new GetTrackingStatusRequest(createMockSoapClient());
    $this->request->initialize([
        'cariKodu' => 'TESTCARI',
        'webSifre' => 'TESTWEBPASS',
        'trackingNumber' => 'TRACK-001',
    ]);
});

it('builds correct SOAP data', function () {
    $data = $this->request->getData();

    expect($data['CariKodu'])->toBe('TESTCARI')
        ->and($data['Sifre'])->toBe('TESTWEBPASS')
        ->and($data['WebSiparisKodu'])->toBe('TRACK-001');
});

it('throws when required parameters are missing', function () {
    $request = new GetTrackingStatusRequest(createMockSoapClient());
    $request->initialize([
        'cariKodu' => 'TESTCARI',
        'webSifre' => 'TESTWEBPASS',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns GetTrackingStatusResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'KargoTakipHareketDetayliV2Result' => 'Teslim Edildi|2026-02-22 14:30:00|Ankara Sube',
    ]);

    $request = new GetTrackingStatusRequest($soapClient);
    $request->initialize([
        'cariKodu' => 'TESTCARI',
        'webSifre' => 'TESTWEBPASS',
        'trackingNumber' => 'TRACK-001',
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(GetTrackingStatusResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});
