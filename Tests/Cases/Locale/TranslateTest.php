<?php

namespace Mindy\Locale\Tests;

use Mindy\Locale\MessageSource;
use Mindy\Locale\Translate;

/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 15/10/14.10.2014 15:49
 */

class TranslateTest extends TestCase
{
    public function testInstance()
    {
        $t = new Translate();

        $this->assertInstanceOf(MessageSource::className(), $t->messages);
        $this->assertInstanceOf(MessageSource::className(), $t->coreMessages);

        $this->assertEquals('en_us', $t->getLanguage());

        $this->assertEquals('', $t->t('base', ''));
        $this->assertEquals('foo', $t->t('base', 'foo'));
    }
}
