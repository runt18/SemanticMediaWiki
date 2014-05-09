<?php

namespace SMW\Test;

use SMW\Tests\MwDBaseUnitTestCase;

use SMW\Tests\Util\PageDeleter;
use SMW\Tests\Util\XmlImportRunner;

use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\StoreFactory;

use Title;

/**
 * Mostly runs regression and integration tests to verify cross-functional
 * interaction with MediaWiki
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
abstract class MwRegressionTestCase extends MwDBaseUnitTestCase {

	protected $expectedAssertions = 0;

	/**
	 * @see MwDBaseUnitTestCase::run
	 */
	public function run( \PHPUnit_Framework_TestResult $result = null ) {

		// Runnning regression tests on postgres will return with something like
		// "pg_query(): Query failed: ERROR:  ... DatabasePostgres.php on line 254"
		// therefore we exclude postgres from this test suite
		$this->markDatabaseToBeExcludedFromTest( 'postgres' );

		parent::run( $result );
	}

	/**
	 * Specifies the source file
	 *
	 * @return string
	 */
	public abstract function getSourceFile();

	/**
	 * Specifies a pool of titles that are expected to be imported
	 *
	 * @return array
	 */
	public abstract function acquirePoolOfTitles();

	/**
	 * Main asserts are implemented by the subclass
	 */
	public abstract function assertDataImport();

	public function testPoolOfTitlesAreNotKnownPriorImport() {
		$this->assertTitleIsNotKnownBeforeImport( $this->acquirePoolOfTitles() );
	}

	/**
	 * @note It is suggested not to add other "test..." unless you want to
	 * re-import the data
	 *
	 * @depends testPoolOfTitlesAreNotKnownPriorImport
	 */
	public function testDataImport() {

		if ( !$this->isUsableUnitTestDatabase() ) {
			$this->markTestSkipped(
				'Database setup did not satisfy the test requirements'
			);
		}

		$importRunner = new XmlImportRunner( $this->getSourceFile() );
		$importRunner->setVerbose( true );

		if ( !$importRunner->run() ) {
			$importRunner->reportFailedImport();
			$this->markTestIncomplete( 'Test was marked as incomplete because the data import failed' );
		}

		$this->assertTitleIsKnownAfterImport( $this->acquirePoolOfTitles() );
		$this->runUpdateJobs( $this->acquirePoolOfTitles() );
		$this->assertDataImport();
		$this->deletePoolOfTitles( $this->acquirePoolOfTitles() );
	}

	protected function runUpdateJobs( $titles ) {
		foreach ( $titles as $title ) {
			$job = new UpdateJob( Title::newFromText( $title ) );
			$job->run();
		}
	}

	private function assertTitleIsNotKnownBeforeImport( $titles ) {
		$this->assertTitleExists( false, $titles );
	}

	private function assertTitleIsKnownAfterImport( $titles ) {
		$this->assertTitleExists( true, $titles );
	}

	private function assertTitleExists( $isExpected, $titles ) {
		foreach ( $titles as $title ) {
			$this->assertEquals(
				$isExpected,
				Title::newFromText( $title )->exists(),
				__METHOD__ . "Assert title {$title}"
			);
		}
	}

	private function deletePoolOfTitles( $titles ) {
		$pageDeleter = new PageDeleter();

		foreach ( $titles as $title ) {
			$pageDeleter->deletePage( Title::newFromText( $title ) );
		}
	}

}
