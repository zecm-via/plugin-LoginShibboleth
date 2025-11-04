<?php

namespace Piwik\Plugins\LoginShibboleth\Adapter;

use Piwik\Plugins\LoginShibboleth\Config;
use RuntimeException;

class ShibbolethAdapter
{
    private $groupSeparator;

    public function __construct()
    {
        $this->groupSeparator = Config::getShibbolethGroupSeparator();
    }

    public function getUserInfo(): array
    {
        return [
            'username' => $this->getServerVar(Config::getShibbolethUserLogin()),
            'email' => $this->getServerVar(Config::getShibbolethUserEmail()),
        ];
    }

    public function getServerVar(string $key): ?string
    {
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        return '';
    }

    public function getUserProperty(): array
    {
        return [
            'view' => $this->getUrls('View'),
            'admin' => $this->getUrls('Admin'),
            'superuser' => $this->isSuperUser()
        ];
    }

    public function getUrls(string $accessType = 'View'): array
    {
        if (!in_array($accessType, array('Admin', 'View'))) {
            throw new RuntimeException("At this moment only 'Admin' and 'View' access types are available");
        }

        $serverGroups = ($accessType === 'Admin') ? Config::getShibbolethAdminGroups() : Config::getShibbolethViewGroups();

        $urls = [];
        $userGroupsArray = explode($this->groupSeparator, $this->getServerVar(Config::getShibbolethGroup()));
        $serverGroupsArray = explode($this->groupSeparator, $serverGroups);
        foreach ($serverGroupsArray as $g) {
            foreach ($userGroupsArray as $ug) {
                preg_match("/$g/", $ug, $result);
                if (count($result) > 0) {
                    $urls[] = [
                        'domain' => $result[1],
                        'path' => ''
                    ];
                }
            }
        }
        return $urls;
    }

    public function isSuperUser(): bool
    {
        $userGroupsArray = explode($this->groupSeparator, $this->getServerVar(Config::getShibbolethGroup()));
        $superGroupsArray = explode($this->groupSeparator, Config::getShibbolethSuperUserGroups());
        foreach ($userGroupsArray as $g) {
            if (in_array($g, $superGroupsArray, true)) {
                return true;
            }
        }

        return false;
    }
}