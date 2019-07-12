<?php

use Egcs\MendrixApi;
use Egcs\MendrixApiException;

require './vendor/autoload.php';

$client_host = 'http://' . $_SERVER['HTTP_HOST'];
$dataFile = __DIR__ . '/data/datastore.json';
$configFile = __DIR__ . '/data/config.json';


function storeConfig(string $configFile, array $config)
{
    //store in safe location, for this demo the keys are stored in a public json file!
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
}

function loadConfig(string $configFile): ?array
{
    return file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
}

function clearTokens($dataFile) {
    unlink($dataFile);
}

$config = loadConfig($configFile);

$api = new MendrixApi($config['client_id'] ?? 0, $config['client_secret'] ?? '');
//store in safe location, for this demo the tokens are stored in a public json file!
$api->setTokenPath($dataFile);
//set debug cookie (doesn't work for now)
$api->setCookies([
    'XDEBUG_SESSION' => 'PHPSTORM',
]);

$error = null;
$result = null;

$task = $_GET['task'] ?? '';
switch ($task) {
    case 'config';
        storeConfig($configFile, $_POST['config']);
        clearTokens($dataFile);
        header(sprintf("Location: %s", $client_host));
        break;
    case 'user';
        try {
            $user = $api->getUser();
            $result = compact('user');

        } catch (MendrixApiException $e) {
            if ($data = $e->getResponseData()) {
                $error = $data['message'];
            } else {
                $error = $e->getMessage();
            }
        }
        break;
    case 'serverdate';
        try {
            $result = $api->getServerdate();

        } catch (MendrixApiException $e) {
            if ($data = $e->getResponseData()) {
                $error = $data['message'];
            } else {
                $error = $e->getMessage();
            }
        }
        break;
    case 'orderids';
        try {
            $result = $api->getOrderIds('2019-01-01T00:00:00+02:00', '2019-03-01T00:00:00+02:00', 110, -1);

        } catch (MendrixApiException $e) {
            if ($data = $e->getResponseData()) {
                $error = $data['message'];
            } else {
                $error = $e->getMessage();
            }
            $error = $e->getMessage();
        }
        break;
    case 'orderbyids';
        try {
            $result = $api->getOrderByIds();

        } catch (MendrixApiException $e) {
            if ($data = $e->getResponseData()) {
                $error = $data['message'];
            } else {
                $error = $e->getMessage();
            }
        }
        break;
    case 'tracesgoods';
        try {
            $result = $api->getTracesGoods();

        } catch (MendrixApiException $e) {
            if ($data = $e->getResponseData()) {
                $error = $data['message'];
            } else {
                $error = $e->getMessage();
            }
        }
        break;
    default;
        break;
}


?>

<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Client</title>

</head>
<body>

<h1>Voorbeeld client</h1>

<h3>Configuratie</h3>
<form action="/?task=config" method="post">
    <p>
        <strong>Client ID:</strong><br/>
        <input type="text" name="config[client_id]" value="<?=$config['client_id'] ?? ''?>"/> <br/>
        <strong>Client Secret:</strong><br/>
        <input type="text" name="config[client_secret]" value="<?=$config['client_secret'] ?? ''?>" style="width: 300px"/> <br/>
        <strong>Scope: <small>(optioneel)</small></strong><br/>
        <input type="text" name="config[scope]" value="<?=$config['scope'] ?? ''?>" style="width: 300px"/> <br/>

    </p>
    <p>
        <button>Opslaan</button>
    </p>
</form>
<p>

    <a href="?task=user">Toon gebruiker</a>
</p>
<p>

    <a href="?task=serverdate">Toon serverdatum</a>
</p>
<p>

    <a href="?task=orderids">Toon order ids</a>
</p>
<p>

    <a href="?task=orderbyids">Laad orders met ids</a>
</p>
<p>

    <a href="?task=tracesgoods">Laad traces</a>
</p>
<?php if ($error): ?>
<p>
    <strong style="color: #c0181e"><?= $error?></strong>
</p>
<?php endif; ?>
<?php if ($result): ?>
<p>
    <pre><?php var_dump($result)?></pre>
</p>
<?php endif; ?>
</body>
</html>
