<?php
namespace PHPUnit\Runner\CleverAndSmart;

use PHPUnit\Framework\Warning;
use PHPUnit\Runner\CleverAndSmart\Storage\StorageInterface;
use PHPUnit\Framework\TestListener as TestListenerInterface;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\AssertionFailedError;
use Exception;
use PHPUnit\Runner\BaseTestRunner as TestRunner;

declare(ticks=1);

class TestListener implements TestListenerInterface
{
    /** @var Run */
    private $run;

    /** @var StorageInterface */
    private $storage;

    /** @var TestCase */
    private $currentTest;

    /** @var bool */
    private $reordered = false;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
        $this->run = new Run();
    }

    public function addError(Test $test, \Throwable $t, float $time) : void
    {
        $this->storage->record($this->run, $test, $time, StorageInterface::STATUS_ERROR);
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time) : void
    {
        $this->storage->record($this->run, $test, $time, StorageInterface::STATUS_ERROR);
    }

    public function addWarning(Test $test, Warning $e, float $time): void
    {
        $this->storage->record($this->run, $test, $time, StorageInterface::STATUS_WARNING);
    }

    public function addRiskyTest(Test $test, \Throwable $t, float $time) : void
    {
    }

    public function startTestSuite(TestSuite $suite) : void
    {
        if ($this->reordered) {
            return;
        }
        $this->reordered = true;

        $this->sort($suite);

        register_shutdown_function(array($this, 'onFatalError'));
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, array($this, 'onCancel'));
        }
    }

    private function sort(TestSuite $suite)
    {
        $sorter = new PrioritySorter(
            $this->storage->getRecordings(
                array(
                    StorageInterface::STATUS_ERROR,
                    StorageInterface::STATUS_FAILURE,
                    StorageInterface::STATUS_CANCEL,
                    StorageInterface::STATUS_FATAL_ERROR,
                    StorageInterface::STATUS_SKIPPED,
                    StorageInterface::STATUS_INCOMPLETE,
                    StorageInterface::STATUS_WARNING,
                ),
                false
            ),
            $this->storage->getRecordings(
                array(
                    StorageInterface::STATUS_PASSED,
                )
            )
        );

        echo "Before:\n";
        foreach($suite->tests() as $i => $testsuite){
            /** @var $testsuite TestSuite */
            echo " {$i}:" . $testsuite->getName() . "\n";
            if($testsuite->getName() == 'examples'){
                $examplesBefore = $testsuite;
            }
        }

        $sorter->sort($suite);

        echo "\nAfter:\n";
        foreach($suite->tests() as $i => $testsuite){
            /** @var $testsuite TestSuite */
            echo " {$i}:" . $testsuite->getName() . "\n";
            if($testsuite->getName() == 'examples'){
                $examplesAfter = $testsuite;
            }
        }

        \Kint::dump(
            $this->storage->getRecordings(
                array(
                    StorageInterface::STATUS_ERROR,
                    StorageInterface::STATUS_FAILURE,
                    StorageInterface::STATUS_CANCEL,
                    StorageInterface::STATUS_FATAL_ERROR,
                    StorageInterface::STATUS_SKIPPED,
                    StorageInterface::STATUS_INCOMPLETE,
                    StorageInterface::STATUS_WARNING,
                ),
                false
            )
        );

        //foreach($suite->tests() as $i => $groups){
        //    foreach($groups->getGroupDetails() as $group) {
            foreach($examplesAfter->getGroupDetails() as $group){
                /** @var $group TestSuite[] */
                foreach ($group as $item) {
                    echo " > {$item->getName()}\n";
                    foreach ($item->getGroupDetails() as $j => $innerItem) {
                        echo "  > {$j}\n";
                        /** @var $innerItem TestSuite[] */
                        foreach ($innerItem as $i => $test) {
                            echo "   > {$i}: {$test->getName()}\n";
                            if (Util::getInvisibleProperty($test, 'dependencies', 'hasDependencies')) {
                                $dependencies = Util::getInvisibleProperty($test, 'dependencies', 'getDependencies');
                             #   \Kint::dump($dependencies);
                            }
                        }
                    }
                }
            }
        //}



#exit;
    }

    public function startTest(Test $test) : void
    {
        $this->currentTest = $test;
    }

    public function endTest(Test $test, $time) : void
    {
        $this->currentTest = null;
        if ($test instanceof TestCase && $test->getStatus() === TestRunner::STATUS_PASSED) {
            $this->storage->record($this->run, $test, $time, StorageInterface::STATUS_PASSED);
        }
    }

    public function addIncompleteTest(Test $test, \Throwable $t, $time) : void
    {
        $this->storage->record($this->run, $test, $time, StorageInterface::STATUS_INCOMPLETE);
    }

    public function addSkippedTest(Test $test, \Throwable $t, $time) : void
    {
        $this->storage->record($this->run, $test, $time, StorageInterface::STATUS_SKIPPED);
    }

    public function endTestSuite(TestSuite $suite) : void
    {
    }

    public function onFatalError()
    {
        $error = error_get_last();
        if (!$error || $error['type'] !== E_ERROR || !$this->currentTest) {
            return;
        }

        $this->storage->record($this->run, $this->currentTest, 0, StorageInterface::STATUS_FATAL_ERROR);
    }

    public function onCancel()
    {
        if (!$this->currentTest) {
            return;
        }

        $this->storage->record($this->run, $this->currentTest, 0, StorageInterface::STATUS_CANCEL);

        exit(1);
    }
}
