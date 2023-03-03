<?php

namespace Tests\Unit\Helpers;

use Efabrica\NetteDatabaseRepository\Helpers\HookIgnore;
use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Examples\Behaviors\IpsumBehavior;
use Examples\Behaviors\LoremBehavior;
use ReflectionClass;
use Tests\TestCase;

class HookIgnoreTest extends TestCase
{
    public function test_can_determine_if_should_be_ignored(): void
    {
        $repositoryReflection = new ReflectionClass($this->container->createInstance(HookIgnoreRepository::class));

        $hookignore = new HookIgnore();
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdIpsum')));

        $hookignore = new HookIgnore(LoremBehavior::class);
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstLorem')));
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondLorem')));
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdIpsum')));

        $hookignore = new HookIgnore(null, 'defaultConditions');
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstLorem')));
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdLorem')));
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstIpsum')));
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdIpsum')));

        $hookignore = new HookIgnore(null, null, 'defaultConditionsApplyFirstLorem');
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdIpsum')));

        $hookignore = new HookIgnore(LoremBehavior::class, 'defaultConditions');
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstLorem')));
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdIpsum')));

        $hookignore = new HookIgnore(LoremBehavior::class, null, 'defaultConditionsApplyFirstLorem');
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdIpsum')));

        $hookignore = new HookIgnore(LoremBehavior::class, 'defaultConditions', 'defaultConditionsApplyFirstLorem');
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdIpsum')));

        $hookignore = new HookIgnore(null, 'defaultConditions', 'defaultConditionsApplyFirstLorem');
        $this->assertTrue($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdLorem')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplyFirstIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('defaultConditionsApplySecondIpsum')));
        $this->assertFalse($hookignore->isCallableIgnored($repositoryReflection, $repositoryReflection->getMethod('beforeSelectApplyThirdIpsum')));
    }
}

class HookIgnoreRepository extends Repository
{
    use LoremBehavior;
    use IpsumBehavior;

    public function getTableName(): string
    {
        return '';
    }
}
