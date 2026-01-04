<?php declare(strict_types = 1);

use Medoo\Medoo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Weather\Ui\Sensor;

require __DIR__ . '/../vendor/autoload.php';

$format = static function($result): string {
  return sprintf("%.02f", $result);
};

$formatTemp = static function($result): string {
  $f = (float)$result * 9 / 5 + 32;
  return sprintf("%.02f°C (%.02f°F)", $result, $f);
};

$sensorMap = [
  "TEMP" => "Temperature",
  "HUMIDITY" => "Humidity",
  "PRESSURE" => "Pressure",
  "VOC" => "VOCs",
];

$db = new Medoo([
  'type' => 'mysql',
  'host' => 'localhost',
  'database' => 'weather',
  'username' => 'weather',
  'password' => 'weather',
]);

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function(Request $request, Response $response, $args) use($db, $format, $formatTemp, $sensorMap) {
  $todayResults = $db->query('SELECT sensor, high, low FROM daily WHERE `date` = CURDATE()')->fetchAll(PDO::FETCH_ASSOC);
  $yesterdayResults = $db->query('SELECT sensor, high, low FROM daily WHERE `date` = CURDATE() - INTERVAL 1 DAY')->fetchAll(PDO::FETCH_ASSOC);

  $today = [];
  $yesterday = [];

  foreach($todayResults as $result) {
    $today[$result['sensor']] = ['high' => $result['high'], 'low' => $result['low']];
  }

  foreach($yesterdayResults as $result) {
    $yesterday[$result['sensor']] = ['high' => $result['high'], 'low' => $result['low']];
  }

  $currentReadings = [];
  foreach(Sensor::members() as $sensor) {
    foreach($sensor->types as $type) {
      $key = $sensor->key() . '_' . $type;
      $currentReadings[$sensor->key()][$type] = $db->query("SELECT reading, timestamp FROM readings WHERE sensor='$key' AND DATE(`timestamp`) = CURDATE() ORDER BY `timestamp` DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }
  }

  $now = date('Y-m-d H:i:s');

  $formatted = '';
  foreach($currentReadings as $location => $types) {
    $locationName = Sensor::memberByKey($location)->name;
    $formatted .= "<tr><td class='location' colspan='7'>{$locationName}</td></tr>";

    foreach($types as $sensor => $result) {
      $fullSensor = $location . '_' . $sensor;
      $f = str_ends_with($sensor, "TEMP") ? $formatTemp : $format;

      $formatted .= "<tr>
        <td>{$sensorMap[$sensor]}</td>
        <td>{$f($result['reading'])}</td>
      ";

      if(array_key_exists($fullSensor, $today)) {
        $formatted .= "
          <td class='high'>{$f($today[$fullSensor]['high'])}</td>
          <td class='low'>{$f($today[$fullSensor]['low'])}</td>
        ";
      } else {
        $formatted .= "
          <td class='high'>{$f("Pending")}</td>
          <td class='low'>{$f("Pending")}</td>
        ";
      }

      if(array_key_exists($fullSensor, $yesterday)) {
        $formatted .= "
          <td class='high'>{$f($yesterday[$fullSensor]['high'])}</td>
          <td class='low'>{$f($yesterday[$fullSensor]['low'])}</td>
        </tr>";
      } else {
        $formatted .= "
          <td class='high'>{$f("Pending")}</td>
          <td class='low'>{$f("Pending")}</td>
        ";
      }

      $formatted .= "<td class='timestamp'>{$result['timestamp']}</td></tr>";
    }
  }

  $output = <<<DOC
<!DOCTYPE html>

<html lang="en">
  <head>
    <title>Weather</title>
    <meta http-equiv="refresh" content="30">
    <style>
      body {
        font-family: Arial, Helvetica, sans-serif;
      }

      .location {
        padding: 0.3em;
        text-align: center;
      }

      td {
        padding-left: 0.5em;
        padding-right: 0.5em;
      }

      thead td {
        border-bottom: 1px solid black;
        font-weight: bold;
      }

      tr:nth-child(even) {
        background-color: #f2f2f2;
      }

      td:not(:first-child,:last-child) {
        text-align: right;
      }

      thead td.high {
        background-color: #f09090;
      }

      thead td.low {
        background-color: #9090f0;
      }

      tbody td.high {
        background-color: #ffe0e0;
      }

      tbody tr:nth-child(even) td.high {
        background-color: #ffd0d0;
      }

      tbody td.low {
        background-color: #e0e0ff;
      }

      tbody tr:nth-child(even) td.low {
        background-color: #d0d0ff;
      }

      .last-refresh {
        color: grey;
      }
    </style>
  </head>
  <body>
    <table>
      <thead>
        <tr>
          <td>Sensor</td>
          <td>Current</td>
          <td class="high">Today's High</td>
          <td class="low">Today's Low</td>
          <td class="high">Yesterday's High</td>
          <td class="low">Yesterday's Low</td>
          <td>Updated</td>
        </tr>
      </thead>
      <tbody>
        {$formatted}
      </tbody>
    </table>
    <p class="last-refresh">Last refresh: {$now}</p>
  </body>
</html>
DOC;

  $response->getBody()->write($output);
  return $response;
});

$app->run();
