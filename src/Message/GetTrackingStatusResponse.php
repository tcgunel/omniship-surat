<?php

declare(strict_types=1);

namespace Omniship\Surat\Message;

use Omniship\Common\Enum\ShipmentStatus;
use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\TrackingResponse;
use Omniship\Common\TrackingEvent;
use Omniship\Common\TrackingInfo;

class GetTrackingStatusResponse extends AbstractResponse implements TrackingResponse
{
    /**
     * Turkish tracking status keywords to ShipmentStatus mapping.
     *
     * @var array<string, ShipmentStatus>
     */
    private const STATUS_MAP = [
        'Kargo Kabul' => ShipmentStatus::PICKED_UP,
        'Kabul' => ShipmentStatus::PICKED_UP,
        'Aktarma Merkezinde' => ShipmentStatus::IN_TRANSIT,
        'Aktarma' => ShipmentStatus::IN_TRANSIT,
        'Transfer' => ShipmentStatus::IN_TRANSIT,
        'Dagitima Verildi' => ShipmentStatus::OUT_FOR_DELIVERY,
        'Dagitimda' => ShipmentStatus::OUT_FOR_DELIVERY,
        'Teslim Edildi' => ShipmentStatus::DELIVERED,
        'Teslim' => ShipmentStatus::DELIVERED,
        'Iade Edildi' => ShipmentStatus::RETURNED,
        'Iade' => ShipmentStatus::RETURNED,
        'Iptal' => ShipmentStatus::CANCELLED,
    ];

    public function isSuccessful(): bool
    {
        $result = $this->getResultString();

        return $result !== null && $result !== '';
    }

    public function getMessage(): ?string
    {
        return $this->getResultString();
    }

    public function getCode(): ?string
    {
        return null;
    }

    public function getTrackingInfo(): TrackingInfo
    {
        $result = $this->getResultString();
        $events = [];
        $status = ShipmentStatus::UNKNOWN;
        $trackingNumber = '';

        $request = $this->getRequest();
        if (method_exists($request, 'getTrackingNumber')) {
            $trackingNumber = $request->getTrackingNumber() ?? '';
        }

        if ($result !== null && $result !== '') {
            $lines = explode("\n", $result);

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $parts = explode('|', $line);
                $description = trim($parts[0]);
                $dateStr = trim($parts[1] ?? '');
                $location = trim($parts[2] ?? '') ?: null;

                $eventStatus = self::mapStatus($description);

                $dateTime = new \DateTimeImmutable();
                if ($dateStr !== '') {
                    try {
                        $dateTime = new \DateTimeImmutable($dateStr);
                    } catch (\Exception) {
                        // Keep default
                    }
                }

                $events[] = new TrackingEvent(
                    status: $eventStatus,
                    description: $description,
                    occurredAt: $dateTime,
                    location: $location,
                );
            }

            // Last event determines the overall status
            if ($events !== []) {
                $lastEvent = end($events);
                $status = $lastEvent->status;
            }
        }

        return new TrackingInfo(
            trackingNumber: $trackingNumber,
            status: $status,
            events: $events,
            carrier: 'Sürat Kargo',
        );
    }

    public static function mapStatus(string $statusText): ShipmentStatus
    {
        // Try exact match first
        if (isset(self::STATUS_MAP[$statusText])) {
            return self::STATUS_MAP[$statusText];
        }

        // Try partial match (keyword contained in status text)
        foreach (self::STATUS_MAP as $keyword => $shipmentStatus) {
            if (str_contains($statusText, $keyword)) {
                return $shipmentStatus;
            }
        }

        return ShipmentStatus::UNKNOWN;
    }

    private function getResultString(): ?string
    {
        $data = $this->data;

        if (!is_object($data)) {
            return null;
        }

        if (isset($data->KargoTakipHareketDetayliV2Result)) {
            $result = $data->KargoTakipHareketDetayliV2Result;

            return is_string($result) ? $result : null;
        }

        return null;
    }
}
