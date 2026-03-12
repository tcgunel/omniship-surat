<?php

declare(strict_types=1);

namespace Omniship\Surat\Message;

use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\CancelResponse;

class CancelShipmentResponse extends AbstractResponse implements CancelResponse
{
    public function isSuccessful(): bool
    {
        $result = $this->getResultString();

        if ($result === null || $result === '') {
            return false;
        }

        // Error responses typically start with "HATA" or contain error indicators
        return !str_starts_with($result, 'HATA');
    }

    public function isCancelled(): bool
    {
        return $this->isSuccessful();
    }

    public function getMessage(): ?string
    {
        return $this->getResultString();
    }

    public function getCode(): ?string
    {
        return null;
    }

    private function getResultString(): ?string
    {
        $data = $this->data;

        if (is_string($data)) {
            return $data !== '' ? $data : null;
        }

        if (!is_object($data)) {
            return null;
        }

        if (isset($data->GonderiGeriCekResult)) {
            $result = $data->GonderiGeriCekResult;

            return is_string($result) && $result !== '' ? $result : null;
        }

        return null;
    }
}
