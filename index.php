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
//set debug cookie
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
            $error = $e->getMessage();
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
