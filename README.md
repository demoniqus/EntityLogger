#Логирование сущностей

`LogRecord` - запись об изменениях в сущности

`BaseEntityLogger` - базовый логгер. Может быть переопределен для создания собственной логики логирования.  

Для связи с событиями Doctrine необходимо зарегистрировать сервис и указать класс, хранящий информацию об изменениях  
&nbsp;&nbsp;`services:`  
&nbsp;&nbsp;&nbsp;&nbsp;`Demoniqus\EntityLogger\Logger\BaseEntityLogger:`  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`arguments:`  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`$logRecordClass: 'Demoniqus\EntityLogger\Entity\LogRecord'`  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`tags:`  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`- { name: doctrine.event_listener, event: postPersist }`  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`- { name: doctrine.event_listener, event: onFlush }`  

Логируемый класс помечается аннотацией `@Demoniqus\EntityLogger\Annotation\Loggable`  
Если требуется исключить некоторый класс из логов, используйте аннотацию `@Demoniqus\EntityLogger\Annotation\Unloggable`  
Для исключения из логирования отдельного поля сущности используйте аннотацию `@Demoniqus\EntityLogger\Annotation\UnloggableField`

По умолчанию из логирования исключены поля `changedAt`, `updatedAt`. если требуется настроить иные поля, можно передать соответствующий аргумент  
&nbsp;&nbsp;`services:`  
&nbsp;&nbsp;&nbsp;&nbsp;`Demoniqus\EntityLogger\Logger\BaseEntityLogger:`  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`arguments:`  
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;`$defaultExcludedProperties: ['someProperty1', 'someProperty2']`  
