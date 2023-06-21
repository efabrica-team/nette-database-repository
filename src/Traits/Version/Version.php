<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Version;

use DateTimeInterface;
use Efabrica\NetteDatabaseRepository\Model\Entity;

/**
 * @property int $id
 * @property DateTimeInterface $created_at @CreatedAt
 * @property string $foreign_id
 * @property string $foreign_table
 * @property int|null $user_id @CreatedBy
 * @property string|null $old_data
 * @property string|null $new_data
 * @property string|null $flag
 * @property string|null $transaction_id
 * @property int|null $linked_id
 */
class Version extends Entity
{
}
