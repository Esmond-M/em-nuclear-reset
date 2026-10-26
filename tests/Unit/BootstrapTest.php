<?php

namespace EsmondMcCain\NuclearReset\Tests\Unit;

use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase {
	public function test_plugin_constants_are_defined(): void {
		self::assertSame( '0.1.0', EM_NUCLEAR_RESET_VERSION );
		self::assertFileExists( EM_NUCLEAR_RESET_FILE );
		self::assertDirectoryExists( EM_NUCLEAR_RESET_DIR );
	}
}