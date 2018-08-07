<?php

namespace core;

use Codeception\Example;
use Codeception\Util\Stub;
use PublishPress\EDD_License\Core\Services;
use UnitTester;

class ServicesCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests-backup
    public function tryToConstructWithValidConfigParamAndCheckIfConfigWasSet(UnitTester $I)
    {
        $config = Stub::make('\\PublishPress\\EDD_License\\Core\\ServicesConfig');

        $service = new class($config) extends Services
        {
            public function getConfig()
            {
                return $this->config;
            }
        };

        $I->assertSame($config, $service->getConfig());
    }

    /**
     * @example ["API_URL", "apiUrl", "http://example.com"]
     * @example ["LICENSE_KEY", "licenseKey", "0101010010101010101010"]
     * @example ["LICENSE_STATUS", "licenseStatus", "Invalid"]
     * @example ["PLUGIN_VERSION", "pluginVersion", "1.0.15"]
     * @example ["EDD_ITEM_ID", "eddItemId", "1251"]
     * @example ["PLUGIN_AUTHOR", "pluginAuthor", "PublishPress"]
     * @example ["PLUGIN_FILE", "pluginFile", "publishpress/publishpress.php"]
     */
    public function tryToRegisterWithValidContainerAndCheckIfConstantsWereSetInTheContainer(UnitTester $I, Example $example)
    {
        $const           = $example[0];
        $configAttribute = $example[1];
        $expected        = $example[2];

        $config = Stub::make(
            '\\PublishPress\\EDD_License\\Core\\ServicesConfig',
            [
                $configAttribute => $expected,
            ]
        );

        $services = Stub::make(
            '\\PublishPress\\EDD_License\\Core\\Services',
            [
                'config' => $config,
            ]
        );

        $container = Stub::make(
            '\\PublishPress\\EDD_License\\Core\\Container',
            []
        );

        $services->register($container);

        $I->assertArrayHasKey($const, $container);
        $I->assertEquals($expected, $container[$const]);
    }

    public function tryToRegisterWithValidContainerAndCheckIfUpdaterWasSetInTheContainer(UnitTester $I)
    {
        $config = Stub::make(
            '\\PublishPress\\EDD_License\\Core\\ServicesConfig',
            [
                'apiUrl'        => 'http://example.com',
                'licenseKey'    => '0101010010101010101010',
                'licenseStatus' => 'Invalid',
                'pluginVersion' => '1.0.15',
                'eddItemId'     => '1251',
                'pluginAuthor'  => 'PublishPress',
                'pluginFile'    => 'publishpress/publishpress.php',
            ]
        );

        $services = Stub::make(
            '\\PublishPress\\EDD_License\\Core\\Services',
            [
                'config' => $config,
            ]
        );

        $container = Stub::make(
            '\\PublishPress\\EDD_License\\Core\\Container',
            []
        );

        $services->register($container);

        $I->assertArrayHasKey('updater', $container);
        $I->assertInstanceOf('\\PublishPress\\EDD_License\\Updater', $container['updater']);
    }
}
