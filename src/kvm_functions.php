<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common;

const BLOCK_SEPARATOR = "\n\n";

const LIST_SEPARATOR = "\t";

function kvm_serialise(array &$data): string {
	$blocks = array();

	foreach ($data as $key => &$value) {
		if (is_array($value)) {
			$value = array_map(kvm_sanitise_value(...), $value);
		} else {
			$value = kvm_sanitise_value($value);
		}

		$valueToSerialise = $value;
		if (!is_array($valueToSerialise)) {
			$valueToSerialise = array($valueToSerialise);
		}

		$serialisedValue = implode(LIST_SEPARATOR, $valueToSerialise);
		$blocks[] = "{$key}\n{$serialisedValue}";
	}

	return implode(BLOCK_SEPARATOR, $blocks);
}

function kvm_parse(string $raw): array {
	$blocks = explode(BLOCK_SEPARATOR, $raw);
	$data = array();
	foreach ($blocks as $block) {
		list($key, $value) = explode("\n", $block, 2);
		$value = mb_trim($value);
		$value = explode(LIST_SEPARATOR, $value);
		if (count($value) === 1) {
			$value = $value[0];
		}

		$data[$key] = $value;
	}

	return $data;
}

function kvm_sanitise_value(string $value): string {
	$value = str_replace(LIST_SEPARATOR, ' ', $value);
	while (str_contains($value, BLOCK_SEPARATOR)) {
		$value = str_replace(BLOCK_SEPARATOR, "\n", $value);
	}

	return $value;
}

function kvm_append(string $key, string $value, array &$data): void {
	$value = kvm_sanitise_value($value);
	if (isset($data[$key])) {
		if (is_array($data[$key])) {
			$data[$key][] = $value;
		} else {
			$data[$key] = array($data[$key], $value);
		}
	} else {
		$data[$key] = $value;
	}
}

function kvm_match(string $query, array $data): bool {
	$conditions = kvm_parse_query($query);
	foreach ($conditions as $cond) {
		if (count($cond) === 1) {
			// boolean condition
			$cond = array_shift($cond);
			$expected = !str_starts_with($cond, '!');
			$cond = mb_ltrim($cond, '!');
			$actual = !empty($data[$cond]);

			if ($expected !== $actual) {
				return false;
			}
		} elseif (count($cond) === 3) {
			list($key, $operator, $expected) = $cond;

			if (!array_key_exists($key, $data)) {
				return false;
			}

			$actual = $data[$key];
			// @psalm-suppress UnhandledMatchCondition
			$result = match ($operator) {
				'=' => is_array($actual) ? in_array($expected, $actual) : (string)$expected === (string)$actual,
				'>' => $expected < $actual,
				'<' => $expected > $actual,
			};

			if (!$result) {
				return false;
			}
		}
	}

	return true;
}

/**
 * @psalm-return array<array{0:string, 1?:'<'|'='|'>', 2?:string}>
 */
function kvm_parse_query(string $query): array {
	$parsed = array();

	$query = explode('/', $query);
	foreach ($query as $condition) {
		$cond = preg_split('/([=><])/', $condition, 3, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);
		if (count($cond) !== 1 && count($cond) !== 3) {
			raise("Condition '{$condition}' is not valid. Must be either a boolean operator or comparison (=><)'");
		}

		$parsed[] = $cond;
	}

	return $parsed;
}
