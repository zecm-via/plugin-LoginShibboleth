<?php

namespace Piwik\Plugins\LoginShibboleth;

use Piwik\Auth as AuthInterface;
use Piwik\AuthResult;
use Piwik\Plugins\LoginShibboleth\Model\ShibbolethUser as ShibbolethModel;
use Piwik\Plugins\UsersManager\Model as RegularModel;
use RuntimeException;

class Auth implements AuthInterface
{
    /**
     * The username to authenticate with.
     *
     * @var null|string
     */
    protected $login = null;

    /**
     * The token auth to authenticate with.
     *
     * @var null|string
     */
    protected $token_auth = null;

    /**
     * The password to authenticate with (unhashed).
     *
     * @var null|string
     */
    protected $password = null;

    /**
     * The password hash to authenticate with.
     *
     * @var string
     */
    private $passwordHash = null;

    /**
     * Authentication module's name, e.g., "LoginShibboleth".
     *
     * @return string
     */
    public function getName()
    {
        return 'LoginShibboleth';
    }

    /**
     * @return AuthResult
     * @throws \Exception
     */
    public function authenticate()
    {
        // Check if there is a shibboleth session active
        if (isset($_SERVER[Config::getShibbolethUserLogin()])) {
            $this->login = $_SERVER[Config::getShibbolethUserLogin()];

            // If so, check if there is a matching session
            if ($_SESSION['loginshibboleth_user'] && $this->login === $_SESSION['loginshibboleth_user']['login']) {
                return $this->makeSuccess($_SESSION['loginshibboleth_user']);
            }
        }

        // If there is no login set, check the token auth
        if ($this->login === null) {
            $model = new RegularModel();
            $user = $model->getUserByTokenAuth($this->token_auth);
            if (!empty($user['login'])) {
                return $this->makeSuccess($user);
            }
        } elseif (!empty($this->login)) {
            if ($this->login !== 'anonymous') {
                $model = new ShibbolethModel();
                $user = $model->getUser($this->login);
                if ($user) {
                    // Save the user in session to avoid querying it again
                    $_SESSION['loginshibboleth_user'] = $user;
                }
                $this->setTokenAuth($model->getToken());
                return $this->makeSuccess($user);
            }
        }

        return new AuthResult(AuthResult::FAILURE, $this->login, $this->token_auth);
    }

    private function makeSuccess($user)
    {
        $this->setLogin($user['login']);
        $this->setTokenAuth($user['token_auth']);
        $code = $user['superuser_access'] ? AuthResult::SUCCESS_SUPERUSER_AUTH_CODE : AuthResult::SUCCESS;

        return new AuthResult($code, $this->login, $user['token_auth']);
    }

    /**
     * Returns the secret used to calculate a user's token auth.
     *
     * @return string
     *
     * @throws RuntimeException if the token auth cannot be calculated at the current time.
     */
    public function getTokenAuthSecret()
    {
        $user = $this->login;
        if (empty($user)) {
            throw new RuntimeException("Cannot find user '{$this->login}'");
        }
        return $user['password'];
    }

    /**
     * Accessor to set password.
     *
     * @param string $password password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Sets the hash of the password to authenticate with. The hash will be an MD5 hash.
     *
     * @param string $passwordHash The hashed password.
     * @throws Exception if authentication by hashed password is not supported.
     */
    public function setPasswordHash(
        #[\SensitiveParameter]
        $passwordHash
    ) {
        $this->passwordHash = $passwordHash;
    }

    /**
     * Returns the login of the user being authenticated.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Sets the login name to authenticate with.
     *
     * @param string $login The username.
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * Returns the user's token auth.
     *
     * @return string
     */
    public function getTokenAuth()
    {
        if (!empty($this->token_auth)) {
            return $this->token_auth;
        }

        return null;
    }

    /**
     * Sets the authentication token to authenticate with.
     *
     * @param string $token_auth authentication token
     */
    public function setTokenAuth(
        #[\SensitiveParameter]
        $token_auth
    ) {
        $this->token_auth = $token_auth;
    }
}