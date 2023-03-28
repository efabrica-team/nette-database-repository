<?php

namespace Efabrica\NetteDatabaseRepository\Enums;

class HookType
{
    public const DEFAULT_CONDITIONS = 'defaultConditions';
    public const BEFORE_SELECT = 'beforeSelect';
    public const AFTER_SELECT = 'afterSelect';
    public const BEFORE_INSERT = 'beforeInsert';
    public const AFTER_INSERT = 'afterInsert';
    public const BEFORE_UPDATE = 'beforeUpdate';
    public const AFTER_UPDATE = 'afterUpdate';
    public const BEFORE_SOFT_DELETE = 'beforeSoftDelete';
    public const AFTER_SOFT_DELETE = 'afterSoftDelete';
    public const BEFORE_RESTORE = 'beforeRestore';
    public const AFTER_RESTORE = 'afterRestore';
    public const BEFORE_DELETE = 'beforeDelete';
    public const AFTER_DELETE = 'afterDelete';
}
