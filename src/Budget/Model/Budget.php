<?php

namespace LifeOrganizer\Core\Budget\Model;

use LifeOrganizer\Core\Budget\Event\BudgetCreated;
use LifeOrganizer\Core\Budget\Event\NameChanged;
use LifeOrganizer\Core\Budget\Event\PositionAdded;
use LifeOrganizer\Core\Budget\ValueObject\PositionDetails;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\EventSourcing\AggregateChanged;

class Budget extends AggregateRoot
{
    private $id;
    private $userId;
    private $name;
    private $positions = [];

    public static function createWithData(
        string $id,
        string $name,
        string $userId
    ): self {
        $budget = new self;
        $budget->recordThat(BudgetCreated::occur($id, [
            'id' => $id,
            'name' => $name,
            'userId' => $userId
        ]));

        return $budget;
    }

    public function newName(string $name): void
    {
        if ($this->name === $name) {
            return;
        }

        $this->recordThat(NameChanged::occur($this->id, [
            'name' => $name
        ]));
    }

    public function addPosition(PositionDetails $positionDetails): void
    {
        $this->recordThat(
            PositionAdded::occur(
                $this->id,
                $positionDetails->asArray()
            )
        );
    }

    protected function aggregateId(): string
    {
        return $this->id;
    }

    protected function apply(AggregateChanged $event): void
    {
        switch (get_class($event)) {
            case BudgetCreated::class:
                /** @var BudgetCreated $event */
                $this->id = $event->id();
                $this->name = $event->name();
                $this->userId = $event->userId();
                break;
            case NameChanged::class:
                /** @var NameChanged $event */
                $this->name = $event->name();
                break;
            case PositionAdded::class:
                /** @var PositionAdded $event */
                $budgetPosition = new BudgetPosition(
                    $this->aggregateId(),
                    $event->positionValue(),
                    $event->positionName()
                );
                $this->positions[] = $budgetPosition;
                break;
            default:
                throw new UnsupportedEvent(get_class($event));
        }
    }
}
