<?php
namespace core;

use UnitTester;
use Codeception\Util\Stub;
use Codeception\Example;

class ServicesConfigCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    /**
     * @example {"apiUrl": "http://example.com/mysite", "licenseKey": "01010101010101001", "licenseStatus": "invalid", "pluginVersion": "1.0.12", "eddItemId": "1234", "pluginAuthor": "PublishPress", "pluginFile": "plublishpress/publishpress.php"}
     */
    public function tryToCallValidWithValidAttributesNotCatchingAnyException(UnitTester $I, Example $example)
    {
        $config = Stub::make(
            '\\PublishPress\\EDD_License\\Core\\ServicesConfig',
            $example
        );

        $current = $config->validate();

        $I->assertTrue($current);
    }

    /**
     * @example {"licenseKey": "01010101010101001", "licenseStatus": "invalid", "pluginVersion": "1.0.12", "eddItemId": "1234", "pluginAuthor": "PublishPress", "pluginFile": "plublishpress/publishpress.php"}
     * @example {"apiUrl": "http://example.com/mysite", "licenseStatus": "invalid", "pluginVersion": "1.0.12", "eddItemId": "1234", "pluginAuthor": "PublishPress", "pluginFile": "plublishpress/publishpress.php"}
     * @example {"apiUrl": "http://example.com/mysite", "licenseKey": "01010101010101001", "pluginVersion": "1.0.12", "eddItemId": "1234", "pluginAuthor": "PublishPress", "pluginFile": "plublishpress/publishpress.php"}
     * @example {"apiUrl": "http://example.com/mysite", "licenseKey": "01010101010101001", "licenseStatus": "invalid", "eddItemId": "1234", "pluginAuthor": "PublishPress", "pluginFile": "plublishpress/publishpress.php"}
     * @example {"apiUrl": "http://example.com/mysite", "licenseKey": "01010101010101001", "licenseStatus": "invalid", "pluginVersion": "1.0.12", "pluginAuthor": "PublishPress", "pluginFile": "plublishpress/publishpress.php"}
     * @example {"apiUrl": "http://example.com/mysite", "licenseKey": "01010101010101001", "licenseStatus": "invalid", "pluginVersion": "1.0.12", "eddItemId": "1234", "pluginFile": "plublishpress/publishpress.php"}
     * @example {"apiUrl": "http://example.com/mysite", "licenseKey": "01010101010101001", "licenseStatus": "invalid", "pluginVersion": "1.0.12", "eddItemId": "1234", "pluginAuthor": "PublishPress"}
     */
    public function tryToCallValidWithInvalidAttributesThrowingException(UnitTester $I, Example $example)
    {
        $I->expectException(
            '\\PublishPress\\EDD_License\\Core\\Exception\\InvalidParams',
            function () use ($example)
            {
                $config = Stub::make(
                    '\\PublishPress\\EDD_License\\Core\\ServicesConfig',
                    $example,
                    $this
                );

                $config->validate();
            }
        );
    }

    /**
     * @example ["http://example.com/api", "http://example.com/api/"]
     * @example ["http://example.com/api/", "http://example.com/api/"]
     * @example ["http://example.com", "http://example.com/"]
     * @example ["http://example.com/", "http://example.com/"]
     */
    public function tryToSetApiurlAttributeCheckingIfTheSetValueHasTraillingSlash(UnitTester $I, Example $example)
    {
        $config = Stub::make(
            '\\PublishPress\\EDD_License\\Core\\ServicesConfig',
            []
        );

        $config->setApiUrl($example[0]);

        $I->assertEquals($example[1], $config->getApiUrl());
    }
}
