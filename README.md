# Omniship Surat Kargo

Surat Kargo carrier driver for the [Omniship](https://github.com/tcgunel/omniship) shipping library.

Uses the unified SOAP API at `webservices.suratkargo.com.tr/services.asmx` with the newer `GonderiyiKargoyaGonderYeniSiparisBarkodOlustur` method that returns structured responses with barcode.

## Installation

```bash
composer require tcgunel/omniship-surat
```

## Usage

### Initialize

```php
use Omniship\Omniship;

$carrier = Omniship::create('Surat');
$carrier->initialize([
    'kullaniciAdi' => '1038106246',    // 10-digit Cari Kodu
    'sifre' => '123456',               // Cari Sifre (for shipment creation & cancel)
    'cariKodu' => '1038106246',        // Same Cari Kodu (for tracking)
    'webSifre' => '123456.Ff',         // Web Servis Sifresi (for tracking queries)
    'testMode' => true,
]);
```

> **Note:** Surat Kargo uses separate passwords for shipment operations (`sifre`) and tracking queries (`webSifre`). The web service password is customer-defined at [ekargo.suratkargo.com.tr](https://ekargo.suratkargo.com.tr).

### Create Shipment

```php
use Omniship\Common\Address;
use Omniship\Common\Package;
use Omniship\Common\Enum\PaymentType;

$response = $carrier->createShipment([
    'shipTo' => new Address(
        name: 'Mehmet Demir',
        street1: 'Ataturk Cad. No:42',
        city: 'Ankara',
        district: 'Cankaya',
        phone: '05559876543',
        email: 'mehmet@example.com',
    ),
    'packages' => [
        new Package(weight: 2.5, desi: 3),
    ],
    'trackingNumber' => 'SIPARIS-001',     // OzelKargoTakipNo (your custom tracking code)
    'referenceNumber' => 'REF-001',        // Optional: group shipments
    'paymentType' => PaymentType::SENDER,  // SENDER (1=Pesin) or RECEIVER (2=Ucret Alici)
    'cargoType' => 3,                      // 1=Dosya, 2=Mi, 3=Koli
    'cargoContent' => 'Elektronik urun',   // Optional
])->send();

if ($response->isSuccessful()) {
    echo $response->getBarcode();        // 14-digit barcode from Surat
    echo $response->getTrackingNumber(); // OzelKargoTakipNo you provided
} else {
    echo $response->getMessage();        // Error description
}
```

### Cash on Delivery (Kapida Odeme)

```php
$response = $carrier->createShipment([
    'shipTo' => new Address(/* ... */),
    'packages' => [new Package(weight: 1.0)],
    'trackingNumber' => 'COD-001',
    'cashOnDelivery' => true,
    'codAmount' => 150.50,               // KapidanOdemeTutari
    // KapidanOdemeTahsilatTipi: 1=Nakit, 2=POS
])->send();
```

### Track Shipment

```php
$response = $carrier->getTrackingStatus([
    'trackingNumber' => 'SIPARIS-001',   // OzelKargoTakipNo or WebSiparisKodu
])->send();

if ($response->isSuccessful()) {
    $info = $response->getTrackingInfo();
    echo $info->trackingNumber;
    echo $info->status->value;           // DELIVERED, IN_TRANSIT, etc.

    foreach ($info->events as $event) {
        echo $event->description;
        echo $event->occurredAt->format('Y-m-d H:i');
        echo $event->location;
    }
}
```

### Cancel Shipment

```php
$response = $carrier->cancelShipment([
    'trackingNumber' => 'SIPARIS-001',   // OzelKargoTakipNo
    'cancelReason' => 'Musteri istegi',  // Required: IptalNeden
])->send();

if ($response->isSuccessful() && $response->isCancelled()) {
    echo 'Shipment cancelled';
}
```

## API Endpoints

| Environment | URL |
|-------------|-----|
| Production | `https://webservices.suratkargo.com.tr/services.asmx` |
| Test | `https://prova.suratkargo.com.tr/services.asmx` |

### Legacy Endpoints (from PDF docs, for reference)

| Service | URL |
|---------|-----|
| Gonderi (Prod) | `http://www.suratkargo.com.tr/GonderiWebServiceGercek/service.asmx` |
| Gonderi (Test) | `http://www.suratkargo.com.tr/GonderiWebServiceProva/service.asmx` |
| Takip (Prod) | `http://webservices.suratkargo.com.tr/services.asmx` |
| Takip (Test) | `https://esatis.suratkargo.com.tr/services.asmx` |

## Credentials

Surat Kargo uses **three credentials** (unlike most Turkish carriers):

| Credential | Field | Used For | Set By |
|-----------|-------|----------|--------|
| Cari Kodu | `kullaniciAdi` / `cariKodu` | All operations | Surat Kargo (10-digit) |
| Cari Sifresi | `sifre` | Shipment creation, cancel | Surat Kargo |
| Web Servis Sifresi | `webSifre` | Tracking, reporting | Customer (at ekargo portal) |

### Test Credentials

| Field | Value |
|-------|-------|
| Cari Kodu | `1038106246` |
| Cari Sifresi | `123456` |
| Web Servis Sifresi | `123456.Ff` |
| COD Cari Kodu | `1038106247` |
| COD Cari Sifresi | `1234567` |
| Test Tracking Codes | `123456789`, `12v34567g89` |

## SOAP Methods Used

| Operation | SOAP Method | Description |
|-----------|-------------|-------------|
| Create Shipment | `GonderiyiKargoyaGonderYeniSiparisBarkodOlustur` | Returns structured response with barcode |
| Track Shipment | `KargoTakipHareketDetayliV2` | Detailed tracking by WebSiparisKodu |
| Cancel Shipment | `GonderiGeriCek` | Cancel with reason (IptalNeden) |

### Alternative Methods Available

| Method | Description |
|--------|-------------|
| `GonderiyiKargoyaGonder` | Legacy create (returns "Tamam" string, no barcode) |
| `GonderiyiKargoyaGonderYeni` | Newer create (returns string, no barcode) |
| `WebSiparisKodu` | Track by order code (returns DataSet) |
| `TakipNo` | Track by Surat's barcode number (returns DataSet) |
| `GonderiSil` | Delete shipment (different auth: cariKodu + WebPassword) |
| `WebSiparisKodundanKargoTeslimatBilgisi` | Delivery info (returns TeslimatBilgisiSonuc) |

## Gonderi Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| KisiKurum | string | Yes | Recipient name |
| AliciAdresi | string | Yes | Full address (single field) |
| Il | string | Yes | City/Province |
| Ilce | string | Yes | District |
| TelefonCep | string | Conditional | Mobile phone (required if SMS service) |
| KargoTuru | int | Yes | 1=Dosya, 2=Mi, 3=Koli |
| OdemeTipi | int | Yes | 1=Pesin (sender), 2=Ucret Alici (receiver) |
| Adet | int | Yes | Package count |
| OzelKargoTakipNo | string | No | Your custom tracking code |
| BirimDesi | string | No | Volumetric weight per unit |
| BirimKg | string | No | Weight per unit |
| KapidanOdemeTahsilatTipi | int | If COD | 1=Nakit, 2=POS |
| KapidanOdemeTutari | string | If COD | COD amount |
| IrsaliyeSeriNo | string | If COD | Invoice serial number |
| IrsaliyeSiraNo | string | If COD | Invoice sequence number |
| TeslimSekli | int | No | 1=Address delivery, 2=Branch pickup |

## Notes

- The unified service at `webservices.suratkargo.com.tr` contains both shipment creation and tracking methods. Legacy separate endpoints may return 404.
- `OzelKargoTakipNo` (custom tracking number) set during creation becomes the `WebSiparisKodu` used for tracking queries.
- `TakipNo` in tracking responses is Surat Kargo's internal barcode number (14 digits).
- Address is a **single field** (`AliciAdresi`), not structured. `Il` (city) and `Ilce` (district) are separate required fields.
- Cancel requires `IptalNeden` (cancellation reason) — it is mandatory.
- The `EkHizmetler` field accepts comma-separated service names: `GondericiyeSms`, `TelefonIhbar`, `AliciyaSms`, `AdrestenAlim`.

## Testing

```bash
vendor/bin/pest
```

## License

MIT
