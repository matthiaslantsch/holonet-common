<?php

$config = require 'vendor/holonet/hdev/.php-cs-fixer.dist.php';
$config->setRules([
	'php_unit_test_class_requires_covers' => false
]);
$config->setFinder(PhpCsFixer\Finder::create()
	->exclude('vendor')
	->exclude('tests/data')
	->in(__DIR__)
);

return $config;
