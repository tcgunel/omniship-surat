<?php

declare(strict_types=1);

use Omniship\Common\Address;
use Omniship\Common\Enum\PaymentType;
use Omniship\Common\Package;
use Omniship\Surat\Message\CreateShipmentRequest;
use Omniship\Surat\Message\CreateShipmentResponse;

use function Omniship\Surat\Tests\createMockSoapClient;
use function Omniship\Surat\Tests\createMockSoapClientWithResponse;

beforeEach(function () {
    $this->request = new CreateShipmentRequest(createMockSoapClient());
    $this->request->initialize([
        'kullaniciAdi' => 'TESTUSER',
        'sifre' => 'TESTPASS',
        'referenceNumber' => 'REF-001',
        'shipTo' => new Address(
            name: 'Mehmet Demir',
            street1: 'Kizilirmak Mah. 123. Sok. No:5',
            city: 'Ankara',
            district: 'Cankaya',
            postalCode: '06420',
            country: 'TR',
            phone: '05559876543',
            email: 'mehmet@example.com',
        ),
        'packages' => [
            new Package(weight: 2.5, desi: 3),
        ],
    ]);
});

it('builds correct SOAP data', function () {
    $data = $this->request->getData();

    expect($data['KullaniciAdi'])->toBe('TESTUSER')
        ->and($data['Sifre'])->toBe('TESTPASS')
        ->and($data['Gonderi'])->toBeArray();

    $gonderi = $data['Gonderi'];

    expect($gonderi['KisiKurum'])->toBe('Mehmet Demir')
        ->and($gonderi['AliciAdresi'])->toBe('Kizilirmak Mah. 123. Sok. No:5')
        ->and($gonderi['Il'])->toBe('Ankara')
        ->and($gonderi['Ilce'])->toBe('Cankaya')
        ->and($gonderi['TelefonCep'])->toBe('05559876543')
        ->and($gonderi['Email'])->toBe('mehmet@example.com')
        ->and($gonderi['ReferansNo'])->toBe('REF-001')
        ->and($gonderi['BirimDesi'])->toBe('3')
        ->and($gonderi['BirimKg'])->toBe('2.5')
        ->and($gonderi['Adet'])->toBe(1);
});

it('builds address from street1 and street2', function () {
    $this->request->setShipTo(new Address(
        name: 'Test',
        street1: 'Line 1',
        street2: 'Line 2',
        city: 'Istanbul',
        phone: '05551234567',
    ));

    $data = $this->request->getData();

    expect($data['Gonderi']['AliciAdresi'])->toBe('Line 1 Line 2');
});

it('calculates totals from multiple packages', function () {
    $this->request->setPackages([
        new Package(weight: 1.5, desi: 2),
        new Package(weight: 3.0, desi: 4, quantity: 2),
    ]);

    $data = $this->request->getData();
    $gonderi = $data['Gonderi'];

    expect($gonderi['BirimDesi'])->toBe('6')
        ->and($gonderi['BirimKg'])->toBe('4.5')
        ->and($gonderi['Adet'])->toBe(3);
});

it('sets sender pays payment type', function () {
    $this->request->setPaymentType(PaymentType::SENDER);

    $data = $this->request->getData();

    expect($data['Gonderi']['OdemeTipi'])->toBe(1);
});

it('sets receiver pays payment type', function () {
    $this->request->setPaymentType(PaymentType::RECEIVER);

    $data = $this->request->getData();

    expect($data['Gonderi']['OdemeTipi'])->toBe(2);
});

it('defaults payment type to sender pays', function () {
    $data = $this->request->getData();

    expect($data['Gonderi']['OdemeTipi'])->toBe(1);
});

it('includes COD fields when cash on delivery is set', function () {
    $this->request->setCashOnDelivery(true);
    $this->request->setCodAmount(150.50);

    $data = $this->request->getData();
    $gonderi = $data['Gonderi'];

    expect($gonderi['KapidanOdemeTahsilatTipi'])->toBe(1)
        ->and($gonderi['KapidanOdemeTutari'])->toBe('150.5');
});

it('has no COD when not set', function () {
    $data = $this->request->getData();
    $gonderi = $data['Gonderi'];

    expect($gonderi['KapidanOdemeTahsilatTipi'])->toBe(0)
        ->and($gonderi['KapidanOdemeTutari'])->toBe('');
});

it('sets custom tracking number', function () {
    $this->request->setTrackingNumber('CUSTOM-TRACK-001');

    $data = $this->request->getData();

    expect($data['Gonderi']['OzelKargoTakipNo'])->toBe('CUSTOM-TRACK-001');
});

it('sets cargo type', function () {
    $this->request->setCargoType(3); // Koli

    $data = $this->request->getData();

    expect($data['Gonderi']['KargoTuru'])->toBe(3);
});

it('defaults cargo type to koli (3)', function () {
    $data = $this->request->getData();

    expect($data['Gonderi']['KargoTuru'])->toBe(3);
});

it('sets cargo content', function () {
    $this->request->setCargoContent('Elektronik urun');

    $data = $this->request->getData();

    expect($data['Gonderi']['KargoIcerigi'])->toBe('Elektronik urun');
});

it('throws when required parameters are missing', function () {
    $request = new CreateShipmentRequest(createMockSoapClient());
    $request->initialize([
        'kullaniciAdi' => 'TESTUSER',
        'sifre' => 'TESTPASS',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns CreateShipmentResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResult' => (object) [
            'isError' => false,
            'Message' => 'Islem basarili',
            'Barcode' => '12345678901234',
        ],
    ]);

    $request = new CreateShipmentRequest($soapClient);
    $request->initialize([
        'kullaniciAdi' => 'TESTUSER',
        'sifre' => 'TESTPASS',
        'referenceNumber' => 'REF-001',
        'trackingNumber' => 'CUSTOM-001',
        'shipTo' => new Address(
            name: 'Test',
            street1: 'Address',
            city: 'Istanbul',
            phone: '05551234567',
        ),
        'packages' => [new Package(weight: 1.0)],
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(CreateShipmentResponse::class)
        ->and($response->isSuccessful())->toBeTrue()
        ->and($response->getBarcode())->toBe('12345678901234')
        ->and($response->getTrackingNumber())->toBe('CUSTOM-001');
});
