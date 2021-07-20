<?php

namespace SeoBundle\Tool;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\Version;
use Pimcore\Db\Connection;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Migrations\Migration\InstallMigration;
use Pimcore\Model\User\Permission;

class Install extends AbstractInstaller
{
    /**
     * @var array
     */
    const REQUIRED_PERMISSION = [
        'seo_bundle_remove_property',
        'seo_bundle_add_property',
    ];

    /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    /**
     * @throws AbortMigrationException
     * @throws DBALException
     */
    public function initializeFreshSetup(): void
    {
        $this->installDbStructure();
        $this->installPermissions();
    }

    /**
     * @throws DBALException
     */
    protected function installDbStructure(): void
    {
        /** @var Connection $db */
        $db = \Pimcore\Db::get();
        $db->query(file_get_contents($this->getInstallSourcesPath().'/sql/install.sql'));
    }

    protected function installPermissions(): void
    {
        foreach (self::REQUIRED_PERMISSION as $permission) {
            $definition = Permission\Definition::getByKey($permission);

            if ($definition) {
                $this->output->write(sprintf(
                    '     <comment>WARNING:</comment> Skipping permission "%s" as it already exists',
                    $permission
                ));

                continue;
            }
            Permission\Definition::create($permission);
        }
    }

    protected function getInstallSourcesPath(): string
    {
        return __DIR__.'/../Resources/install';
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    /**
     * {@inheritdoc}
     */
    public function install(): void
    {
        $this->installDbStructure();
        $this->installPermissions();
    }

    public function isInstalled(): bool
    {
        $db = \Pimcore\Db::get();
        $check = $db->fetchOne('SELECT `key` FROM users_permission_definitions where `key` = ?', [self::REQUIRED_PERMISSION[0]]);

        return (bool)$check;
    }
}
