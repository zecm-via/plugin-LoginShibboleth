# Shibboleth plugin

## Description

A simple login plugin to force Shibboleth login on Matomo.

Based on a users group membership, corresponding matomo websites will be accessible with a matching membership level.

### Limitations

* No support for manual groups
* No LDAP support
* Permissions are updated on log-in only 
* (Currently) no Single Sign-Out

## Installation

* Ensure that the serviceProvider is running on the same server than Matomo
   * It should at least provide three attributes: the users login, its mail-address and its group membership
* Configure the plugin for your use cases (See [Settings](#settings))
* Enable the Shibboleth login in your apache configuration:

```apacheconf
# Use shibboleth for index.php
<Files "index.php">
  AuthType shibboleth
  ShibRequestSetting requireSession true
  Require shibboleth
  <If "%{QUERY_STRING} =~ /(module=API)/">
    ShibRequestSetting requireSession false
  </If>
  <If "%{QUERY_STRING} =~ /(module=CoreAdminHome&action=optOut)/">
    ShibRequestSetting requireSession false
  </If>
</Files>
```

## Settings

Settings are to be changed in the config file directly, no admin interface is provided yet.

| Name                         | Description                                                                          | Default value                         |
|------------------------------|--------------------------------------------------------------------------------------|---------------------------------------|
| `delete_old_user`            | Should users without permissions be deleted?                                         | `0` (no)                              |
| `shibboleth_handler_path`    | Path to the ServiceProvider endpoint                                                 | `/Shibboleth.sso`                     |
| `shibboleth_group`           | Shibboleth attribute to look for membership                                          | `memberOf`                            |
| `shibboleth_group_separator` | Separator used in the membership attribute                                           | `;`                                   |
| `shibboleth_groups_admin`    | One (or several) regexes used to identify domains the user has admin permission on   | `CN=(.*),OU=Groups,DC=organization`   |
| `shibboleth_groups_view`     | One (or several) regexes used to identify domains the user has viewing permission on | `CN=(.*),OU=Groups,DC=organization`   |
| `shibboleth_groups_superuser`| One (or several) groups defining superuser access                                    | `CN=admin,OU=Groups,DC=organization`  |
| `shibboleth_user_email`      | Shibboleth attribute to look for the users email                                     | `mail`                               |
| `shibboleth_user_login`      | Shibboleth attribute to look for the users login                                     | `uid`                                 |
