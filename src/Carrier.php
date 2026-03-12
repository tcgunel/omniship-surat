<?php

declare(strict_types=1);

namespace Omniship\Surat;

use Omniship\Common\AbstractSoapCarrier;
use Omniship\Common\Message\RequestInterface;
use Omniship\Surat\Message\CancelShipmentRequest;
use Omniship\Surat\Message\CreateShipmentRequest;
use Omniship\Surat\Message\GetTrackingStatusRequest;

class Carrier extends AbstractSoapCarrier
{
    private const WSDL_PRODUCTION = 'https://webservices.suratkargo.com.tr/services.asmx?WSDL';
    private const WSDL_TEST = 'https://prova.suratkargo.com.tr/services.asmx?WSDL';

    public function getName(): string
    {
        return 'Sürat Kargo';
    }

    public function getShortName(): string
    {
        return 'Surat';
    }

    protected function getWsdlUrl(): string
    {
        return $this->getTestMode() ? self::WSDL_TEST : self::WSDL_PRODUCTION;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultParameters(): array
    {
        return [
            'kullaniciAdi' => '',
            'sifre' => '',
            'cariKodu' => '',
            'webSifre' => '',
            'testMode' => false,
        ];
    }

    public function getKullaniciAdi(): string
    {
        return $this->getParameter('kullaniciAdi') ?? '';
    }

    public function setKullaniciAdi(string $kullaniciAdi): static
    {
        return $this->setParameter('kullaniciAdi', $kullaniciAdi);
    }

    public function getSifre(): string
    {
        return $this->getParameter('sifre') ?? '';
    }

    public function setSifre(string $sifre): static
    {
        return $this->setParameter('sifre', $sifre);
    }

    public function getCariKodu(): string
    {
        return $this->getParameter('cariKodu') ?? '';
    }

    public function setCariKodu(string $cariKodu): static
    {
        return $this->setParameter('cariKodu', $cariKodu);
    }

    public function getWebSifre(): string
    {
        return $this->getParameter('webSifre') ?? '';
    }

    public function setWebSifre(string $webSifre): static
    {
        return $this->setParameter('webSifre', $webSifre);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createShipment(array $options = []): RequestInterface
    {
        return $this->createRequest(CreateShipmentRequest::class, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getTrackingStatus(array $options = []): RequestInterface
    {
        return $this->createRequest(GetTrackingStatusRequest::class, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function cancelShipment(array $options = []): RequestInterface
    {
        return $this->createRequest(CancelShipmentRequest::class, $options);
    }
}
