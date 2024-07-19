<?php

namespace Core\Alerts\Domain;

use Core\Alerts\Domain\ValueObjects\Category;
use Core\Alerts\Domain\ValueObjects\Moment;
use Core\Alerts\Domain\ValueObjects\Priority;
use Core\Alerts\Domain\ValueObjects\Status;
use Core\Alerts\Domain\ValueObjects\Result;
use Core\Alerts\Domain\ValueObjects\Type;
use DateTime;

class Alert
{
    public function __construct(
        protected ?string $id,
        protected ?string $service_id,
        protected string $external_id,
        protected string $external_type,
        protected Priority $priority,
        protected Status $status,
        protected Result $result,
        protected Category $category,
        protected Type $type,
        protected string $fact,
        protected DateTime $created_at,
        protected DateTime $modified_at,
        protected Moment $moment,
        protected string $closed_criteria,
        protected string $solved_criteria,
        protected ?string $more_info = null
    ) {

    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getServiceId(): ?string
    {
        return $this->service_id;
    }

    public function setServiceId(string $service_id): void
    {
        $this->service_id = $service_id;
    }

    public function getExternalId(): string
    {
        return $this->external_id;
    }

    public function setExternalId(string $external_id): void
    {
        $this->external_id = $external_id;
    }

    public function getExternalType(): string
    {
        return $this->external_type;
    }

    public function setExternalType(string $external_type): void
    {
        $this->external_type = $external_type;
    }

    public function getPriority(): Priority
    {
        return $this->priority;
    }

    public function setPriority(Priority $priority): void
    {
        $this->priority = $priority;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    public function setResult(Result $result): void
    {
        $this->result = $result;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): void
    {
        $this->type = $type;
    }

    public function getFact(): string
    {
        return $this->fact;
    }

    public function setFact(string $fact): void
    {
        $this->fact = $fact;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTime $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getModifiedAt(): DateTime
    {
        return $this->modified_at;
    }

    public function setModifiedAt(DateTime $modified_at): void
    {
        $this->modified_at = $modified_at;
    }

    public function getMoment(): Moment
    {
        return $this->moment;
    }

    public function setMoment(Moment $moment): void
    {
        $this->moment = $moment;
    }

    public function getClosedCriteria(): string
    {
        return $this->closed_criteria;
    }

    public function setClosedCriteria(string $closed_criteria): void
    {
        $this->closed_criteria = $closed_criteria;
    }

    public function getSolvedCriteria(): string
    {
        return $this->solved_criteria;
    }

    public function setSolvedCriteria(string $solved_criteria): void
    {
        $this->solved_criteria = $solved_criteria;
    }

    public function getMoreInfo(): ?string
    {
        return $this->more_info;
    }

    public function setMoreInfo(string $more_info): void
    {
        $this->more_info = $more_info;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'service_id' => $this->getServiceId(),
            'external_id' => $this->getExternalId(),
            'external_type' => $this->getExternalType(),
            'priority' => $this->priority->getValue(),
            'status' => $this->status->getValue(),
            'is_solved' => $this->result->getValue(),
            'category' => $this->category->getValue(),
            'type' => $this->type->getValue(),
            'alert_fact' => $this->getFact(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'modified_at' => $this->getModifiedAt()->format('Y-m-d H:i:s'),
            'moment' => $this->getMoment()->getValue(),
            'closed_criteria' => $this->getClosedCriteria(),
            'solved_criteria' => $this->getSolvedCriteria(),
            'more_info' => $this->getMoreInfo()
        ];
    }
}