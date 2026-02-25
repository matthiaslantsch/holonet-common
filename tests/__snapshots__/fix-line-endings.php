<?php

foreach (glob(__DIR__ . '/*.txt') as $file) {
	$content = file_get_contents($file);

	file_put_contents($file, trim($content));
}
