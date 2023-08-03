<?php

namespace Efabrica\NetteRepository\Traits\Publish;

use Efabrica\NetteRepository\Traits\DefaultWhere\DefaultWhereBehavior;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class PublishBehavior extends DefaultWhereBehavior
{
    private string $publishedField;

    public function __construct(string $publishedField)
    {
        parent::__construct([$publishedField => true]);
        $this->publishedField = $publishedField;
    }

    public function getPublishedField(): string
    {
        return $this->publishedField;
    }

}
