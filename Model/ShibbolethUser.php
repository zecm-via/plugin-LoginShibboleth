<?php

namespace Piwik\Plugins\LoginShibboleth\Model;

use Piwik\Common;
use Piwik\Date;
use Piwik\Plugins\LoginShibboleth\Adapter\ShibbolethAdapter;
use Piwik\Plugins\LoginShibboleth\Config;
use Piwik\Plugins\UsersManager\Model as UserModel;
use Piwik\Plugins\SitesManager\Model as SiteManager;

class ShibbolethUser extends UserModel
{
    /**
     * Placeholder for UserInfo array.
     *
     * @var
     */
    private $userInfo = [
        'username' => '',
        'email' => '',
        'token' => ''
    ];

    /**
     * Placeholder for UserProperty array.
     *
     * @var
     */
    private $userProperty = [
        'view' => [],
        'admin' => [],
        'superuser' => false
    ];


    /**
     * @param $username
     * @return array|mixed
     * @throws Exception
     */
    public function getUser($username): array
    {
        $sh = new ShibbolethAdapter();
        $this->userProperty = $sh->getUserProperty();
        $this->userInfo = $sh->getUserInfo();

        // Check the permissions in database
        $this->addOrUpdateUserGeneric();

        return [
            'login' => $this->userInfo['username'],
            'email' => $this->userInfo['email'],
            'token_auth' => $this->getToken(),
            'superuser_access' => $this->userProperty['superuser'],
            'password' => $this->getPassword(),
        ];
    }

    /**
     * Adds the user with the given rights.
     * It also updates the user if it exists.
     */
    private function addOrUpdateUserGeneric()
    {
        $viewSiteIds = $this->convertDomainsToIds($this->userProperty['view']);
        $adminSiteIds = $this->convertDomainsToIds($this->userProperty['admin']);
        $login = $this->userInfo['username'];
        $isSuperUser = $this->userProperty['superuser'];

        // Early return if the user has no permissions
        if (Config::isDeleteOldUserActive()
            && !$isSuperUser
            && (count($this->getSitesAccessFromUser($login)) === 0
                || (count($viewSiteIds) === 0
                    && count($adminSiteIds) === 0
                )
            )
        ) {
            // Delete user account
            $this->deleteUserOnly($login);
            return;
        }

        // Create user if not existing already
        if (!$this->userExists($login)) {
            if (count($viewSiteIds) > 0 || count($adminSiteIds) > 0) {
                $this->addUser(
                    $login,
                    md5($this->getPassword()),
                    $this->userInfo['email'],
                    Date::now()->getDatetime()
                );
            }
        }

        // Configure superUserAccess
        $this->setSuperUserAccess($login, $isSuperUser);

        // Sync view and admin permissions
        foreach (['view', 'admin'] as $access) {
            $localAccess = array_column(
                $this->getSitesAccessFromUserWithFilters(userLogin: $login, access: $access)[0],
                'idsite'
            );
            $targetAccess = $this->convertDomainsToIds($this->userProperty[$access]);

            if (count($targetAccess) > 0) {
                $toAdd = array_diff($targetAccess, $localAccess);
                if (count($toAdd) > 0) {
                    $this->addUserAccess($login, $access, $toAdd);
                }
            }

            if (count($localAccess) > 0) {
                $toRemove = array_diff($localAccess, $targetAccess);
                if (count($toRemove) > 0) {
                    $this->removeUserAccess($login, $access, $toRemove);
                }
            }
        }
    }

    /**
     * @param $sections
     * @return array
     * @throws Exception
     */
    private function convertDomainsToIds($sections)
    {
        $result = [];
        $sitesManager = new SiteManager();

        foreach ($sections as $s) {
            $siteIds = $sitesManager->getAllSitesIdFromSiteUrl(
                $this->getNormalizedUrls($s['domain'] . $s['path'])
            );
            if (count($siteIds)) {
                array_push($result, ...$siteIds);
            }
        }

        return array_column($result, 'idsite');
    }

    protected function getNormalizedUrls($url)
    {
        // if found, remove scheme and www. from URL
        $hostname = str_replace('www.', '', $url);
        $hostname = str_replace('http://', '', $hostname);
        $hostname = str_replace('https://', '', $hostname);

        // return all variations of the URL
        return [
            $url,
            "http://" . $hostname,
            "http://www." . $hostname,
            "https://" . $hostname,
            "https://www." . $hostname,
        ];
    }

    /**
     * Get the password.
     *
     * @return string 8char
     */
    public function getPassword()
    {
        if (array_key_exists('password', $this->userInfo)) {
            return $this->userInfo['password'];
        }
        return Common::getRandomString(8);
    }

    /**
     * Get the token.
     *
     * @return string
     */
    public function getToken()
    {
        if (array_key_exists('token', $this->userInfo)) {
            return $this->userInfo['token'];
        }
        return $this->generateRandomTokenAuth();
    }
}