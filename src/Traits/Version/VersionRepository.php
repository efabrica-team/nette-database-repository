<?php

namespace Efabrica\NetteRepository\Traits\Version;

use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryDependencies;

/**
 * @extends Repository<Version,Query<Version>>
 */
abstract class VersionRepository extends Repository
{
    public function __construct(RepositoryDependencies $deps, string $tableName = 'versions')
    {
        parent::__construct($tableName, Version::class, Query::class, $deps);
    }
}
