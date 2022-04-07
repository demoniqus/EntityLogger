<?php

namespace Demoniqus\EntityLogger\Logger;


use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs as OnFlushEventArgsAlias;

interface LoggerInterface
{
//region SECTION:Public

//endregion Public
//region SECTION: Getters/Setters
    public function onFlush(OnFlushEventArgsAlias $args): void;

    public function postPersist(EventArgs $args): void;
//endregion Getters/Setters
}