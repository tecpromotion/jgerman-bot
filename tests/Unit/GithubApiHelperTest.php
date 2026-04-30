<?php

declare(strict_types=1);

namespace joomlagerman\Tests\Unit;

use joomlagerman\Helper\GithubApiHelper;
use PHPUnit\Framework\TestCase;

final class GithubApiHelperTest extends TestCase
{
	public function testPickLatestTagSelectsHighestPatchOnBranch(): void
	{
		$tags = ['6.1.5', '6.1.10', '6.2.0', '5.2.0'];

		self::assertSame('6.1.10', GithubApiHelper::pickLatestTag($tags, '6.1'));
	}

	public function testPickLatestTagReturnsNullWhenNoMatch(): void
	{
		$tags = ['5.2.0', '6.0.0'];

		self::assertNull(GithubApiHelper::pickLatestTag($tags, '6.1'));
	}

	public function testPickLatestTagIgnoresOtherBranches(): void
	{
		$tags = ['6.0.0', '6.1.0', '6.1.5', '6.1.2'];

		self::assertSame('6.1.5', GithubApiHelper::pickLatestTag($tags, '6.1'));
	}

	public function testPickLatestTagSkipsMalformedTags(): void
	{
		$tags = ['nightly', '6', '6.1.0', '6.1.1'];

		self::assertSame('6.1.1', GithubApiHelper::pickLatestTag($tags, '6.1'));
	}
}
