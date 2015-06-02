<?php

namespace dlds\localeurls;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\UrlManager as BaseUrlManager;

/**
 * UrlManager
 *
 * An extension of yii\web\UrlManager that takes care of adding a language parameter to all
 * created URLs.
 */
class UrlManager extends BaseUrlManager {

    /**
     * @inheritdoc
     */
    public $enablePrettyUrl = true;

    /**
     * @var string if a parameter with this name is passed to any `createUrl()` method, the created URL
     * will use the language specified there. URLs created this way can be used to switch to a different
     * language. If no such parameter is used, the currently detected application language is used.
     */
    public $languageParam = 'language';

    /**
     * @var string param which disable locale urls and retrieves standart url
     */
    public $disableLocalesParam = '_disable_locales';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->enablePrettyUrl)
        {
            throw new InvalidConfigException('Locale URL support requires enablePrettyUrl to be set to true.');
        }

        return parent::init();
    }

    /**
     * @inheritdoc
     */
    public function createUrl($params)
    {
        $params = (array) $params;
        $anchor = isset($params['#']) ? $params['#'] : '';
        $localeUrls = Yii::$app->localeUrls;

        if (isset($params[$this->languageParam]))
        {
            $language = $params[$this->languageParam];
            unset($params[$this->languageParam]);
            $languageRequired = true;
        }
        else
        {
            $language = Yii::$app->language;
            $languageRequired = false;
        }

        // if disable param is set return default url
        if (isset($params[$this->disableLocalesParam]))
        {
            unset($params[$this->disableLocalesParam]);

            return parent::createUrl($params);
        }

        $url = parent::createUrl($params);

        // Unless a language was explicitely specified in the parameters we can return a URL without any prefix
        // for the default language, if suffixes are disabled for the default language. In any other case we
        // always add the suffix, e.g. to create "reset" URLs that explicitely contain the default language.
        if (!$languageRequired && !$localeUrls->enableDefaultSuffix && $language === $localeUrls->getDefaultLanguage())
        {
            return $url;
        }
        else
        {
            $key = array_search($language, $localeUrls->languages);
            $base = $this->showScriptName ? $this->getScriptUrl() : $this->getBaseUrl();
            $length = strlen($base);
            if (is_string($key))
            {
                $language = $key;
            }

            $parts = parse_url($url);

            if (isset($parts['scheme'], $parts['host'], $parts['path']))
            {
                if ($length)
                {
                    $parts['path'] = trim(substr_replace($parts['path'], sprintf('%s/%s', $base, $language), 0, $length), '/');
                }
                else
                {
                    $parts['path'] = trim(sprintf('%s%s', $language, $parts['path']), '/');
                }

                $fullpath = sprintf('%s://%s/%s%s', $parts['scheme'], $parts['host'], $parts['path'], $this->suffix);

                if ($anchor)
                {
                    $fullpath = sprintf('%s#%s', $fullpath, $anchor);
                }

                if (isset($parts['query']))
                {
                    return sprintf('%s?%s', $fullpath, $parts['query']);
                }

                return $fullpath;
            }

            if ($length)
            {
                $path = substr_replace($url, "$base/$language", 0, $length);
            }
            else
            {
                $path = "/$language$url";
            }

            return $path.$anchor;
        }
    }
}