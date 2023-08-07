<?php

namespace Efabrica\NetteRepository\Traits\Publish;

use Efabrica\NetteRepository\Traits\Filter\FilterBehavior;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior adds default where condition to every query.
 */
class PublishBehavior extends FilterBehavior
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
