<?php

namespace Tests\Extensions;

use Nette\Utils\FileSystem;
use PHPUnit\Runner\AfterLastTestHook;

class RemoveTempFolderAfterTests implements AfterLastTestHook
{
    public function executeAfterLastTest(): void
    {
        FileSystem::delete(__DIR__ . '/../../temp');
    }
}
