<?php declare(strict_types = 1);

namespace Weather\Ui;

use Eloquent\Enumeration\AbstractMultiton;

class Sensor extends AbstractMultiton {
  protected static function initializeMembers(): void {
    new Sensor('UP', 'Upstairs', 'TEMP', 'HUMIDITY', 'VOC');
    new Sensor('DOWN', 'Downstairs', 'TEMP', 'HUMIDITY', 'VOC');
    new Sensor('FRONT', 'Front of house', 'TEMP');
    new Sensor('BACK', 'Back of house', 'TEMP');
    new Sensor('DECK', 'Deck', 'TEMP');
    new Sensor('POOL', 'Pool', 'TEMP');
    new Sensor('GARAGE', 'Garage', 'TEMP', 'PRESSURE', 'HUMIDITY', 'VOC');
  }

  public string $name;
  public array $types;

  public function __construct(string $key, string $name, string... $types) {
    parent::__construct($key);
    $this->name = $name;
    $this->types = $types;
  }
}
