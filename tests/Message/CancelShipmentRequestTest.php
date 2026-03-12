<?php

declare(strict_types=1);

use Omniship\Surat\Message\CancelShipmentRequest;
use Omniship\Surat\Message\CancelShipmentResponse;

use function Omniship\Surat\Tests\createMockSoapClient;
use function Omniship\Surat\Tests\createMockSoapClientWithResponse;

beforeEach(function () {
    $this->request = new CancelShipmentRequest(createMockSoapClient());
    $this->request->initialize([
        'kullaniciAdi' => 'TESTUSER',
        'sifre' => 'TESTPASS',
        'trackingNumber' => 'CANCEL-001',
        'cancelReason' => 'Musteri siparis iptali',
    ]);
});

it('builds correct SOAP data', function () {
    $data = $this->request->getData();

    expect($data['KullaniciAdi'])->toBe('TESTUSER')
        ->and($data['Sifre'])->toBe('TESTPASS')
        ->and($data['OzelKargoTakipNo'])->toBe('CANCEL-001')
        ->and($data['IptalNeden'])->toBe('Musteri siparis iptali');
});

it('throws when required parameters are missing', function () {
    $request = new CancelShipmentRequest(createMockSoapClient());
    $request->initialize([
        'kullaniciAdi' => 'TESTUSER',
        'sifre' => 'TESTPASS',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('throws when cancel reason is missing', function () {
    $request = new CancelShipmentRequest(createMockSoapClient());
    $request->initialize([
        'kullaniciAdi' => 'TESTUSER',
        'sifre' => 'TESTPASS',
        'trackingNumber' => 'CANCEL-001',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns CancelShipmentResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'GonderiGeriCekResult' => 'Islem basarili',
    ]);

    $request = new CancelShipmentRequest($soapClient);
    $request->initialize([
        'kullaniciAdi' => 'TESTUSER',
        'sifre' => 'TESTPASS',
        'trackingNumber' => 'CANCEL-001',
        'cancelReason' => 'Siparis iptali',
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(CancelShipmentResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});
