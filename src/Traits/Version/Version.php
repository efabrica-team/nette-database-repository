<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Version;

use DateTimeInterface;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Nette\Utils\Json;

class Version extends Entity
{
    public ?int $id = null;

    /** @CreatedAt */
    public DateTimeInterface $created_at;

    public string $foreign_id;

    public string $foreign_table;

    /** @CreatedBy */
    public ?int $user_id = null;

    public ?string $old_data = null;

    public ?string $new_data = null;

    public ?string $flag = null;

    public ?string $transaction_id = null;

    public ?int $linked_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->created_at;
    }

    public function getForeignId(): string
    {
        return $this->foreign_id;
    }

    public function setForeignId(string $foreign_id): self
    {
        $this->foreign_id = $foreign_id;
        return $this;
    }

    public function getForeignTable(): string
    {
        return $this->foreign_table;
    }

    public function setForeignTable(string $foreign_table): self
    {
        $this->foreign_table = $foreign_table;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getOldData(): ?array
    {
        return $this->old_data === null ? null : Json::decode($this->old_data, Json::FORCE_ARRAY);
    }

    public function setOldData(?array $old_data): self
    {
        $this->old_data = $old_data === null ? null : Json::encode($old_data);
        return $this;
    }

    public function getNewData(): ?array
    {
        return $this->new_data === null ? null : Json::decode($this->new_data, Json::FORCE_ARRAY);
    }

    public function setNewData(?array $new_data): self
    {
        $this->new_data = $new_data === null ? null : Json::encode($new_data);
        return $this;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function setFlag(?string $flag): self
    {
        $this->flag = $flag;
        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transaction_id;
    }

    public function setTransactionId(?string $transaction_id): self
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    public function getLinkedId(): ?int
    {
        return $this->linked_id;
    }

    public function setLinkedId(?int $linked_id): self
    {
        $this->linked_id = $linked_id;
        return $this;
    }

    public function getLinked(bool $cached = true): ?self
    {
        return $this->relToOne($cached, VersionRepository::class, $this->linked_id);
    }

    public function setLinked(?self $linked): self
    {
        if ($linked === null) {
            $this->linked_id = null;
            return $this;
        }
        $this->linked_id = $linked->getId();
        return $this;
    }
}
