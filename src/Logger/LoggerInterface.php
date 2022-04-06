<?php


use Doctrine\ORM\Event\OnFlushEventArgs as OnFlushEventArgsAlias;

interface LoggerInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    public function onFlush(OnFlushEventArgsAlias $args): void;

    public function postPersist(\Doctrine\Common\EventArgs $args): void;
//endregion Getters/Setters
}