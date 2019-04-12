<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\EventSourcing\Testing;

use Astrocasts\Stocker\EventSourcing\EventEnvelope;
use Prooph\EventStoreClient\ExpectedVersion;
use Tests\TestCase;

class Scenario
{
    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @var string
     */
    private $aggregateRootClass;

    /**
     * @var object
     */
    private $aggregateRootInstance;

    /**
     * @var string
     */
    private $aggregateId;

    public function __construct(
        TestCase $testCase,
        string $aggregateRootClass,
        string $aggregateId = '1'
    ) {
        $this->testCase = $testCase;
        $this->aggregateRootClass = $aggregateRootClass;
        $this->aggregateId = $aggregateId;
    }

    public function withAggregateId(string $aggregateId): self
    {
        $this->aggregateId = $aggregateId;

        return $this;
    }

    public function given(?array $givens): self
    {
        if (null === $givens) {
            return $this;
        }

        $messages = [];

        $playhead = ExpectedVersion::EMPTY_STREAM;

        foreach ($givens as $event) {
            ++$playhead;
            $messages[] = EventEnvelope::fromEvent($event, $playhead);
        }

        $this->aggregateRootInstance = call_user_func([
            $this->aggregateRootClass,
            'reconstituteFromHistory'
        ], $messages);

        return $this;
    }

    public function when(callable $when): self
    {
        if (null === $this->aggregateRootInstance) {
            $this->aggregateRootInstance = $when();
            $this->testCase->assertInstanceOf(
                $this->aggregateRootClass,
                $this->aggregateRootInstance
            );
        } else {
            $when($this->aggregateRootInstance);
        }

        return $this;
    }

    public function then(array $thens): self
    {
        $this->testCase->assertEquals($thens, $this->getEvents());

        return $this;
    }

    private function getEvents(): array
    {
        return array_map(function (EventEnvelope $eventEnvelope) {
            return $eventEnvelope->event();
        }, $this->aggregateRootInstance->popRecordedEvents());
    }
}
