<?php

if (!isset($config) || !$config instanceof \holonet\common\collection\ConfigRegistry) {
	throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\collection\ConfigRegistry');
}

$initialServices ??= array();

return new class($config, $initialServices) extends \holonet\common\di\Container {
	protected array $aliases = array(
		'container' => 'holonet\\common\\di\\Container',
		'registry' => 'holonet\\common\\collection\\ConfigRegistry',
		'service1' => 'holonet\\common\\tests\\di\\holonet_common_tests_DiAnonDep',
	);
	
	protected array $services = array(
		'container',
		'registry',
		'service1',
	);
	
	protected array $contracts = array();
	
	protected array $providers = array();
	
	protected function instantiate(string $class, array $params = array()): object {
		return match ($class) {
			holonet\common\tests\di\holonet_common_tests_DiAnonDep::class => $this->instantiate_holonet_common_tests_di_holonet_common_tests_DiAnonDep($params),
			default => parent::instantiate($class, $params)
		};
	}
	
	protected function instantiate_holonet_common_tests_di_holonet_common_tests_DiAnonDep(array $params): holonet\common\tests\di\holonet_common_tests_DiAnonDep {
		return new holonet\common\tests\di\holonet_common_tests_DiAnonDep();
	}
};