<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

use Nette\Utils\Strings;

trait SlugBehavior
{
    use RepositoryBehavior;

    protected function slugField(): string
    {
        return 'slug';
    }

    protected function stringToWebalize(array $recordData): string
    {
        if (isset($recordData['title'])) {
            return $recordData['title'];
        }

        if (isset($recordData['name'])) {
            return $recordData['name'];
        }

        return implode(' ', $recordData);
    }

    final public function beforeInsertGenerateUuid(array $data): array
    {
        if (!isset($data[$this->slugField()])) {
            $data[$this->slugField()] = Strings::webalize($this->stringToWebalize($data));
        }
        return $data;
    }
}
