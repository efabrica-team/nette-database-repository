<?php

namespace Efabrica\NetteDatabaseRepository\Helpers;

use ReflectionClass;
use ReflectionMethod;

final class HookIgnore
{
    private ?string $traitClass;

    private ?string $hookType;

    private ?string $hookName;

    public function __construct(string $traitClass = null, string $hookType = null, string $hookName = null)
    {
        $this->traitClass = $traitClass;
        $this->hookType = $hookType;
        $this->hookName = $hookName;
    }

    public function isCallableIgnored(ReflectionClass $repositoryReflection, ReflectionMethod $hookReflection): bool
    {
        if ($this->traitClass === null && $this->hookType === null && $this->hookName === null) {
            return true;
        }

        $hookTraitClass = null;
        foreach ($repositoryReflection->getTraits() as $repositoryTraitReflection) {
            if ($hookReflection->getFileName() === $repositoryTraitReflection->getFileName()) {
                $hookTraitClass = $repositoryTraitReflection->getName();
                break;
            }
        }

        if ($this->traitClass !== null) {
            $classIgnored = $this->traitClass === $hookTraitClass;
            $nameIgnored = true;

            if ($this->hookType !== null) {
                $typeIgnored = str_starts_with($hookReflection->getName(), $this->hookType);
                if ($this->hookName !== null) {
                    $nameIgnored = $this->hookName === $hookReflection->getName();
                }
                return $classIgnored && $typeIgnored && $nameIgnored;
            }

            if ($this->hookName !== null) {
                $nameIgnored = $this->hookName === $hookReflection->getName();
            }

            return $classIgnored && $nameIgnored;
        }

        if ($this->hookType !== null) {
            $typeIgnored = str_starts_with($hookReflection->getName(), $this->hookType);
            $nameIgnored = true;

            if ($this->hookName !== null) {
                $nameIgnored = $this->hookName === $hookReflection->getName();
            }

            return $typeIgnored && $nameIgnored;
        }

        if ($this->hookName !== null) {
            return $this->hookName === $hookReflection->getName();
        }

        return false;
    }
}
