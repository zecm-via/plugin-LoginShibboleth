<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginShibboleth;

use Exception;
use Piwik\Url;

/**
 * Login controller
 *
 * @package Login
 */
class Controller extends \Piwik\Plugins\Login\Controller
{
    public function login($messageNoAccess = null, $infoMessage = false)
    {
        // Remove user session (if any)
        unset($_SESSION['loginshibboleth_user']);
        Url::redirectToUrl(Config::getLoginUrl());
    }

    /**
     * @throws Exception
     */
    public function logout()
    {
        // Remove user session (if any)
        unset($_SESSION['loginshibboleth_user']);
        Url::redirectToUrl(Config::getLogoutUrl());
    }
}
