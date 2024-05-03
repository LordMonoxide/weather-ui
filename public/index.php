<?php declare(strict_types = 1);

use Medoo\Medoo;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

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
  $results = $db->query('SELECT sensor, AVG(reading) avg FROM readings WHERE DATE(`timestamp`) = CURDATE() GROUP BY sensor')->fetchAll(PDO::FETCH_ASSOC);

  $formatted = '';
  foreach($results as $result) {
    $formatted .= "<tr><td>{$result['sensor']}</></td><td>{$result['avg']}</td></tr>";
  }

  $output = <<<DOC
<!DOCTYPE html>

<html lang="en">
  <head>
    <title>Weather</title>
  </head>
  <body>
    <table>
      <thead>
        <tr>
          <td><strong>Sensor</strong></td>
          <td><strong>Today's average</strong></td>
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
