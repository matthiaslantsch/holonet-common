<?php

if (!isset($config) || !$config instanceof \holonet\common\config\ConfigRegistry) {
	throw new \InvalidArgumentException('The config parameter must be an instance of \holonet\common\config\ConfigRegistry');
}

return new class($config) extends \holonet\common\di\Container {
	public function make(string $abstract, array $extraParams = array()): object {
		return match ($abstract) {
			'service1' => $this->make_service1(),
			default => parent::make($abstract, $extraParams)
		};
	}
	
	public function make_service1(): holonet\common\tests\di\DiAnonDep {
		return new holonet\common\tests\di\DiAnonDep();
	}
};