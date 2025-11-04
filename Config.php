<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginShibboleth;

use Exception;
use Piwik\Config as PiwikConfig;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\LoginShibboleth\Ldap\Client;
use Piwik\Plugins\LoginShibboleth\Ldap\ServerInfo;
use Piwik\Log\LoggerInterface;
use Piwik\Url;

/**
 * Utility class with methods to manage LoginShibboleth INI configuration.
 */
class Config
{
    public static $defaultConfig = array(
        'delete_old_user' => 0,
        'shibboleth_group' => 'memberOf',
        'shibboleth_group_separator' => ';',
        'shibboleth_groups_admin' => 'CN=(.*),OU=Groups,DC=organization',
        'shibboleth_groups_superuser' => 'CN=admin,OU=Groups,DC=organization',
        'shibboleth_groups_view' => 'CN=(.*),OU=Groups,DC=organization',
        'shibboleth_handler_path' => '/Shibboleth.sso',
        'shibboleth_user_email' => 'mail',
        'shibboleth_user_login' => 'uid',
    );

    public static function getPluginOptionValuesWithDefaults()
    {
        $result = self::$defaultConfig;
        foreach ($result as $name => $ignore) {
            $actualValue = Config::getConfigOption($name);

            if (isset($actualValue)) {
                $result[$name] = $actualValue;
            }
        }
        return $result;
    }

    /**
     * Returns an INI option value that is stored in the `[LoginShibboleth]` config section.
     *
     * @param $optionName
     * @return mixed
     */
    public static function getConfigOption($optionName)
    {
        return self::getConfigOptionFrom(PiwikConfig::getInstance()->LoginShibboleth, $optionName);
    }

    public static function getConfigOptionFrom($config, $optionName)
    {
        if (isset($config[$optionName])) {
            return $config[$optionName];
        } else {
            return self::getDefaultConfigOptionValue($optionName);
        }
    }

    public static function getDefaultConfigOptionValue($optionName)
    {
        return @self::$defaultConfig[$optionName];
    }

    public static function getLoginUrl()
    {
        return self::getHandlerUrl() . '/Login';
    }

    public static function getHandlerUrl()
    {
        $handlerPath = trim(self::getConfigOption('shibboleth_handler_path'), '/');
        if (is_null(Url::getHostFromUrl($handlerPath))) {
            $handlerPath = Url::getCurrentScheme() . '://' . Url::getHost() . '/' . $handlerPath;
        }

        return $handlerPath;
    }

    public static function getLogoutUrl()
    {
        return self::getHandlerUrl() . '/Logout';
    }

    public static function getShibbolethAdminGroups()
    {
        return self::getConfigOption('shibboleth_groups_admin');
    }

    public static function getShibbolethGroup()
    {
        return self::getConfigOption('shibboleth_group');
    }

    public static function getShibbolethGroupSeparator()
    {
        return self::getConfigOption('shibboleth_group_separator');
    }

    public static function getShibbolethSuperUserGroups()
    {
        return self::getConfigOption('shibboleth_groups_superuser');
    }

    public static function getShibbolethUserEmail()
    {
        return self::getConfigOption('shibboleth_user_email');
    }

    public static function getShibbolethUserLogin()
    {
        return self::getConfigOption('shibboleth_user_login');
    }

    public static function getShibbolethViewGroups()
    {
        return self::getConfigOption('shibboleth_groups_view');
    }


    public static function isDeleteOldUserActive()
    {
        return self::getConfigOption('delete_old_user');
    }

    public static function savePluginOptions($config)
    {
        $LoginShibboleth = PiwikConfig::getInstance()->LoginShibboleth;

        foreach (self::$defaultConfig as $name => $value) {
            if (isset($config[$name])) {
                $LoginShibboleth[$name] = $config[$name];
            }
        }

        PiwikConfig::getInstance()->LoginShibboleth = $LoginShibboleth;
        PiwikConfig::getInstance()->forceSave();
    }
}
