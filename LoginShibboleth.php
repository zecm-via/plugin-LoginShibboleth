<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginShibboleth;

use Exception;
use Piwik\Container\StaticContainer;
use Piwik\FrontController;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Plugins\LoginShibboleth\Auth;
use Piwik\View;
use Piwik\Request;
use Piwik\Session;

/**
 *
 * @package LoginShibboleth
 */
class LoginShibboleth extends \Piwik\Plugin
{
    /**
     * @return array
     */
    public function registerEvents()
    {
        $hooks = array(
            'Request.initAuthenticationObject' => 'initAuthenticationObject',
            'API.Request.authenticate' => 'apiRequestAuthenticate',
            'Controller.Login.resetPassword' => 'disablePasswordReset',
            'Controller.LoginShibboleth.resetPassword' => 'disablePasswordReset',
            'Controller.Login.confirmResetPassword' => 'disableConfirmResetPassword',
            'Controller.Login.confirmPassword' => 'skipPasswordVerification',
            'UsersManager.checkPassword' => 'checkPassword',
            'Login.userRequiresPasswordConfirmation' => 'skipPasswordConfirmation',
        );
        return $hooks;
    }

    public function disableConfirmResetPassword()
    {
        // redirect to login w/ error message
        $errorMessage = Piwik::translate("LoginShibboleth_UnsupportedPasswordReset");
        echo FrontController::getInstance()->dispatch('LoginShibboleth', 'login', array($errorMessage));

        exit;
    }

    public function disablePasswordReset()
    {
        $errorMessage = Piwik::translate("LoginShibboleth_UnsupportedPasswordReset");

        $view = new View("@Login/resetPassword");
        $view->infoMessage = null;
        $view->formErrors = array($errorMessage);

        echo $view->render();

        exit;
    }

    /**
     * Initializes the authentication object.
     * Listens to Request.initAuthenticationObject hook.
     */
    public function initAuthenticationObject($activateCookieAuth = false)
    {
        $auth = new Auth();
        StaticContainer::getContainer()->set('Piwik\Auth', $auth);
    }

    /**
     * Set login name and authentication token for authentication request.
     * Listens to API.Request.authenticate hook.
     */
    public function apiRequestAuthenticate(
        #[\SensitiveParameter]
        $tokenAuth
    ) {
        /** @var Auth $auth */
        $auth = StaticContainer::get('Piwik\Auth');
        $auth->setLogin($login = null);
        $auth->setTokenAuth($tokenAuth);
    }

    /**
     * Listens to UsersManager.checkPassword hook.
     */
    public function checkPassword()
    {
        $auth = StaticContainer::get('Piwik\Auth');
        $this->disablePasswordChange($auth);
    }

    /**
     * Throws Exception when user tries to change password
     * because such user's pass should be managed directly on Shibboleth
     *
     * @throws Exception
     */
    public function disablePasswordChange(Auth $auth)
    {
        throw new Exception(
            Piwik::translate('LoginShibboleth_LdapUserCantChangePassword')
        );
    }

    public function skipPasswordVerification()
    {
        $passwordVerify = StaticContainer::get('Piwik\Plugins\Login\PasswordVerifier');
        $passwordVerify->setPasswordVerifiedCorrectly();
    }

    public function skipPasswordConfirmation(&$requiresPasswordConfirmation, $login)
    {
        $requiresPasswordConfirmation = false;
    }
}