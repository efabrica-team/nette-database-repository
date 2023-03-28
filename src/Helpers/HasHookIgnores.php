<?php

namespace Efabrica\NetteDatabaseRepository\Helpers;

trait HasHookIgnores
{
    /**
     * @var HookIgnore[]
     */
    protected array $hookIgnores = [];

    public function getHookIgnores(): array
    {
        return $this->hookIgnores;
    }

    /**
     * @return static
     */
    public function importHookIgnores(array $hookIgnores)
    {
        $this->hookIgnores = array_merge($this->hookIgnores, $hookIgnores);
        return $this;
    }

    /**
     * @return static
     */
    public function resetHookIgnores()
    {
        $this->hookIgnores = [];
        return $this;
    }

    /**
     * @return static
     */
    public function ignoreHook(string $hookName)
    {
        return $this->ignoreBehavior(null, null, $hookName);
    }

    /**
     * @return static
     */
    public function ignoreHookType(string $hookType, string $hookName = null)
    {
        return $this->ignoreBehavior(null, $hookType, $hookName);
    }

    /**
     * @return static
     */
    public function ignoreBehavior(?string $traitName, string $hookType = null, string $hookName = null)
    {
        $this->hookIgnores[] = new HookIgnore($traitName, $hookType, $hookName);
        return $this;
    }

    /**
     * @return static
     */
    public function ignoreHooks()
    {
        $this->hookIgnores[] = new HookIgnore();
        return $this;
    }
}
