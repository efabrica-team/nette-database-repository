<?php

namespace Efabrica\NetteRepository\Efabrica\Bridge;

use Efabrica\NetteRepository\Bridge\EfabricaNetteRepositoryExtension;
use Efabrica\NetteRepository\Efabrica\Traits\Account\AccountEventSubscriber;
use Efabrica\NetteRepository\Efabrica\Traits\AES\AESEventSubscriber;
use Efabrica\NetteRepository\Efabrica\Traits\Owner\OwnerEventSubscriber;
use Efabrica\NetteRepository\Efabrica\Traits\Version\VersionEventSubscriber;
use Efabrica\NetteRepository\Efabrica\Traits\Version\VersionRepository;

class EfabricaCompanyRepositoryExtension extends EfabricaNetteRepositoryExtension
{
    public function loadConfiguration(): void
    {
        parent::loadConfiguration();
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('userOwnedEventSubscriber'))->setFactory(AccountEventSubscriber::class);
        $builder->addDefinition($this->prefix('aesEventSubscriber'))->setFactory(AESEventSubscriber::class);
        $builder->addDefinition($this->prefix('ownerEventSubscriber'))->setFactory(OwnerEventSubscriber::class);
        $builder->addDefinition($this->prefix('versionEventSubscriber'))->setFactory(VersionEventSubscriber::class);
        $builder->addDefinition('versionRepository')->setFactory(VersionRepository::class);
    }

    public function getDefaultIgnoreTables(): array
    {
        return parent::getDefaultIgnoreTables() + [
                'versions' => true,
                'dashboard_stats' => true,
            ];
    }
}
