<?php

declare(strict_types=1);

namespace Omniship\Surat\Message;

use Omniship\Common\Label;
use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\ShipmentResponse;

class CreateShipmentResponse extends AbstractResponse implements ShipmentResponse
{
    public function isSuccessful(): bool
    {
        $result = $this->getResultObject();

        if ($result === null) {
            return false;
        }

        if (isset($result->isError)) {
            return !$result->isError;
        }

        return false;
    }

    public function getMessage(): ?string
    {
        $result = $this->getResultObject();

        if ($result !== null && isset($result->Message)) {
            return (string) $result->Message;
        }

        return null;
    }

    public function getCode(): ?string
    {
        return null;
    }

    public function getShipmentId(): ?string
    {
        return null;
    }

    public function getTrackingNumber(): ?string
    {
        $request = $this->getRequest();

        if (method_exists($request, 'getTrackingNumber')) {
            $trackingNumber = $request->getTrackingNumber();
            if ($trackingNumber !== null && $trackingNumber !== '') {
                return $trackingNumber;
            }
        }

        return null;
    }

    public function getBarcode(): ?string
    {
        $result = $this->getResultObject();

        if ($result !== null && isset($result->Barcode) && $result->Barcode !== '') {
            return (string) $result->Barcode;
        }

        return null;
    }

    public function getLabel(): ?Label
    {
        return null;
    }

    public function getTotalCharge(): ?float
    {
        return null;
    }

    public function getCurrency(): ?string
    {
        return null;
    }

    private function getResultObject(): ?object
    {
        $data = $this->data;

        if (!is_object($data)) {
            return null;
        }

        if (isset($data->GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResult)) {
            $result = $data->GonderiyiKargoyaGonderYeniSiparisBarkodOlusturResult;

            return is_object($result) ? $result : null;
        }

        // Flat response (isError directly on data)
        if (isset($data->isError)) {
            return $data;
        }

        return null;
    }
}
