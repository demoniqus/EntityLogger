<?php


class BaseEntityLogger implements LoggerInterface
{
//region SECTION: Fields

//endregion Fields

//region SECTION: Constructor

//endregion Constructor

//region SECTION: Protected

//endregion Protected

//region SECTION: Public
    public function onFlush(\Doctrine\ORM\Event\OnFlushEventArgs $args): void
    {

    }

    public function postPersist(\Doctrine\Common\EventArgs $args): void
    {

    }
//endregion Public

//region SECTION: Getters/Setters

//endregion Getters/Setters
}