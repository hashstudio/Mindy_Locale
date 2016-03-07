<?php

namespace Mindy\Locale\Tests;

use Mindy\Locale\Translate;

/**
 * Tests for various usages of Translate::t
 *
 * http://code.google.com/p/yii/issues/detail?id=1875
 * http://code.google.com/p/yii/issues/detail?id=1987
 *
 * http://unicode.org/repos/cldr-tmp/trunk/diff/supplemental/language_plural_rules.html
 */
class BaseTest extends TestCase
{
    /**
     * @var Translate
     */
    public $t;

    public function setUp()
    {
        $this->t = Translate::getInstance([
            'sourceLanguage' => 'es',
            'source' => [
                'messages' => [
                    'class' => '\Mindy\Locale\PhpMessageSource',
                    'basePath' => dirname(__FILE__) . '/../../data',
                    // 'forceTranslation' => true,
                ],
            ],
        ]);
    }

    public function tearDown()
    {
        $this->t->sourceLanguage = 'en_us';
    }

    // Simple: 'msg'
    public function testSimple()
    {
        $this->t->setLanguage('ru');
        $this->assertEquals('апельсины', $this->t->t('test', 'oranges'));
    }

    public function testSimpleSameLanguage()
    {
        $this->t->setLanguage('es');
        $this->assertEquals('no_changes', $this->t->t('test', 'no_changes'));
    }

    public function testSimplePlaceholders()
    {
        $this->t->setLanguage('ru');
        $this->assertEquals('сумочки caviar', $this->t->t('test', '{brand} bags', array('{brand}' => 'caviar')));
        $this->assertEquals('в корзине: 10', $this->t->t('test', 'in the cart: {n}', 10));
    }

    public function testSimplePlaceholdersSameLanguage()
    {
        $this->t->setLanguage('es');
        $this->assertEquals('10 changes', $this->t->t('test', '{n} changes', 10));
    }

    // Plural: 'msg1|msg2|msg3'
    public function testPlural()
    {
        // CLDR
        $this->t->setLanguage('ru');

        // array notation
        $this->assertEquals('огурец', $this->t->t('test', 'cucumber|cucumbers', [1]));

        //ru
        $this->assertEquals('огурец', $this->t->t('test', 'cucumber|cucumbers', 1));
        $this->assertEquals('огурец', $this->t->t('test', 'cucumber|cucumbers', 101));
        $this->assertEquals('огурец', $this->t->t('test', 'cucumber|cucumbers', 51));
        $this->assertEquals('огурца', $this->t->t('test', 'cucumber|cucumbers', 2));
        $this->assertEquals('огурца', $this->t->t('test', 'cucumber|cucumbers', 62));
        $this->assertEquals('огурца', $this->t->t('test', 'cucumber|cucumbers', 104));
        $this->assertEquals('огурцов', $this->t->t('test', 'cucumber|cucumbers', 5));
        $this->assertEquals('огурцов', $this->t->t('test', 'cucumber|cucumbers', 78));
        $this->assertEquals('огурцов', $this->t->t('test', 'cucumber|cucumbers', 320));
        $this->assertEquals('огурцов', $this->t->t('test', 'cucumber|cucumbers', 0));

        // fractions (you should specify fourh variant to use these in Russian)
        $this->assertEquals('огурца', $this->t->t('test', 'cucumber|cucumbers', 1.5));

        // en
        $this->t->setLanguage('en');

        $this->assertEquals('cucumber', $this->t->t('test', 'cucumber|cucumbers', 1));
        $this->assertEquals('cucumbers', $this->t->t('test', 'cucumber|cucumbers', 2));
        $this->assertEquals('cucumbers', $this->t->t('test', 'cucumber|cucumbers', 0));

        // short forms
        $this->t->setLanguage('ru');

        $this->assertEquals('огурец', $this->t->t('test', 'cucumber|cucumbers', 1));

        // explicit params
        $this->assertEquals('огурец', $this->t->t('test', 'cucumber|cucumbers', array(0 => 1)));
    }

    public function testPluralPlaceholders()
    {
        $this->t->setLanguage('ru');

        $this->assertEquals('1 огурец', $this->t->t('test', '{n} cucumber|{n} cucumbers', 1));
        $this->assertEquals('2 огурца', $this->t->t('test', '{n} cucumber|{n} cucumbers', 2));
        $this->assertEquals('5 огурцов', $this->t->t('test', '{n} cucumber|{n} cucumbers', 5));

        // more placeholders
        $this->assertEquals('+ 5 огурцов', $this->t->t('test', '{sign} {n} cucumber|{sign} {n} cucumbers', array(5, '{sign}' => '+')));

        // placeholder swapping
        $this->assertEquals('один огурец', $this->t->t('test', '{n} cucumber|{n} cucumbers', array(1, '{n}' => 'один')));
    }

    /**
     * If there are useless params in translation just ignore them.
     */
    public function testPluralMoreVariants()
    {
        $this->t->setLanguage('ru');
        $this->assertEquals('шляпы', $this->t->t('test', 'hat|hats', array(2)));
    }

    /**
     * If there are less variants in translation like
     * 'zombie|zombies' => 'зомби' (CLDR requires 3 variants for Russian
     * but zombie is too special to be plural)
     *
     * Same for Chinese but there are no plurals at all.
     */
    public function testPluralLessVariants()
    {
        // three variants are required and only one specified (still valid for
        // Russian in some special cases)
        $this->t->setLanguage('ru');
        $this->assertEquals('зомби', $this->t->t('test', 'zombie|zombies', 10));
        $this->assertEquals('зомби', $this->t->t('test', 'zombie|zombies', 1));

        // language with no plurals
        $this->t->setLanguage('zh_cn');
        $this->assertEquals('k-s', $this->t->t('test', 'kiss|kisses', 1));

        // 3 variants are required while only 2 specified
        // this one is synthetic but still good to know it at least does not
        // produce error
        $this->t->setLanguage('ru');
        $this->assertEquals('син1', $this->t->t('test', 'syn1|syn2|syn3', 1));
        $this->assertEquals('син2', $this->t->t('test', 'syn1|syn2|syn3', 2));
        $this->assertEquals('син2', $this->t->t('test', 'syn1|syn2|syn3', 5));
    }

    public function pluralLessVariantsInSource()
    {
        // new doesn't have two forms in English
        $this->t->setLanguage('ru');
        $this->assertEquals('новости', $this->t->t('test', 'news', 2));
    }

    public function testPluralSameLanguage()
    {
        $this->t->setLanguage('es');

        $this->assertEquals('cucumbez', $this->t->t('test', 'cucumbez|cucumberz', 1));
        $this->assertEquals('cucumberz', $this->t->t('test', 'cucumbez|cucumberz', 2));
        $this->assertEquals('cucumberz', $this->t->t('test', 'cucumbez|cucumberz', 0));
    }

    public function testPluralPlaceholdersSameLanguage()
    {
        $this->t->setLanguage('es');

        $this->assertEquals('1 cucumbez', $this->t->t('test', '{n} cucumbez|{n} cucumberz', 1));
        $this->assertEquals('2 cucumberz', $this->t->t('test', '{n} cucumbez|{n} cucumberz', 2));
        $this->assertEquals('5 cucumberz', $this->t->t('test', '{n} cucumbez|{n} cucumberz', 5));
    }

    // Choice: 'expr1#msg1|expr2#msg2|expr3#msg3'
    public function testChoice()
    {
        $this->t->setLanguage('ru');
        // simple choices
//        $this->assertEquals('одна книга', $this->t->t('test', 'n==1#one book|n>1#many books', 1));
//        $this->assertEquals('много книг', $this->t->t('test', 'n==1#one book|n>1#many books', 10));
//        $this->assertEquals('одна книга', $this->t->t('test', '1#one book|n>1#many books', 1));
        $this->assertEquals('много книг', $this->t->t('test', '1#one book|n>1#many books', 10));
    }

    public function testChoiceSameLanguage()
    {
        $this->t->setLanguage('es');
        $this->assertEquals('one book', $this->t->t('test', 'n==1#one book|n>1#many books', 1));
        $this->assertEquals('many books', $this->t->t('test', 'n==1#one book|n>1#many books', 10));
    }

    public function testChoicePlaceholders()
    {
        //$this->assertEquals('51 apples', $this->t->t('app', '1#1apple|n>1|{n} apples', array(51, 'n'=>51)));
    }

    public function testChoicePlaceholdersSameLanguage()
    {

    }
}
