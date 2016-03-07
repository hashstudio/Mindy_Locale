<?php

namespace Mindy\Locale;

use Mindy\Helper\Alias;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Singleton;
use ReflectionProperty;

/**
 * Class Translate
 * @package Mindy\Locale
 */
class Translate
{
    use Accessors, Singleton;

    /**
     * @var string the class used to get locale data. Defaults to 'CLocale'.
     */
    public $localeClass = 'Mindy\Locale\Locale';
    /**
     * @var array
     */
    public $source = [];
    /**
     * @var string the charset
     */
    public $charset = 'utf-8';
    /**
     * @var string the language that the application is written in. This mainly refers to
     * the language that the messages and view files are in. Defaults to 'en_us' (US English).
     */
    public $sourceLanguage = 'en_us';
    /**
     * @var MessageSource[]
     */
    private static $_sources = [];
    /**
     * @var string
     */
    private $_language;

    public function __get($name)
    {
        if (isset(self::$_sources[$name])) {
            return self::$_sources[$name];
        }

        return $this->__getInternal($name);
    }

    public function init()
    {
        if (!array_key_exists('coreMessages', $this->source)) {
            $this->source['coreMessages'] = [
                'class' => '\Mindy\Locale\PhpMessageSource',
                'language' => 'en_us',
            ];
        }

        if (!array_key_exists('messages', $this->source)) {
            $this->source['messages'] = [
                'class' => '\Mindy\Locale\PhpMessageSource',
            ];
        }
        foreach ($this->source as $name => $source) {
            $params = is_array($source) ? array_merge(['parent' => $this], $source) : $source;
            self::$_sources[$name] = Creator::createObject($params);
        }
    }

    /**
     * Specifies which language the application is targeted to.
     *
     * This is the language that the application displays to end users.
     * If set null, it uses the {@link sourceLanguage source language}.
     *
     * Unless your application needs to support multiple languages, you should always
     * set this language to null to maximize the application's performance.
     * @param string $language the user language (e.g. 'en_US', 'zh_CN').
     * If it is null, the {@link sourceLanguage} will be used.
     */
    public function setLanguage($language)
    {
        $this->_language = $language;
    }

    /**
     * Returns the language that the user is using and the application should be targeted to.
     * @return string the language that the user is using and the application should be targeted to.
     * Defaults to the {@link sourceLanguage source language}.
     */
    public function getLanguage()
    {
        return $this->_language === null ? $this->sourceLanguage : $this->_language;
    }

    /**
     * Returns the locale instance.
     * @param string $localeID the locale ID (e.g. en_US). If null, the {@link getLanguage application language ID} will be used.
     * @return \Mindy\Locale\Locale an instance of CLocale
     */
    public function getLocale($localeID = null)
    {
        return call_user_func_array([$this->localeClass, 'getInstance'], [$localeID === null ? $this->getLanguage() : $localeID]);
    }

    /**
     * Returns the directory that contains the locale data.
     * @return string the directory that contains the locale data. It defaults to 'framework/i18n/data'.
     * @since 1.1.0
     */
    public function getLocaleDataPath()
    {
        $vars = get_class_vars($this->localeClass);
        if (empty($vars['dataPath'])) {
            return Alias::get('system.i18n.data');
        }
        return $vars['dataPath'];
    }

    /**
     * Sets the directory that contains the locale data.
     * @param string $value the directory that contains the locale data.
     * @since 1.1.0
     */
    public function setLocaleDataPath($value)
    {
        $property = new ReflectionProperty($this->localeClass, 'dataPath');
        $property->setValue($value);
    }

    /**
     * Returns the localized version of a specified file.
     *
     * The searching is based on the specified language code. In particular,
     * a file with the same name will be looked for under the subdirectory
     * named as the locale ID. For example, given the file "path/to/view.php"
     * and locale ID "zh_cn", the localized file will be looked for as
     * "path/to/zh_cn/view.php". If the file is not found, the original file
     * will be returned.
     *
     * For consistency, it is recommended that the locale ID is given
     * in lower case and in the format of LanguageID_RegionID (e.g. "en_us").
     *
     * @param string $srcFile the original file
     * @param string $srcLanguage the language that the original file is in. If null, the application {@link sourceLanguage source language} is used.
     * @param string $language the desired language that the file should be localized to. If null, the {@link getLanguage application language} will be used.
     * @return string the matching localized file. The original file is returned if no localized version is found
     * or if source language is the same as the desired language.
     */
    public function findLocalizedFile($srcFile, $srcLanguage = null, $language = null)
    {
        if ($srcLanguage === null) {
            $srcLanguage = $this->sourceLanguage;
        }
        if ($language === null) {
            $language = $this->getLanguage();
        }
        if ($language === $srcLanguage) {
            return $srcFile;
        }
        $desiredFile = dirname($srcFile) . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . basename($srcFile);
        return is_file($desiredFile) ? $desiredFile : $srcFile;
    }

    /**
     * Returns the locale-dependent date formatter.
     * @return \Mindy\Locale\DateFormatter the locale-dependent date formatter.
     * The current {@link getLocale application locale} will be used.
     */
    public function getDateFormatter()
    {
        return $this->getLocale()->getDateFormatter();
    }

    /**
     * Translates a message to the specified language.
     * This method supports choice format (see {@link ChoiceFormat}),
     * i.e., the message returned will be chosen from a few candidates according to the given
     * number value. This feature is mainly used to solve plural format issue in case
     * a message has different plural forms in some languages.
     * @param string $category message category. Please use only word letters. Note, category 'yii' is
     * reserved for Yii framework core code use. See {@link PhpMessageSource} for
     * more interpretation about message category.
     * @param string $message the original message
     * @param array $params parameters to be applied to the message using <code>strtr</code>.
     * The first parameter can be a number without key.
     * And in this case, the method will call {@link ChoiceFormat::format} to choose
     * an appropriate message translation.
     * Starting from version 1.1.6 you can pass parameter for {@link ChoiceFormat::format}
     * or plural forms format without wrapping it with array.
     * This parameter is then available as <code>{n}</code> in the message translation string.
     * @param string $source which message source application component to use.
     * Defaults to null, meaning using 'coreMessages' for messages belonging to
     * the 'yii' category and using 'messages' for the rest messages.
     * @param string $language the target language. If null (default), the {@link Application::getLanguage application language} will be used.
     * @return string the translated message
     * @see MessageSource
     */
    public static function t($category, $message, $params = [], $source = null, $language = null)
    {
        if ($source === null) {
            $source = ($category === 'yii' || $category === 'zii') ? 'coreMessages' : 'messages';
        }

        if (($source = self::getSource($source)) !== null) {
            $message = $source->translate($category, $message, $language);
        }

        if ($params === []) {
            return $message;
        }

        if (!is_array($params))
            $params = [$params];

        if (isset($params[0])) { // number choice
            if (strpos($message, '|') !== false) {
                if (strpos($message, '#') === false) {
                    $chunks = explode('|', $message);
                    $expressions = self::getInstance()->getLocale($language)->getPluralRules();
                    if ($n = min(count($chunks), count($expressions))) {
                        for ($i = 0; $i < $n; $i++) {
                            $chunks[$i] = $expressions[$i] . '#' . $chunks[$i];
                        }
                        $message = implode('|', $chunks);
                    }
                }
                $message = ChoiceFormat::format($message, $params[0]);
            }
            if (!isset($params['{n}'])) {
                $params['{n}'] = $params[0];
            }
            unset($params[0]);
        }
        return $params !== [] ? strtr($message, $params) : $message;
    }

    private static function getSource($source)
    {
        return array_key_exists($source, self::$_sources) ? self::$_sources[$source] : null;
    }
}
