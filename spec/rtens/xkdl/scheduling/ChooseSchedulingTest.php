<?php
namespace spec\rtens\xkdl\scheduling;

use rtens\mockster\Mock;
use rtens\mockster\MockFactory;
use rtens\xkdl\scheduler\SchedulerFactory;
use rtens\xkdl\Scheduler;
use rtens\xkdl\web\root\ScheduleResource;
use spec\rtens\xkdl\fixtures\ConfigFixture;
use spec\rtens\xkdl\fixtures\ResourceFixture;
use watoki\scrut\Specification;

/**
 * The user can choose which Scheduler to use when creating a schedule
 *
 * @see Scheduler and its subclasses
 *
 * @property ConfigFixture config <-
 * @property ResourceFixture resource <-
 */
class ChooseSchedulingTest extends Specification {

    protected function background() {
        $this->givenTheScheduler_WithTheName_AndDescription('foo', 'Some Scheduler', 'Does magic');
        $this->givenTheScheduler_WithTheName_AndDescription('bar', 'Another Scheduler', 'Does more magic');
        $this->givenTheScheduler_WithTheName_AndDescription('baz', 'And a third one', 'Maybe too many');
    }

    function testShowOptionsWithDefaultSelected() {
        $this->config->givenTheDefaultSchedulerIs('bar');

        $this->whenIOpenTheSchedule();

        $this->thenThereShouldBe_SchedulerOptions(3);

        $this->thenSchedulerOption_ShouldHaveTheKey(1, 'foo');
        $this->thenSchedulerOption_ShouldHaveTheName(1, 'Some Scheduler');
        $this->thenSchedulerOption_ShouldHaveTheDescription(1, 'Does magic');

        $this->thenSchedulerOption_ShouldHaveTheKey(2, 'bar');
        $this->thenSchedulerOption_ShouldHaveTheKey(3, 'baz');

        $this->thenTheSchedulerOption_ShouldNotBeSelected(1);
        $this->thenTheSchedulerOption_ShouldBeSelected(2);
        $this->thenTheSchedulerOption_ShouldNotBeSelected(3);
    }

    function testChooseScheduler() {
        $this->config->givenTheDefaultSchedulerIs('foo');
        $this->whenICreateAScheduleUsingTheScheduler('baz');
        $this->thenTheScheduler_ShouldBeUsed('baz');
    }

    function testInvalidSchedulerChosen() {
        $this->whenITryToCreateAScheduleUsingTheScheduler('not');
        $this->thenTheErrorShouldOccur('Invalid scheduler key: not');
    }

    ###################### SET-UP ##################

    /** @var SchedulerFactory */
    private $schedulerFactory;

    /** @var array|Mock[] */
    private $schedulers = [];

    /** @var null|\Exception */
    private $caught;

    protected function setUp() {
        parent::setUp();

        $this->schedulerFactory = $this->factory->getInstance(SchedulerFactory::$CLASS);
        $this->schedulerFactory->clear();

        $this->factory->setSingleton(SchedulerFactory::$CLASS, $this->schedulerFactory);

        $this->resource->givenTheResourceIs(ScheduleResource::$CLASS);
    }

    private function givenTheScheduler_WithTheName_AndDescription($key, $name, $description) {
        $schedulerClass = 'Scheduler' . $key;

        $mf = new MockFactory();
        $schedulerMock = $mf->getMock(Scheduler::$CLASS);

        $this->schedulers[$key] = $schedulerMock;
        $this->factory->setSingleton($schedulerClass, $schedulerMock);

        $this->schedulerFactory->set($key, $schedulerClass, $name, $description);
    }

    private function whenIOpenTheSchedule() {
        $this->resource->whenIInvoke('doGet');
    }

    private function whenICreateAScheduleUsingTheScheduler($key) {
        $this->resource->whenIInvoke_With('doPost', [new \DateTime(), new \DateTime(), $key]);
    }

    private function whenITryToCreateAScheduleUsingTheScheduler($key) {
        try {
            $this->whenICreateAScheduleUsingTheScheduler($key);
        } catch (\Exception $e) {
            $this->caught = $e;
        }
    }

    private function thenThereShouldBe_SchedulerOptions($int) {
        $this->resource->then_ShouldHaveTheSize("algorithm", $int);
    }

    private function thenSchedulerOption_ShouldHaveTheKey($pos, $string) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'meta/value', $string);
    }

    private function thenSchedulerOption_ShouldHaveTheName($pos, $string) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'name', $string);
    }

    private function thenSchedulerOption_ShouldHaveTheDescription($pos, $string) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'description', $string);
    }

    private function thenTheSchedulerOption_ShouldBeSelected($pos) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'meta/checked', 'checked');
    }

    private function thenTheSchedulerOption_ShouldNotBeSelected($pos) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'meta/checked', false);
    }

    private function thenOption_ShouldHaveTheField_WithValue($pos, $field, $value) {
        $pos--;
        $this->resource->then_ShouldBe("algorithm/$pos/$field", $value);
    }

    private function thenTheScheduler_ShouldBeUsed($key) {
        $this->assertTrue($this->schedulers[$key]->__mock()->method('createSchedule')->getHistory()->wasCalled());
    }

    private function thenTheErrorShouldOccur($string) {
        $this->assertNotNull($this->caught);
        $this->assertEquals($string, $this->caught->getMessage());
    }

} 