<?php

declare(strict_types=1);

use Omniship\Surat\Carrier;
use Omniship\Surat\Message\CancelShipmentRequest;
use Omniship\Surat\Message\CreateShipmentRequest;
use Omniship\Surat\Message\GetTrackingStatusRequest;

use function Omniship\Surat\Tests\createMockSoapClient;

beforeEach(function () {
    $this->carrier = new Carrier(createMockSoapClient());
    $this->carrier->initialize([
        'kullaniciAdi' => 'TESTUSER',
        'sifre' => 'TESTPASS',
        'cariKodu' => 'TESTCARI',
        'webSifre' => 'TESTWEBPASS',
        'testMode' => true,
    ]);
});

it('has the correct name', function () {
    expect($this->carrier->getName())->toBe('Sürat Kargo');
    expect($this->carrier->getShortName())->toBe('Surat');
});

it('has correct default parameters', function () {
    $carrier = new Carrier(createMockSoapClient());
    $carrier->initialize();

    expect($carrier->getKullaniciAdi())->toBe('')
        ->and($carrier->getSifre())->toBe('')
        ->and($carrier->getCariKodu())->toBe('')
        ->and($carrier->getWebSifre())->toBe('')
        ->and($carrier->getTestMode())->toBeFalse();
});

it('initializes with custom parameters', function () {
    expect($this->carrier->getKullaniciAdi())->toBe('TESTUSER')
        ->and($this->carrier->getSifre())->toBe('TESTPASS')
        ->and($this->carrier->getCariKodu())->toBe('TESTCARI')
        ->and($this->carrier->getWebSifre())->toBe('TESTWEBPASS')
        ->and($this->carrier->getTestMode())->toBeTrue();
});

it('returns test WSDL URL in test mode', function () {
    $reflection = new ReflectionMethod($this->carrier, 'getWsdlUrl');

    expect($reflection->invoke($this->carrier))
        ->toContain('prova.suratkargo.com.tr');
});

it('returns production WSDL URL in production mode', function () {
    $this->carrier->setTestMode(false);
    $reflection = new ReflectionMethod($this->carrier, 'getWsdlUrl');

    expect($reflection->invoke($this->carrier))
        ->toContain('webservices.suratkargo.com.tr');
});

it('supports createShipment method', function () {
    expect($this->carrier->supports('createShipment'))->toBeTrue();
});

it('supports getTrackingStatus method', function () {
    expect($this->carrier->supports('getTrackingStatus'))->toBeTrue();
});

it('supports cancelShipment method', function () {
    expect($this->carrier->supports('cancelShipment'))->toBeTrue();
});

it('creates a CreateShipmentRequest', function () {
    $request = $this->carrier->createShipment([
        'referenceNumber' => 'TEST123',
    ]);

    expect($request)->toBeInstanceOf(CreateShipmentRequest::class);
});

it('creates a GetTrackingStatusRequest', function () {
    $request = $this->carrier->getTrackingStatus([
        'trackingNumber' => 'TEST123',
    ]);

    expect($request)->toBeInstanceOf(GetTrackingStatusRequest::class);
});

it('creates a CancelShipmentRequest', function () {
    $request = $this->carrier->cancelShipment([
        'trackingNumber' => 'TEST123',
    ]);

    expect($request)->toBeInstanceOf(CancelShipmentRequest::class);
});

it('sets and gets kullaniciAdi', function () {
    $this->carrier->setKullaniciAdi('NEWUSER');

    expect($this->carrier->getKullaniciAdi())->toBe('NEWUSER');
});

it('sets and gets sifre', function () {
    $this->carrier->setSifre('NEWPASS');

    expect($this->carrier->getSifre())->toBe('NEWPASS');
});

it('sets and gets cariKodu', function () {
    $this->carrier->setCariKodu('NEWCARI');

    expect($this->carrier->getCariKodu())->toBe('NEWCARI');
});

it('sets and gets webSifre', function () {
    $this->carrier->setWebSifre('NEWWEBPASS');

    expect($this->carrier->getWebSifre())->toBe('NEWWEBPASS');
});
