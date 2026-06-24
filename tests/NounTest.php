<?php
/**
 * This file is part of the hdev common library package
 * (c) Matthias Lantsch.
 *
 * @license http://www.wtfpl.net/ Do what the fuck you want Public License
 * @author  Matthias Lantsch <matthias.lantsch@bluewin.ch>
 */

namespace holonet\common\tests;

use holonet\common\Noun;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Noun::class)]
class NounTest extends TestCase {
	/**
	 * @return array<string, array{string, string}>
	 */
	public static function nounProvider(): array {
		return array(
			'regular' => array('book', 'books'),
			'sibilant' => array('bus', 'buses'),
			'y after consonant' => array('city', 'cities'),
			'f ending' => array('leaf', 'leaves'),
			'irregular' => array('child', 'children'),
			'mouse' => array('mouse', 'mice'),
			'louse' => array('louse', 'lice'),
			'quiz' => array('quiz', 'quizzes'),
			'matrix' => array('matrix', 'matrices'),
		);
	}

	#[DataProvider('nounProvider')]
	public function test_pluralise(string $singular, string $plural): void {
		$this->assertSame($plural, Noun::pluralise($singular));
	}

	#[DataProvider('nounProvider')]
	public function test_singularise(string $singular, string $plural): void {
		$this->assertSame($singular, Noun::singularise($plural));
	}

	public function test_uncountable_nouns_are_left_alone(): void {
		$this->assertSame('sheep', Noun::pluralise('sheep'));
		$this->assertSame('information', Noun::pluralise('information'));
		$this->assertSame('series', Noun::singularise('series'));
	}
}
