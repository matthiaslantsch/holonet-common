<?php

if (!isset($config) || !$config instanceof \holonet\common\collection\ConfigRegistry) {
	throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\collection\ConfigRegistry');
}

$initialServices ??= array();

return new class($config, $initialServices) extends \holonet\common\di\Container {
	protected array $aliases = array(
		'container' => 'holonet\\common\\di\\Container',
		'registry' => 'holonet\\common\\collection\\ConfigRegistry',
		'service1' => 'holonet\\common\\tests\\di\\DiAnonDep',
	);
	
	protected array $services = array(
		0 => 'container',
		1 => 'registry',
		2 => 'service1',
	);
	
	protected function instance(string $class, array $params = array()): object {
		return match ($class) {
			'service1' => $this->make_service1($params),
			default => parent::instance($class, $params)
		};
	}
	
	protected function make_service1(array $params): holonet\common\tests\di\DiAnonDep {
		return new holonet\common\tests\di\DiAnonDep();
	}
};