<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Version;

use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Repository\RepositoryDependencies;

/**
 * @extends Repository<Version>
 */
abstract class VersionRepository extends Repository
{
    public function __construct(RepositoryDependencies $deps, string $tableName = 'versions')
    {
        parent::__construct($tableName, Version::class, $deps);
    }
}
