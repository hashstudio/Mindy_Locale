<?php

namespace Mindy\Locale\Tests;

use Mindy\Helper\Alias;
use Mindy\Locale\Translate;

class PhpMessageSourceTest extends TestCase
{
    public function testExtensionTranslation()
    {
        $t = Translate::getInstance();
        Alias::set('PhpMessageSourceTest.messages', dirname(__FILE__) . '/../../messages');
        $t->setLanguage('de_DE');
        $t->messages->extensionPaths['MyTestExtension'] = 'PhpMessageSourceTest.messages';
        $this->assertEquals('Hallo Welt!', $t->t('MyTestExtension.testcategory', 'Hello World!'));
    }

    public function testModuleTranslation()
    {
        $t = Translate::getInstance();
        $t->setLanguage('de_DE');
        $this->assertEquals('de_DE', $t->getLanguage());
        $this->assertEquals('PhpMessageSourceTest.messages', $t->messages->extensionPaths['MyTestExtension']);
        $this->assertEquals('Hallo Welt!', $t->t('MyTestExtension.testcategory', 'Hello World!'));
    }
}
