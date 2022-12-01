<?php

$config = require 'vendor/holonet/hdev/.php-cs-fixer.dist.php';
$config->setFinder(PhpCsFixer\Finder::create()
	->exclude('vendor')
	->in(__DIR__)
);

return $config;
