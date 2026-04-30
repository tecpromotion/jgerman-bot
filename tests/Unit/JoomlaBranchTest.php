<?php

declare(strict_types=1);

namespace joomlagerman\Tests\Unit;

use joomlagerman\Enum\JoomlaBranch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class JoomlaBranchTest extends TestCase
{
	#[DataProvider('branchLabelCases')]
	public function testLabelFor(string $branch, string $expected): void
	{
		self::assertSame($expected, JoomlaBranch::labelFor($branch));
	}

	/**
	 * @return  iterable<string, array{string, string}>
	 */
	public static function branchLabelCases(): iterable
	{
		// Historical multi-digit minors (Joomla 3 + 4 had 10+ minor releases).
		yield 'historical 3.10' => ['3.10-dev', 'Joomla! 3.10'];
		yield 'historical 4.10' => ['4.10-dev', 'Joomla! 4.10'];
		// Joomla 5+ caps minors at .4 before bumping the major.
		yield 'five-four dev'   => ['5.4-dev', 'Joomla! 5.4'];
		yield 'six-one dev'     => ['6.1-dev', 'Joomla! 6.1'];
		yield 'six-four dev'    => ['6.4-dev', 'Joomla! 6.4'];
		yield 'seven-zero dev'  => ['7.0-dev', 'Joomla! 7.0'];
		yield 'plain'           => ['6.1', 'Joomla! 6.1'];
	}
}
