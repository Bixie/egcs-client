<?php

use Egcs\MendrixApi;
use Egcs\MendrixApiException;

require './vendor/autoload.php';

$client_host = 'http://' . $_SERVER['HTTP_HOST'];
$dataFile = __DIR__ . '/data/datastore.json';
$configFile = __DIR__ . '/data/config.json';


function storeConfig (string $configFile, array $config)
{
    //store in safe location, for this demo the keys are stored in a public json file!
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
}

function loadConfig (string $configFile): ?array
{
    return file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
}

function clearTokens ($dataFile)
{
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
$errors = null;
$result = null;

$task = $_GET['task'] ?? $_POST['task'] ?? '';
$from = $_GET['from'] ?? (new DateTime())->sub(new DateInterval('P1W'))->format('Y-m-d');
$to = $_GET['to'] ?? (new DateTime())->format('Y-m-d');
$page = (int)($_GET['page'] ?? 1);

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
    case 'orders';
        try {
            $result = $api->getOrders($from, $to, $page, 10);

        } catch (MendrixApiException $e) {
            if ($data = $e->getResponseData()) {
                $error = $data['message'];
            } else {
                $error = $e->getMessage();
            }
        }
        break;
    case 'order_form';
        //
        break;
    case 'create_order';

        $input = $_POST['order'];
        echo '<pre>'. json_encode($input, JSON_PRETTY_PRINT). '</pre>';

        try {
            $result = $api->createOrder($input);

        } catch (MendrixApiException $e) {
            if ($data = $e->getResponseData()) {
                $error = $data['message'];
                $errors = $data['errors'] ?? [];
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

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
</head>
<body>

<div class="container mt-3">
    <div class="d-flex align-items-center">
        <h1 class="flex-grow-1">Voorbeeld client</h1>
        <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#configuration"
                aria-expanded="false" aria-controls="configuration">
            Configuratie
        </button>
    </div>

    <div class="collapse <?= empty($config['client_secret']) ? 'show' : '' ?> mt-3" id="configuration">
        <div class="card card-body">
            <form action="/?task=config" method="post">
                <div class="form-group">
                    <label for="client_id">Client ID:</label>
                    <input type="text" id="client_id" name="config[client_id]" value="<?= $config['client_id'] ?? '' ?>"
                           class="form-control"/>
                </div>
                <div class="form-group">
                    <label for="client_secret">Client Secret:</label>
                    <input type="text" id="client_secret" name="config[client_secret]"
                           value="<?= $config['client_secret'] ?? '' ?>"
                           class="form-control"/>
                </div>
                <div class="form-group">
                    <label for="scope">Scope:</label>
                    <input type="text" id="scope" name="config[scope]" value="<?= $config['scope'] ?? '' ?>"
                           class="form-control"/>
                    <small class="form-text text-muted">(optioneel)</small>
                </div>
                <p>

                </p>
                <p>
                    <button class="btn">Opslaan</button>
                </p>
            </form>
        </div>
    </div>

    <form name="nav" action="">
        <input type="hidden" name="task"/>
        <div class="row mt-3">
            <div class="col-sm">
                <div class="form-group row">
                    <label for="from" class="col-sm-4 col-form-label">Datum vanaf</label>
                    <div class="col-sm-8">
                        <input type="date" id="from" name="from" value="<?= $from ?>"
                               class="form-control"/>
                    </div>
                </div>
            </div>
            <div class="col-sm">
                <div class="form-group row">
                    <label for="to" class="col-sm-4 col-form-label">Datum tot</label>
                    <div class="col-sm-8">
                        <input type="date" id="to" name="to" value="<?= $to ?>"
                               class="form-control"/>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" hidden></button>
    </form>

    <nav class="nav nav-pills mt-3">
        <a class="nav-link <?= $task == 'user' ? 'active' : '' ?>" href="#"
           onclick="document.nav.task.value='user';document.nav.submit();return false">Toon gebruiker</a>
        <a class="nav-link <?= $task == 'serverdate' ? 'active' : '' ?>" href="#"
           onclick="document.nav.task.value='serverdate';document.nav.submit();return false">Toon serverdatum</a>
        <a class="nav-link <?= $task == 'orders' ? 'active' : '' ?>" href="#"
           onclick="document.nav.task.value='orders';document.nav.submit();return false">Toon orders</a>
        <a class="nav-link <?= $task == 'order_form' ? 'active' : '' ?>" href="#"
           onclick="document.nav.task.value='order_form';document.nav.submit();return false">Creëer order</a>
    </nav>

    <?php if ($error): ?>
        <p>
            <strong class="text-danger"><?= $error ?></strong>
        </p>
    <?php endif; ?>
    <?php if ($errors): ?>
        <p>
            <pre><?= json_encode($errors, JSON_PRETTY_PRINT) ?></pre>
        </p>
    <?php endif; ?>
    <?php if ($result): ?>
        <?php if ($task === 'orders'): ?>
            <div class="row mt-3">
                <div class="col-sm text-center">
                    Totaal <?= $result['total'] ?> orders gevonden
                </div>
                <div class="col-sm text-center form-inline">
                    Pagina <select class="form-control-sm mx-1"
                                   onchange="window.location.href = window.location.href.replace(/&page=\d+/, '') + '&page=' + this.value">
                        <?php for ($p = 1;$p <= ceil($result['total']/$result['limit']); $p++): ?>
                            <option value="<?=$p?>"<?= $p == $result['page']?' selected':''?>><?=$p?></option>
                        <?php endfor; ?>
                    </select> van <?= ceil($result['total']/$result['limit']) ?>
                </div>
            </div>
            <table class="table mt-3">
                <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Client Name</th>
                    <th scope="col">Client Number</th>
                    <th scope="col">Order Type</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($result['items'] as $order): ?>
                <tr>
                    <th rowspan="2" scope="col"><?= $order['OrderId']['Id'] ?></th>
                    <td><?= $order['client'] ? $order['client']['Address']['Name'] : 'Onbekend' ?></td>
                    <td><?= $order['ClientNumber'] ?></td>
                    <td><?= $order['OrderType'] ?></td>

                </tr>
                <tr>
                    <td colspan="3">
                        <table class="table table-sm">
                            <tbody>
                            <?php foreach ($order['ArticlesSell'] as $article): ?>
                            <tr>
                                <th scope="row">Article Sell</th>
                                <td><?= $article['AmountArticle'] ?></td>
                                <td><?= $article['Description'] ?></td>
                                <td><?= round((float)$article['Price'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach ($order['ArticlesBuy'] as $article): ?>
                            <tr>
                                <th scope="row">Article Buy</th>
                                <td><?= $article['AmountArticle'] ?></td>
                                <td><?= $article['Description'] ?></td>
                                <td><?= round((float)$article['Price'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach ($order['Goods'] as $good): ?>
                            <tr>
                                <th scope="row">Goods</th>
                                <td><?= $good['Barcode'] ?></td>
                                <td><?= $good['Packing']['Name'] ?></td>
                                <td></td>
                            </tr>
                                <?php foreach (array_filter($order['traces'], function ($trace) use ($good) {
                                    return $trace['GoodId'] === $good['GoodId']['Id'];
                                }) as $trace): ?>
                                    <tr>
                                        <th></th>
                                        <td>Trace</td>
                                        <td><?= $trace['Description'] ?></td>
                                        <td><?= (new DateTime($trace['Moment']))->format('d-m-Y H:i:s') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <?php foreach ($order['Tasks'] as $item): ?>
                            <tr>
                                <th scope="row">Taak - <?= $item['TaskTypeId']['Id'] == 1 ? 'halen' : 'brengen'?></th>
                                <td><?= $item['GoodDescription'] ?></td>
                                <td><?= $item['Address']['Name'] ?></td>
                                <td><?= $item['Address']['Place'] ?></td>
                            </tr>
                            <?php endforeach; ?>
<!--                            <tr><td colspan="4">--><?php //var_dump($order['Tasks']) ?><!--</td></tr>-->
                            </tbody>
                        </table>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
            <pre><?php var_dump($result['items']) ?></pre>
            </p>
        <?php else: ?>
            <p>
            <pre><?php var_dump($result) ?></pre>
            </p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($task == 'order_form'): ?>
        <form name="order_form" method="post" action="index.php" class="mt-4">
            <h4>Order</h4>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Contact</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Contact]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Notes</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Notes]" value="" class="form-control"/>
                </div>
            </div>
            <h4>Goederen</h4>
            <small>In dit voorbeeld slechts één product mogelijk, api ondersteund meerdere</small>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Packing</label>
                <div class="col-sm-8">
                    <input type="text" name="order[GoodList][0][Packing]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">BarCode</label>
                <div class="col-sm-8">
                    <input type="text" name="order[GoodList][0][Barcode]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Comments</label>
                <div class="col-sm-8">
                    <input type="text" name="order[GoodList][0][Comments]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Depth</label>
                <div class="col-sm-8">
                    <input type="number" step="0.01" name="order[GoodList][0][Depth]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Height</label>
                <div class="col-sm-8">
                    <input type="number" step="0.01" name="order[GoodList][0][Height]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Width</label>
                <div class="col-sm-8">
                    <input type="number" step="0.01" name="order[GoodList][0][Width]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Parts</label>
                <div class="col-sm-8">
                    <input type="number" step="1" name="order[GoodList][0][Parts]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Volume</label>
                <div class="col-sm-8">
                    <input type="number" step="0.01" name="order[GoodList][0][Volume]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">VolumeWeight</label>
                <div class="col-sm-8">
                    <input type="number" step="0.001" name="order[GoodList][0][VolumeWeight]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">ArticleWeight</label>
                <div class="col-sm-8">
                    <input type="number" step="0.001" name="order[GoodList][0][ArticleWeight]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Weight</label>
                <div class="col-sm-8">
                    <input type="number" step="0.001" name="order[GoodList][0][Weight]" value="" class="form-control"/>
                </div>
            </div>

            <h4>Ophalen</h4>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Instructions</label>
                <div class="col-sm-8">
                    <input type="text" name="order[PickUp][Instructions]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">ReferenceOur</label>
                <div class="col-sm-8">
                    <input type="text" name="order[PickUp][ReferenceOur]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">ReferenceYour</label>
                <div class="col-sm-8">
                    <input type="text" name="order[PickUp][ReferenceYour]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Requested DateTimeBegin</label>
                <div class="col-sm-8">
                    <input type="text" name="order[PickUp][Requested][DateTimeBegin]" value="" placeholder="jjjj-mm-ddThh:mm:ss" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Requested DateTimeBegin</label>
                <div class="col-sm-8">
                    <input type="text" name="order[PickUp][Requested][DateTimeBegin]" value="" placeholder="jjjj-mm-ddThh:mm:ss" class="form-control"/>
                </div>
            </div>
            <h4>Afleveren</h4>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Address Name *</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Address][Name]" required value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Address Premise</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Address][Premise]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Address Street *</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Address][Street]" required value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Address Number</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Address][Number]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Address PostalCode *</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Address][PostalCode]" required value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Address Place *</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Address][Place]" required value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Address Country *</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Address][Country]" required value="Nederland" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Address CountryCode *</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Address][CountryCode]" required value="NL" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">ContactName</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][ContactName]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Instructions</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Instructions]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">ReferenceOur</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][ReferenceOur]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">ReferenceYour</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][ReferenceYour]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Connectivity Phone</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Connectivity][Phone]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Connectivity Mobile</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Connectivity][Mobile]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Connectivity Email</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Connectivity][Email]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Connectivity Web</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Connectivity][Web]" value="" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Requested DateTimeBegin</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Requested][DateTimeBegin]" value="" placeholder="jjjj-mm-ddThh:mm:ss" class="form-control"/>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-4 col-form-label">Requested DateTimeBegin</label>
                <div class="col-sm-8">
                    <input type="text" name="order[Delivery][Requested][DateTimeBegin]" value="" placeholder="jjjj-mm-ddThh:mm:ss" class="form-control"/>
                </div>
            </div>
            <p>
                <button type="submit" class="btn btn-success">Verzenden</button>
            </p>
            <input type="hidden" name="task" value="create_order"/>
        </form>
    <?php endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
        integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49"
        crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"
        integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy"
        crossorigin="anonymous"></script>
</body>
</html>
