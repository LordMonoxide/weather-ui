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

$app->get('/', function(Request $request, Response $response, $args) use($db, $format) {
  $today = $db->query('SELECT sensor, MIN(reading) min, MAX(reading) max FROM readings WHERE DATE(`timestamp`) = CURDATE() GROUP BY sensor')->fetchAll(PDO::FETCH_ASSOC);
  $yesterdayResults = $db->query('SELECT sensor, MIN(reading) min, MAX(reading) max FROM readings WHERE DATE(`timestamp`) = CURDATE() - INTERVAL 1 DAY GROUP BY sensor')->fetchAll(PDO::FETCH_ASSOC);

  $yesterday = [];
  foreach($yesterdayResults as $result) {
    $yesterday[$result['sensor']] = $result;
  }

  $currentReadings = [];
  foreach(Sensor::members() as $sensor) {
    foreach($sensor->types as $type) {
      $key = $sensor->key() . '_' . $type;
      $currentReadings[$key] = $db->query("SELECT reading FROM readings WHERE sensor='$key' AND DATE(`timestamp`) = CURDATE() ORDER BY `timestamp` DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }
  }

  $formatted = '';
  foreach($today as $result) {
    if($currentReadings[$result['sensor']]) {
      $current = $currentReadings[$result['sensor']]['reading'];
    } else {
      $current = 'No reading';
    }

    $formatted .= "<tr>
      <td>{$result['sensor']}</td>
      <td>{$format($current)}</td>
      <td class='high'>{$format($result['max'])}</td>
      <td class='low'>{$format($result['min'])}</td>
      <td class='high'>{$format($yesterday[$result['sensor']]['max'])}</td>
      <td class='low'>{$format($yesterday[$result['sensor']]['min'])}</td>
    </tr>";
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
    
      td {
        padding-left: 0.5em;
        padding-right: 0.5em;
      }
      
      thead td {
        border-bottom: 1px solid black;
      }
      
      tr:nth-child(even) {
        background-color: #f2f2f2;
      }
      
      td:not(:first-child) {
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
    </style>
  </head>
  <body>
    <table>
      <thead>
        <tr>
          <td><strong>Sensor</strong></td>
          <td><strong>Current</strong></td>
          <td class="high"><strong>Today's High</strong></td>
          <td class="low"><strong>Today's Low</strong></td>
          <td class="high"><strong>Yesterday's High</strong></td>
          <td class="low"><strong>Yesterday's Low</strong></td>
        </tr>
      </thead>
      <tbody>
        {$formatted}
      </tbody>
    </table>
  </body>
</html>
DOC;

  $response->getBody()->write($output);
  return $response;
});

$app->run();
