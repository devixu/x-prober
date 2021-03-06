<?php

namespace InnStudio\Prober\Components\Updater;

use InnStudio\Prober\Components\Config\ConfigApi;
use InnStudio\Prober\Components\Events\EventsApi;
use InnStudio\Prober\Components\Helper\HelperApi;
use InnStudio\Prober\Components\I18n\I18nApi;

class Updater
{
    private $ID = 'updater';

    public function __construct()
    {
        EventsApi::on('init', array($this, 'update'));
        EventsApi::on('conf', array($this, 'conf'));
    }

    public function conf(array $conf)
    {
        $langUpdateNotice = I18nApi::_('%1$s found update! Version %2$s → {APP_NEW_VERSION}');
        $langUpdating     = I18nApi::_('Updating...');
        $langUpdateError  = I18nApi::_('Update error');

        $conf[$this->ID] = array(
            'changelogUrl' => ConfigApi::$CHANGELOG_URL,
            'version'      => ConfigApi::$APP_VERSION,
            'lang'         => array(
                'foundNewVersion' => \sprintf(
                    "✨ {$langUpdateNotice}",
                    $this->_(ConfigApi::$APP_NAME),
                    ConfigApi::$APP_VERSION
                ),
                'loading' => "⏳ {$langUpdating}",
                'error'   => "❌ {$langUpdateError}",
            ),
        );

        return $conf;
    }

    public function update()
    {
        if ( ! HelperApi::isAction('update')) {
            return;
        }

        // check file writable
        if ( ! \is_writable(__FILE__)) {
            HelperApi::dieJson(array(
                'code' => -1,
                'msg'  => I18nApi::_('File can not update.'),
            ));
        }

        $content = \file_get_contents(ConfigApi::$UPDATE_PHP_URL);

        if ( ! $content) {
            HelperApi::dieJson(array(
                'code' => -1,
                'msg'  => I18nApi::_('Update file not found.'),
            ));
        }

        if ((bool) \file_put_contents(__FILE__, $content)) {
            if (\function_exists('\\opcache_reset')) {
                \opcache_reset();
            }

            HelperApi::dieJson(array(
                'code' => 0,
                'msg'  => I18nApi::_('Update success...'),
            ));
        }

        HelperApi::dieJson(array(
            'code' => -1,
            'msg'  => I18nApi::_('Update error.'),
        ));
    }

    private function _($str)
    {
        return I18nApi::_($str);
    }
}
