# umichoidc (wwsauth) module for Drupal

umichoidc (wwsauth) is a Drupal module that extends the OpenID Connect (openid_connect) module to allow MCommunity groups to be used for authorization roles for group-based permissions in Drupal.

## Quick Links

* [Versions](README.md#versions)

* [Features](README.md#features)

* [Install](README.md#install)

* [Documentation](README.md#documentation)

* [How can I report an issue, get help, request a feature, or help with module development?](README.md#how-can-i-report-an-issue-get-help-request-a-feature-or-help-with-module-development-howtohelp)



## Versions

* 3.x - This release of umichoidc (wwsauth) is dependent upon the [3.0.0-alpha6](https://www.drupal.org/project/openid_connect/releases/3.0.0-alpha6) release of the openid_connect module.  This version of umichoidc (wwsauth) has been tested with and is supported for Drupal 10.5+ through Drupal 11.

* 1.0.x - The initial release of umichoidc (wwsauth) was dependent upon the [8.x-1.4](https://www.drupal.org/project/openid_connect/releases/8.x-1.4) release of the openid_connect module.  This version works with Drupal 9.5 - 10.  This release is still supported currently for Drupal 10 sites in U-M Pantheon, although support for 1.0.x with Drupal 10 in U-M Pantheon will soon be updated to 3.x.

## Features

* Allows use of U-M MCommunity groups to manage as Roles in Drupal and Role Permissions with the same granularity as native Roles in Drupal.
* Module adds/removes MCommunity groups based Roles for the user as they login
* Group membership is managed in MCommunity but the corresponding Drupal Role will likely never reflect more than a snapshot of the MCommunity group membership at any given time.
* At user login, the Drupal Role will be added to their username at the time of login if they are a member of the corresponding MCommunity group.
* The Drupal Roles *DO NOT* sync with MCommunity.
* Supported / tested OIDC providers:
    * [Shibboleth](https://www.shibboleth.net/) OIDC using the `edumember_ismemberof` attribute for LDAP group membership.


## Install

### Requirements
* Drupal 10.5 or later
* PHP 8.3 or later - [see Drupal system requirements](https://www.drupal.org/docs/getting-started/system-requirements/overview)
* Client credentials for a supported OIDC provider (for example, Shibboleth OIDC)

### Install steps

As of July 9, 2025, new Drupal 11 sites in U-M Pantheon via the [ITS Web Hosting Services Portal](https://admin.webservices.umich.edu/) are initialized with [openid_connect](https://www.drupal.org/project/openid_connect) and [umichoidc](https://github.com/its-webhosting/umichoidc) so no additional installation steps are required for those sites.  

Drupal 10.5 sites created since that date had the older releases of `openid_connect` and `umichoidc` installed and will need to be manually upgraded.  See the [Drupal 10 Manual Upgrade Notes](#drupal10upgradenotes) below for more details.

Drupal 10.5+ and 11 sites in U-M Pantheon created prior to July 9, 2025, will need to have these modules installed and configured manually.

Drupal 10.5+ and 11 sites hosted outside of U-M Pantheon will require installation and configuration according to your hosting vendor's specifications.

To Install **umichoidc** via the command line (CLI) into a U-M Pantheon site that was created prior to OIDC provisioning integration:

* Prerequisites

  * **Pantheon WebOps Workflow**:  This documentation assumes an understanding of the Pantheon WebOps Workflow.  Read the [Pantheon WebOps Workflow documentation](https://docs.pantheon.io/pantheon-workflow) for more information.

  * **Terminus**:  This documentation assumes an understanding of the Pantheon Terminus command line interface (CLI).  Read the [Terminus documentation](https://docs.pantheon.io/terminus) for more information.  Install and configure Terminus according to the [installation documentation](https://docs.pantheon.io/terminus/install).

1. Set environment variables for your site name and the environment you're working in (typically will be the "dev" environment)

```bash
export SITE="yourSiteName"
export ENV="dev"
```

2. Change to sftp mode to make changes

```bash
terminus connection:set ${SITE}.${ENV} sftp
```

3. *For Drupal 10 Manual Upgrade only*

Drupal 10.5+ sites in U-M Pantheon that have the older release (v1.0.x) of `umichoidc (wwsauth)` and the dependency of `openid_connect` (8.x-1.x) will need to first uninstall the old versions before proceeding with the installation of the new versions of the modules.

Drupal 10 sites that do *not* have the old versions of the modules installed should skip this step and proceed to [step 4](README.md#step-4-anchor-point).  Drupal 11 sites should also proceed to [step 4](README.md#step-4-anchor-point) as the older versions of these modules will not install on Drupal 11 sites anyway so they will not be present in Drupal 11 sites.

NOTE:  On U-M Pantheon, it is much more difficult to uninstall the old versions and then install & configure the new versions of the modules *AND* propogate those changes from `dev` to `test` and `live` environments, due to the way code changes and database changes are handled via the [Pantheon WebOps Workflow](https://docs.pantheon.io/pantheon-workflow).  

This upgrade requires both code changes as well as database changes.

If your site is hosting on U-M Pantheon and you have the old modules already deployed to `test` and/or `live`, you will likely need to develop a maintenance plan involving downtime for your `live` environment.  If your site is in this particular situation, you may wish to reach out to webmaster@umich.edu for assistance.

To upgrade your Drupal 10 site's `dev` environment:

  a. Confirm that the old modules are installed and activated in the site with the following command:

```bash
terminus drush -n ${SITE}.${ENV} -- pm:list --type=module --no-core
```

  b. Uninstall both modules (`openid_connect`, `wwsauth`)

```bash
terminus drush -n ${SITE}.${ENV} -- pm:uninstall wwsauth openid_connect
```

<a name="step-4-anchor-point"></a>
4. Install & enable **_openid_connect_** and **_umichoidc (wwsauth)_** modules

```bash
terminus composer -n ${SITE}.${ENV} -- require 'drupal/openid_connect:^3.0@alpha' 'its-webhosting/umichoidc:^v3.0@alpha'
terminus drush -n ${SITE}.${ENV} -- pm:enable openid_connect wwsauth
```

5. Configure **_umichoidc (wwsauth)_** module

Obtain the OIDC credentials for your site from the [ITS Web Hosting Services Portal](https://admin.webservices.umich.edu/).

* client_id

```bash
terminus drush -n ${SITE}.${ENV} -- config:set -y openid_connect.client.wwsumich settings.client_id blahblahblah
```

* client_secret

```bash
terminus drush -n ${SITE}.${ENV} -- config:set -y openid_connect.client.wwsumich settings.client_secret blahblahblah
```

* Enable client profile

```bash
terminus drush -n ${SITE}.${ENV} -- config:set -y openid_connect.client.wwsumich status true
```

* Replace default login with OIDC login

```bash
terminus drush -n ${SITE}.${ENV} -- config:set -y openid_connect.settings user_login_display replace
```

6. Connect Administrator account(s) to external OIDC provider

Repeat these steps for each local user account that needs to be connected to the external OIDC provider.

* Get uid for Admin user uniqname

Substitute value for "${uniqname}" into sql statement below:

```bash
terminus drush -n ${SITE}.${ENV} -- sql:query "select uid from users_field_data where name='${uniqname}'"
```

* Add user to **_authmap_** table to connect local user to external OIDC provider

Substitute values for "${uid}" and "${uniqname}" into sql statement below:

```bash
terminus drush -n ${SITE}.${ENV} -- sql:query "INSERT INTO authmap (uid, provider, authname, data) VALUES (${uid}, \"openid_connect.wwsumich\", \"${uniqname}\", \"N;\");"
```

7. Commit changes

```bash
terminus env:commit --message "install/configure openid_connect" --force -- ${SITE}.${ENV}
```

8. Change back to git mode

```bash
terminus connection:set ${SITE}.${ENV} git
```

9. Rebuild cache

```bash
terminus drush -n ${SITE}.${ENV} -- cache:rebuild
```

### Documentation

For more details, refer to [the documentation from the University of Michigan](https://documentation.its.umich.edu/node/5423).


### How can I report an issue, get help, request a feature, or help with module development? {#howtohelp}

[Open a GitHub issue](https://github.com/its-webhosting/umichoidc/issues) or email [webmaster@umich.edu](mailto:webmaster@umich.edu)
