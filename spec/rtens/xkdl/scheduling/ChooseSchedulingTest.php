<?php
namespace spec\rtens\xkdl\scheduling;

use rtens\xkdl\scheduler\SchedulerFactory;
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
        $this->markTestIncomplete();

        $this->config->givenTheDefaultSchedulerIs('foo');
        $this->whenICreateAScheduleUsingTheScheduler('baz');
        $this->thenTheScheduler_ShouldBeUsed('baz');
    }

    function testFallBackToDefaultScheduler() {
        $this->markTestIncomplete();

        $this->config->givenTheDefaultSchedulerIs('foo');
        $this->whenICreateASchedule();
        $this->thenTheScheduler_ShouldBeUsed('foo');
    }

    function testInvalidSchedulerChosen() {
        $this->markTestIncomplete();

        $this->whenITryToCreateAScheduleUsingTheScheduler('not');
        $this->thenTheErrorShouldOccur('Invalid scheduler key: "not"');
    }

    ###################### SET-UP ##################

    /** @var SchedulerFactory */
    private $schedulerFactory;

    protected function setUp() {
        parent::setUp();

        $this->schedulerFactory = new SchedulerFactory();
        $this->schedulerFactory->clear();

        $this->factory->setSingleton(SchedulerFactory::$CLASS, $this->schedulerFactory);

        $this->resource->givenTheResourceIs(ScheduleResource::$CLASS);
    }

    private function givenTheScheduler_WithTheName_AndDescription($key, $name, $description) {
        $this->schedulerFactory->set($key, 'StdClass', $name, $description);
    }

    private function whenIOpenTheSchedule() {
        $this->resource->whenIInvoke('doGet');
    }

    private function thenThereShouldBe_SchedulerOptions($int) {
        $this->resource->then_ShouldHaveTheSize("algorithm", $int);
    }

    private function thenSchedulerOption_ShouldHaveTheKey($pos, $string) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'key', $string);
    }

    private function thenSchedulerOption_ShouldHaveTheName($pos, $string) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'name', $string);
    }

    private function thenSchedulerOption_ShouldHaveTheDescription($pos, $string) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'description', $string);
    }

    private function thenTheSchedulerOption_ShouldBeSelected($pos) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, 'checked', 'checked');
    }

    private function thenTheSchedulerOption_ShouldNotBeSelected($pos) {
        $this->thenOption_ShouldHaveTheField_WithValue($pos, "checked", false);
    }

    private function thenOption_ShouldHaveTheField_WithValue($pos, $field, $value) {
        $pos--;
        $this->resource->then_ShouldBe("algorithm/$pos/$field", $value);
    }

} 