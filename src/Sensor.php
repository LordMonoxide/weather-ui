<?php declare(strict_types = 1);

namespace Weather\Ui;

use Eloquent\Enumeration\AbstractMultiton;

class Sensor extends AbstractMultiton {
  protected static function initializeMembers(): void {
    new Sensor('UP', 'TEMP', 'PRESSURE', 'HUMIDITY', 'VOC');
    new Sensor('DOWN', 'TEMP', 'PRESSURE', 'HUMIDITY', 'VOC');
    new Sensor('FRONT', 'TEMP', 'PRESSURE', 'HUMIDITY', 'VOC');
    new Sensor('BACK', 'TEMP');
    new Sensor('DECK', 'TEMP');
    new Sensor('POOL', 'TEMP');
    new Sensor('GARAGE', 'TEMP', 'PRESSURE', 'HUMIDITY', 'VOC');
  }

  public array $types;

  public function __construct(string $key, string... $types) {
    parent::__construct($key);
    $this->types = $types;
  }
}