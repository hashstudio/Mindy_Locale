<?php

namespace Mindy\Locale;

use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 10/06/14.06.2014 18:47
 *
 * CMessageSource is the base class for message translation repository classes.
 *
 * A message source is an application component that provides message internationalization (i18n).
 * It stores messages translated in different languages and provides
 * these translated versions when requested.
 *
 * A concrete class must implement {@link loadMessages} or override {@link translateMessage}.
 *
 * @property string $language The language that the source messages are written in.
 * Defaults to {@link CApplication::language application language}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Mindy\Locale
 * @since 1.0
 */
abstract class MessageSource
{
    use Accessors, Configurator;

    /**
     * @var Translate
     */
    public $parent;
    /**
     * @var boolean whether to force message translation when the source and target languages are the same.
     * Defaults to false, meaning translation is only performed when source and target languages are different.
     * @since 1.1.4
     */
    public $forceTranslation = false;

    private $_language;
    private $_messages = [];

    public function init()
    {
//        Mindy::app()->signal->handler($this, 'missingTranslation', [$this, 'missingTranslation']);
    }

    /**
     * Loads the message translation for the specified language and category.
     * @param string $category the message category
     * @param string $language the target language
     * @return array the loaded messages
     */
    abstract protected function loadMessages($category, $language);

    /**
     * @return string the language that the source messages are written in.
     * Defaults to {@link CApplication::language application language}.
     */
    public function getLanguage()
    {
        return $this->_language === null ? $this->parent->sourceLanguage : $this->_language;
    }

    /**
     * @param string $language the language that the source messages are written in.
     */
    public function setLanguage($language)
    {
        $this->_language = Locale::getCanonicalID($language);
    }

    /**
     * Translates a message to the specified language.
     *
     * Note, if the specified language is the same as
     * the {@link getLanguage source message language}, messages will NOT be translated.
     *
     * If the message is not found in the translations, an {@link onMissingTranslation}
     * event will be raised. Handlers can mark this message or do some
     * default handling. The {@link CMissingTranslationEvent::message}
     * property of the event parameter will be returned.
     *
     * @param string $category the message category
     * @param string $message the message to be translated
     * @param string $language the target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
     * @return string the translated message (or the original message if translation is not needed)
     */
    public function translate($category, $message, $language = null)
    {
        if ($language === null) {
            $language = $this->parent->getLanguage();
        }

        if ($this->forceTranslation || $language !== $this->getLanguage()) {
            return $this->translateMessage($category, $message, $language);
        } else {
            return $message;
        }
    }

    /**
     * Translates the specified message.
     * If the message is not found, an {@link onMissingTranslation}
     * event will be raised.
     * @param string $category the category that the message belongs to
     * @param string $message the message to be translated
     * @param string $language the target language
     * @return string the translated message
     */
    protected function translateMessage($category, $message, $language)
    {
        $key = $language . '.' . $category;
        if (!isset($this->_messages[$key])) {
            $this->_messages[$key] = $this->loadMessages($category, $language);
        }

        if (isset($this->_messages[$key][$message]) && $this->_messages[$key][$message] !== '') {
            return $this->_messages[$key][$message];
//        } else {
//            Mindy::app()->signal->send($this, 'missingTranslation', $this, $language, $message);
        }
        return $message;
    }

    /**
     * Raised when a message cannot be translated.
     * Handlers may log this message or do some default handling.
     * The {@link CMissingTranslationEvent::message} property
     * will be returned by {@link translateMessage}.
     * @param $owner MessageSource
     * @param $language string
     * @param $message string
     */
    public function missingTranslation($owner, $language, $message)
    {

    }
}
