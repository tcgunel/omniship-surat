<?php

declare(strict_types=1);

namespace Omniship\Surat\Message;

use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;

class GetTrackingStatusRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'KargoTakipHareketDetayliV2';
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('cariKodu', 'webSifre', 'trackingNumber');

        return [
            'CariKodu' => $this->getParameter('cariKodu'),
            'Sifre' => $this->getParameter('webSifre'),
            'WebSiparisKodu' => $this->getTrackingNumber(),
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new GetTrackingStatusResponse($this, $data);
    }
}
