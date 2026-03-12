<?php

declare(strict_types=1);

namespace Omniship\Surat\Message;

use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;

class CancelShipmentRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'GonderiGeriCek';
    }

    public function getCancelReason(): ?string
    {
        return $this->getParameter('cancelReason');
    }

    public function setCancelReason(string $reason): static
    {
        return $this->setParameter('cancelReason', $reason);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('kullaniciAdi', 'sifre', 'trackingNumber', 'cancelReason');

        return [
            'KullaniciAdi' => $this->getParameter('kullaniciAdi'),
            'Sifre' => $this->getParameter('sifre'),
            'OzelKargoTakipNo' => $this->getTrackingNumber(),
            'IptalNeden' => $this->getCancelReason(),
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new CancelShipmentResponse($this, $data);
    }
}
