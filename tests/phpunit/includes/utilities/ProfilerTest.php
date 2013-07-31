<?php

namespace SMW\Test;

use SMW\Profiler;

use ReflectionClass;

/**
 * Tests for the Profiler class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\Profiler
 *
 * @ingroup SMW
 *
 * @group SMW
 * @group SMWExtension
 */
class ProfilerTest extends SemanticMediaWikiTestCase {

	/**
	 * Holds original values of MediaWiki configuration settings
	 * @var array
	 */
	private $mwGlobals = array();

	/** Set-up */
	protected function setUp() {
		parent::setUp();

		$this->mwGlobals['wgProfiler'] = $GLOBALS['wgProfiler'];
		$GLOBALS['wgProfiler']['class'] = '\ProfilerStub';
	}

	/** Tear down */
	protected function tearDown() {
		$GLOBALS['wgProfiler'] = $this->mwGlobals['wgProfiler'];
		$this->mwGlobals = array();

		parent::tearDown();
	}

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Profiler';
	}

	/**
	 * @test Profiler::getInstance
	 * @test Profiler::reset
	 *
	 * @since 1.9
	 */
	public function testGetInstance() {

		// Never mind the unset here because setup/tearDown
		// stores the original setting
		unset( $GLOBALS['wgProfiler'] );

		$this->assertEquals( null, Profiler::getInstance() );

		$GLOBALS['wgProfiler']['class'] = '\ProfilerStub';

		Profiler::reset();
		$instance = Profiler::getInstance();

		$this->assertInstanceOf( '\ProfilerStub', $instance );
		$this->assertTrue( $instance === Profiler::getInstance(), 'Failed asserting that the instance is identical' );

	}

	/**
	 * @test Profiler::In
	 * @test Profiler::Out
	 *
	 * @since 1.9
	 */
	public function testInOut() {

		Profiler::reset();

		$this->assertInstanceOf( '\ProfilerStub',  Profiler::In( 'Lala' ) );
		$this->assertInstanceOf( '\ProfilerStub',  Profiler::Out( 'Lala' ) );
		$this->assertInstanceOf( '\ProfilerStub',  Profiler::In( 'Lila', true ) );
		$this->assertInstanceOf( '\ProfilerStub',  Profiler::Out( 'Lila', true ) );
	}
}