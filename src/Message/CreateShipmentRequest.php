<?php

declare(strict_types=1);

namespace Omniship\Surat\Message;

use Omniship\Common\Address;
use Omniship\Common\Enum\PaymentType;
use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;
use Omniship\Common\Package;

class CreateShipmentRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'GonderiyiKargoyaGonderYeniSiparisBarkodOlustur';
    }

    public function getReferenceNumber(): ?string
    {
        return $this->getParameter('referenceNumber');
    }

    public function setReferenceNumber(string $referenceNumber): static
    {
        return $this->setParameter('referenceNumber', $referenceNumber);
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->getParameter('paymentType');
    }

    public function setPaymentType(PaymentType $paymentType): static
    {
        return $this->setParameter('paymentType', $paymentType);
    }

    public function getCashOnDelivery(): bool
    {
        return (bool) $this->getParameter('cashOnDelivery');
    }

    public function setCashOnDelivery(bool $value): static
    {
        return $this->setParameter('cashOnDelivery', $value);
    }

    public function getCodAmount(): ?float
    {
        return $this->getParameter('codAmount');
    }

    public function setCodAmount(float $amount): static
    {
        return $this->setParameter('codAmount', $amount);
    }

    public function getCargoType(): int
    {
        return $this->getParameter('cargoType') ?? 3;
    }

    public function setCargoType(int $type): static
    {
        return $this->setParameter('cargoType', $type);
    }

    public function getCargoContent(): ?string
    {
        return $this->getParameter('cargoContent');
    }

    public function setCargoContent(string $content): static
    {
        return $this->setParameter('cargoContent', $content);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('kullaniciAdi', 'sifre', 'shipTo');

        $shipTo = $this->getShipTo();
        assert($shipTo instanceof Address);

        $packages = $this->getPackages() ?? [];

        $totalDesi = 0.0;
        $totalKg = 0.0;
        $totalCount = 0;

        foreach ($packages as $package) {
            $totalDesi += $package->getDesi() ?? 0;
            $totalKg += $package->weight;
            $totalCount += $package->quantity;
        }

        if ($totalCount === 0) {
            $totalCount = 1;
        }

        $paymentType = $this->getPaymentType();
        $odemeTipi = match ($paymentType) {
            PaymentType::RECEIVER => 2,
            default => 1,
        };

        $codType = 0;
        $codAmount = '';

        if ($this->getCashOnDelivery() && $this->getCodAmount() !== null) {
            $codType = 1;
            $codAmount = (string) $this->getCodAmount();
        }

        $gonderi = [
            'KisiKurum' => $shipTo->name ?? '',
            'SahisBirim' => '',
            'AliciAdresi' => $this->buildAddress($shipTo),
            'Il' => $shipTo->city ?? '',
            'Ilce' => $shipTo->district ?? '',
            'TelefonEv' => '',
            'TelefonIs' => '',
            'TelefonCep' => $shipTo->phone ?? '',
            'Email' => $shipTo->email ?? '',
            'AliciKodu' => '',
            'KargoTuru' => $this->getCargoType(),
            'OdemeTipi' => $odemeTipi,
            'IrsaliyeSeriNo' => '',
            'IrsaliyeSiraNo' => '',
            'ReferansNo' => $this->getReferenceNumber() ?? '',
            'OzelKargoTakipNo' => $this->getTrackingNumber() ?? '',
            'Adet' => $totalCount,
            'BirimDesi' => $totalDesi > 0 ? (string) $totalDesi : '',
            'BirimKg' => $totalKg > 0 ? (string) $totalKg : '',
            'KargoIcerigi' => $this->getCargoContent() ?? '',
            'KapidanOdemeTahsilatTipi' => $codType,
            'KapidanOdemeTutari' => $codAmount,
            'EkHizmetler' => '',
            'SevkAdresi' => '',
            'TeslimSekli' => 1,
            'TasimaSekli' => 0,
            'GonderiSekli' => 0,
            'TeslimSubeKodu' => '',
            'Pazaryerimi' => 0,
            'EntegrasyonFirmasi' => '',
            'Iademi' => false,
        ];

        return [
            'KullaniciAdi' => $this->getParameter('kullaniciAdi'),
            'Sifre' => $this->getParameter('sifre'),
            'Gonderi' => $gonderi,
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new CreateShipmentResponse($this, $data);
    }

    private function buildAddress(Address $address): string
    {
        $parts = array_filter([
            $address->street1,
            $address->street2,
        ]);

        return implode(' ', $parts);
    }
}
