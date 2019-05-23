EGcs Mendrix API client
===

### Usage:

Works with _client_credentials_ OAuth2. Only Client-ID and Secret are needed.

```php
$api = new MendrixApi($client_id, $client_secret);
$user = $api->getUser();
```

Use with file-cache for tokens:

```php
$api->setTokenPath('secure/path/tokens.json');
```

See https://packagist.org/packages/kamermans/guzzle-oauth2-subscriber for more storage options.
