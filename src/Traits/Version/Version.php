<?php

namespace Efabrica\NetteRepository\Traits\Version;

use DateTimeInterface;
use Efabrica\NetteRepository\Model\Entity;

/**
 * @property int $id
 * @property DateTimeInterface $created_at
 * @property string $foreign_id
 * @property string $foreign_table
 * @property int|null $user_id
 * @property string|null $old_data
 * @property string|null $new_data
 * @property string|null $flag
 * @property string|null $transaction_id
 * @property int|null $linked_id
 */
class Version extends Entity
{
    public const ID = 'id';
    public const CREATED_AT = 'created_at';
    public const FOREIGN_ID = 'foreign_id';
    public const FOREIGN_TABLE = 'foreign_table';
    public const USER_ID = 'user_id';
    public const OLD_DATA = 'old_data';
    public const NEW_DATA = 'new_data';
    public const FLAG = 'flag';
    public const TRANSACTION_ID = 'transaction_id';
    public const LINKED_ID = 'linked_id';
}
