EGcs Mendrix API client
===

### Usage:

Works with _client_credentials_ OAuth2. Only Client-ID and Secret are needed.

```php
$api = new MendrixApi($client_id, $client_secret);
//test server connection
$user = $api->getServerdate();
//get user data
$user = $api->getUser();
```

View orders

```php
$from = '2020-01-04T23:00:00+01:00';
$to = '2020-01-07T22:59:59+01:00';
$page = 1;
$limit = 10;
$orders = $api->getOrders($from, $to, $page, $limit);
//returns ['total', 'page', 'limit', 'items',]
```

Create new order

```php
try {
    $result = $api->createOrder([
        'Contact' => 'Contact Persoon Logique',
        'Notes' => 'Notities Logique',
        'PickUp' => [
            'Instructions' => 'Ophaalinstructies',
            'ReferenceOur' => '#8746',
            'ReferenceYour' => 'ref-1234',
            'Requested' => [
                'DateTimeBegin' => '2020-01-04T09:00:00',
                'DateTimeEnd' => '2020-01-04T17:00:00'
            ]
        ],
        'Delivery' => [
            'Address' => [
                'Name' => 'Piet Klant', //required
                'Premise' => 'expeditie',
                'Street' => 'Straatweg', //required
                'Number' => '123',
                'PostalCode' => '1234 AB', //required
                'Place' => 'Ede', //required
                'Country' => 'Nederland', //required
                'CountryCode' => 'NL' //required
            ],
            'ContactName' => 'Piet Klant',
            'Instructions' => 'Instructies afleveren',
            'ReferenceOur' => '#8746',
            'ReferenceYour' => 'ref-1234',
            'Requested' => [
                'DateTimeBegin' => '2020-01-05T09:00:00',
                'DateTimeEnd' => '2020-01-05T17:00:00'
            ],
            'Connectivity' => [
                'Email' => 'piet@klant.nl',
                'Phone' => '012-2345678',
                'Mobile' => '06-12345678'
            ]
        ]
    ]);

} catch (MendrixApiException $e) {
    if ($data = $e->getResponseData()) {
        $error = $data['message'];
    } else {
        $error = $e->getMessage();
    }
}

```

Use with file-cache for tokens:

```php
$api->setTokenPath('secure/path/tokens.json');
```

See https://packagist.org/packages/kamermans/guzzle-oauth2-subscriber for more storage options.


### Demo

To use this demo, install with composer:

```
git clone git@github.com:Bixie/egcs-client.git
cd egcs-client
composer install
```

Mount this directory to a web-root or run a temporary server via php:

```
php -S localhost:8000
```

**The demo provides a minimal implementation of the API, without proper security/data validation. Do NOT use this code in production!**
