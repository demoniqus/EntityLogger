<?php

namespace Demoniqus\EntityLogger\Entity;

interface LogRecordInterface
{
//region SECTION:Public
    const CREATE_OPERATION = 'create';
    const UPDATE_OPERATION = 'update';
    const DELETE_OPERATION = 'delete';
//endregion Public
//region SECTION: Getters/Setters
    function getId();

    function getEntityClass(): string;

    function getEntityId(): int;

    function getOperation(): string;

    function getOldValue(): ?array;

    function getNewValue(): ?array;

    function setEntityId(int $entityId): LogRecordInterface;

    function setOperation(string $operation): LogRecordInterface;

    function setOldValue(?array $oldValue): LogRecordInterface;

    function setNewValue(?array $newValue): LogRecordInterface;

    function setEntityClass(string $entityClass): LogRecordInterface;

    function getChangedAt(): \DateTime;
//endregion Getters/Setters
}