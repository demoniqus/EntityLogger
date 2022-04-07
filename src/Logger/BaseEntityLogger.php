<?php

namespace Demoniqus\EntityLogger\Logger;

use Demoniqus\EntityLogger\Annotation\Loggable;
use Demoniqus\EntityLogger\Annotation\Unloggable;
use Demoniqus\EntityLogger\Annotation\UnloggableField;
use Demoniqus\EntityLogger\Entity\LogRecord;
use Demoniqus\EntityLogger\Entity\LogRecordInterface;
use Demoniqus\EntityLogger\Model\EntityStateModelInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs as OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

class BaseEntityLogger implements LoggerInterface
{
//region SECTION: Fields
    private Reader $annotationReader;
    /**
     * @var LogRecordInterface[]
     */
    private array $pendingLogRecordInserts = [];

    private string $logRecordClass = '';

    private array $defaultExcludedProperties = [];
//endregion Fields

//region SECTION: Constructor
    public function __construct(
        Reader $annotationReader,
        string $logRecordClass = LogRecord::class,
        array $defaultExcludedProperties = ['updatedAt', 'changedAt']
    )
    {
        $this->logRecordClass = $logRecordClass;
        $this->annotationReader = $annotationReader;
        $this->defaultExcludedProperties = $defaultExcludedProperties;
    }
//endregion Constructor

//region SECTION: Protected
    /**
     * @param                        $operation
     * @param                        $entity
     * @param UnitOfWork             $uow
     * @param EntityManagerInterface $em
     *
     * @throws \ReflectionException
     */
    protected function log($operation, $entity, $uow, $em)
    {
        $realClass = ClassUtils::getClass($entity);

        if (!$this->isLoggableClass($realClass)) {
            return;
        }

        $diff      = [];
        /** @var array <propertyName, <0 -> oldValue, 1 -> newValue>> $changeSet */
        $changeSet = $uow->getEntityChangeSet($entity);

        $operation = $this->checkForDeleteOperation($operation, $changeSet);
        foreach ($changeSet as $property => $changes) {
            if ($this->isEqualChanges($changes, $realClass, $property)) {
                continue;
            }
            if ($this->isExcludedEntityField($realClass, $property)) {
                continue;
            }

            $diff['old'][$property] = $this->computeOldValue($changes[0]);
            $diff['new'][$property] = $this->computeNewValue($changes[1]);
        }

        if (empty($diff)) {
            return;
        }

        $logRecord = $this->createLogRecord($operation, $entity, $diff);

        $em->persist($logRecord);
        $uow->computeChangeSet($em->getClassMetadata($this->logRecordClass), $logRecord);
    }

    /**
     * @param $operation
     * @param $changeSet
     *
     * @return string
     */
    protected function checkForDeleteOperation($operation, $changeSet): string
    {
        $activeFieldChanged = array_key_exists(EntityStateModelInterface::FIELD_NAME, $changeSet);
        if ($activeFieldChanged && $changeSet[EntityStateModelInterface::FIELD_NAME][1] === EntityStateModelInterface::STATE_DELETED) {
            $operation = LogRecordInterface::DELETE_OPERATION;
        }

        return $operation;
    }

    /**
     * @param $changes
     * @param $realClass
     * @param $property
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function isEqualChanges($changes, $realClass, $property)
    {
        if (
            $changes[0] instanceof \DateTime && $changes[1] instanceof \DateTime
            && $changes[0]->getTimestamp() === $changes[1]->getTimestamp()
        ) {
            return true;
        }
        if (
            is_numeric($changes[0])
            && is_numeric($changes[1])
            && round(floatval($changes[0])) === round(floatval($changes[1]))
        ) {
            return true;
        }
        if ($changes[0] === null && !($changes[1])) {
            return true;
        }
        if ($this->checkUnloggable($realClass, $property)) {
            return true;
        }

        return false;
    }

    /**
     * @param $realClass
     *
     * @param $property
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function isExcludedEntityField($realClass, $property): bool
    {
        if (\in_array($property, $this->defaultExcludedProperties, true)) {
            return true;
        }
        $unloggableField = $this->annotationReader->getPropertyAnnotation(
            new \ReflectionProperty($realClass, $property),
            UnloggableField::class
        );

        return (bool)$unloggableField;
    }

    /**
     * @param $newValue
     *
     * @return mixed
     */
    protected function computeNewValue($newValue)
    {
        return $this->prepareDateTime($this->getPropertyValue($newValue));
    }

    /**
     * @param $oldValue
     *
     * @return mixed
     */
    protected function computeOldValue($oldValue)
    {
        return $this->prepareDateTime($this->getPropertyValue($oldValue));
    }

    /**
     * @param $operation
     * @param $entity
     * @param $diff
     *
     * @return LogRecordInterface
     */
    protected function createLogRecord(string $operation, $entity, array $diff): LogRecordInterface
    {
        $logRecord = new $this->logRecordClass();
        $logRecord->setOperation($operation);
        $logRecord->setOldValue($diff['old']);
        $logRecord->setNewValue($diff['new']);

        $this->setEntityId($operation, $entity, $logRecord);
        $this->setEntityClass($entity, $logRecord);

        return $logRecord;
    }

    /**
     * @param                    $entity
     * @param LogRecordInterface $logRecord
     */
    protected function setEntityClass($entity, LogRecordInterface $logRecord): void
    {
        $logRecord->setEntityClass(ClassUtils::getClass($entity));
    }

    /**
     * @param          $operation
     * @param          $entity
     * @param LogRecordInterface $logRecord
     */
    protected function setEntityId($operation, $entity, LogRecordInterface $logRecord): void
    {
        if ($operation === LogRecordInterface::CREATE_OPERATION) {
            $this->pendingLogRecordInserts[spl_object_hash($entity)] = $logRecord;
        } else {
            $logRecord->setEntityId($entity->getId());
        }
    }
//endregion Protected
//region SECTION: Private
    /**
     * @param string $realClass
     * @return bool
     * @throws \ReflectionException
     */
    private function isLoggableClass(string $realClass): bool
    {
        return !!$this->annotationReader->getClassAnnotation(new \ReflectionClass($realClass), Loggable::class);
    }
    /**
     * @param $realClass
     * @param $property
     *
     * @return bool
     * @throws \ReflectionException
     */
    private function checkUnloggable($realClass, $property): bool
    {
        $unloggable = $this->annotationReader->getPropertyAnnotation(
            new \ReflectionProperty($realClass, $property),
            Unloggable::class
        );

        return (bool)$unloggable;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function getPropertyValue($value)
    {
        if ($this->isIdentifiedObject($value)) {
            $value = $value->getId();
        }
        else if ($value instanceof \ArrayAccess) {
            /**
             * Если поле представляет собой коллекцию связанных идентифицируемых объектов,
             * собираем их идентификаторы
             */
            $collection = [];

            foreach ($value as $item) {
                $collection[] = $this->isIdentifiedObject($item) ?
                    ['id' => $item->getId()] :
                    $item;
            }

            $value = $collection;
        }

        return $value;
    }

    /**
     * Метод проверяет, является ли объект идентифицируемым
     * @param $value
     * @return bool
     */
    private function isIdentifiedObject($value): bool
    {
        return is_object($value) && method_exists($value, 'getId') && $value->getId() !== null;
    }

    /**
     * @param $value
     *
     * @return string
     */
    private function prepareDateTime($value)
    {
        if ($value instanceof \DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        }

        return $value;
    }
//endregion Private

//region SECTION: Public
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em  = $args->getEntityManager();
        $uow = $args->getEntityManager()->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $objectHash => $entity) {
            $this->log(LogRecordInterface::CREATE_OPERATION, $entity, $uow, $em);
        }
        foreach ($uow->getScheduledEntityUpdates() as $objectHash => $entity) {
            $this->log(LogRecordInterface::UPDATE_OPERATION, $entity, $uow, $em);
        }
    }

    /**
     * @param EventArgs $args
     */
    public function postPersist(EventArgs $args): void
    {
        /* @var $em EntityManagerInterface */
        $em         = $args->getEntityManager();
        $uow        = $em->getUnitOfWork();
        $entity     = $args->getEntity();
        $entityHash = spl_object_hash($entity);

        if ($this->pendingLogRecordInserts && array_key_exists($entityHash, $this->pendingLogRecordInserts)) {
            $logRecord = $this->pendingLogRecordInserts[$entityHash];
            foreach ($logRecord->getNewValue() as $property => $newValue) {
                if (is_object($newValue) && method_exists($newValue, 'getId')) {
                    $logRecord->getNewValue()[$property] = $this->computeNewValue($newValue);
                }
            }
            $logRecord->setEntityId($entity->getId());
            $uow->scheduleExtraUpdate(
                $logRecord,
                [
                    'entityId' => [
                        null,
                        $entity->getId(),
                    ],
                ]
            );
            unset($this->pendingLogRecordInserts[$entityHash]);
        }
    }
//endregion Public
}