<?php declare(strict_types = 1);

use Medoo\Medoo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Weather\Ui\Sensor;

require __DIR__ . '/../vendor/autoload.php';

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

$app->get('/', function(Request $request, Response $response, $args) use($db) {
  $results = $db->query('SELECT sensor, MIN(reading) min, MAX(reading) max FROM readings WHERE DATE(`timestamp`) = CURDATE() GROUP BY sensor')->fetchAll(PDO::FETCH_ASSOC);

  $currentReadings = [];
  foreach(Sensor::members() as $sensor) {
    foreach($sensor->types as $type) {
      $key = $sensor->key() . '_' . $type;
      $currentReadings[$key] = $db->query("SELECT reading FROM readings WHERE sensor='$key' AND DATE(`timestamp`) = CURDATE() ORDER BY `timestamp` DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }
  }

  $formatted = '';
  foreach($results as $result) {
    if($currentReadings[$result['sensor']]) {
      $current = $currentReadings[$result['sensor']]['reading'];
    } else {
      $current = 'No reading';
    }

    $formatted .= "<tr><td>{$result['sensor']}</></td><td>{$current}</td><td>{$result['max']}</td><td>{$result['min']}</td></tr>";
  }

  $output = <<<DOC
<!DOCTYPE html>

<html lang="en">
  <head>
    <title>Weather</title>
    <meta http-equiv="refresh" content="30">
    <style>
      td {
        padding-right: 1em;
      }
      
      thead td {
        border-bottom: 1px solid black;
      }
      
      tr:nth-child(even) {
        background-color: #f2f2f2;
      }
    </style>
  </head>
  <body>
    <table>
      <thead>
        <tr>
          <td><strong>Sensor</strong></td>
          <td><strong>Current</strong></td>
          <td><strong>Today's High</strong></td>
          <td><strong>Today's Low</strong></td>
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
