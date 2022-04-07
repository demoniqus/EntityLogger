<?php

namespace Demoniqus\EntityLogger\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="log_record")
 * @ORM\Entity()
 */
class LogRecord implements LogRecordInterface
{
//region SECTION: Fields
    /**
     * @var integer
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $entityClass;
    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $entityId;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $operation;
    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    protected $oldValue;
    /**
     * @var array
     * @ORM\Column(type="json", nullable=false)
     */
    protected $newValue;
    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $changedAt;
//endregion Fields

//region SECTION: Constructor
    /**
     * constructor.
     */
    public function __construct()
    {
        $this->changedAt = new DateTime();
    }
//endregion Constructor

//region SECTION: Getters/Setters
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @return array|null
     */
    public function getOldValue(): ?array
    {
        return $this->oldValue;
    }

    /**
     * @return array|null
     */
    public function getNewValue(): ?array
    {
        return $this->newValue;
    }

    /**
     * @param string $entityClass
     *
     * @return LogRecordInterface
     */
    public function setEntityClass(string $entityClass): LogRecordInterface
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @param int $entityId
     *
     * @return LogRecordInterface
     */
    public function setEntityId(int $entityId): LogRecordInterface
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @param string $operation
     *
     * @return LogRecordInterface
     */
    public function setOperation(string $operation): LogRecordInterface
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * @param array|null $oldValue
     *
     * @return LogRecordInterface
     */
    public function setOldValue(?array $oldValue): LogRecordInterface
    {
        $this->oldValue = $oldValue;

        return $this;
    }

    /**
     * @param array|null $newValue
     *
     * @return LogRecordInterface
     */
    public function setNewValue(?array $newValue): LogRecordInterface
    {
        $this->newValue = $newValue;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getChangedAt(): DateTime
    {
        return $this->changedAt;
    }
//endregion Getters/Setters
}