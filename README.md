# EC-SI-SisEsxi

PHP web application that lists ESXi hosts and their virtual machines and can power those virtual machines on, off, or reset them. The inventory page title in `inc_header.php` is "Shannon ESXi Inventory". The `version` file in the repository root is `version=0.0.7`.

Host and VM details are read from text snapshots under `content/downloaded/`. Those snapshots are produced by SSHing to each host and running `vim-cmd` (and `vmware -l -v` for the host version). A separate console page embeds the VMware MKS ActiveX control.

The tree also vendors VMware vSphere CLI 4.1.0 (Perl `esxcfg-*` / `vicfg-*` scripts and the vSphere SDK for Perl) under `content/cli/`. The PHP application does not call those scripts. Their copyright, EULA, and open-source license text are under `content/cli/share/doc/vmware-vcli/`.

## Layout

| Path                                             | Role                                                                                                                                                                 |
| ------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `index.php`                                      | Inventory page. Calls `quickList()` once for each host speciality group.                                                                                             |
| `index_console.php`                              | Console page. Sets `$vmconsole` and includes the shared header and footer.                                                                                           |
| `inc_common.php`                                 | Loads the classes, reads the HTTP user, checks group membership, and handles power and annotation requests.                                                          |
| `inc_functions.php`                              | Sets `$libdir` and counts `*.info` snapshots.                                                                                                                        |
| `inc_inventory.php`                              | Renders the inventory, looks up hosts, and implements `sync()`.                                                                                                      |
| `inc_header.php`, `inc_footer.php`               | Page chrome, navigation, and frontend script tags.                                                                                                                   |
| `classes/vmware.class.php`                       | SSH wrapper around remote `vim-cmd` power, summary, device, hardware, network, and annotation calls.                                                                 |
| `classes/ldap.class.php`                         | LDAP connection and user/group lookup.                                                                                                                               |
| `classes/ldapuser.class.php`                     | LDAP person entry (`SieEsxi\LdapUser`).                                                                                                                              |
| `classes/ldapgroup.class.php`                    | LDAP group membership.                                                                                                                                               |
| `content/sync.php`                               | CLI-style entry that includes the classes and calls `sync()`.                                                                                                        |
| `content/downloaded/`                            | Cached host files (`*.hwinfo`, `*.netinfo`, `*.version`) and per-VM files (`*.info`, `*.device`, `*.power`).                                                         |
| `content/js/`, `content/css/`, `content/images/` | Vendored jQuery, jQuery UI, fancyBox, and images. `inc_header.php` loads the copies it references.                                                                   |
| `content/js/intel.js`                            | ActiveX console connect, CD-ROM attach, and power buttons.                                                                                                           |
| `content/activex/vmware-mks.cab`                 | MKS ActiveX control used by the console page.                                                                                                                        |
| `content/.key/`                                  | SSH identity material. The path the `Vmware` class uses is set in `classes/vmware.class.php`.                                                                        |
| `content/cli/`                                   | Vendored vSphere CLI 4.1.0.                                                                                                                                          |
| `content/log.txt`                                | Append-only log opened by `Vmware` when the constructed user is not `root`.                                                                                          |
| `composer.json`, `composer.lock`                 | Composer classmap for `classes/`. The lockfile has no packages. PHP files include classes directly.                                                                  |
| `.github/workflows/main.yml`                     | On push and pull request to `main`, and on a `refresh` `repository_dispatch`, calls `dmzoneill/dmzoneill/.github/workflows/dispatch.yaml@main` with `basic: "true"`. |
| `.github/workflows/ai-responder.yml`             | Comments on opened issues and pull requests.                                                                                                                         |
| `.github/dependabot.yml`                         | Weekly updates for GitHub Actions and Composer.                                                                                                                      |
| `.ignore`                                        | Lists vendored paths (vSphere CLI, jQuery, fancyBox, Composer `vendor/`).                                                                                            |
| `LICENSE`                                        | Apache License 2.0.                                                                                                                                                  |

`editform()` in `inc_inventory.php` prints an annotation form (project, OS, version, architecture, customer). Nothing else in the tree calls it. When the authenticated user is in the group from `inc_common.php`, that file accepts the matching POST fields (`pproject`, `pos`, `pversion`, `parch`, `puser`, `pusercon`, `phost`, `ppath`, `pvmid`) and writes the annotation through `Vmware::setAnnotation()`.

## Dependencies

What the PHP code calls:

- PHP 8.1 or later. `classes/ldapuser.class.php` and `inc_inventory.php` use union types and `\LDAP\Connection`, which PHP provides from 8.1. `composer.json` does not declare a PHP version.
- The PHP LDAP extension (`ldap_connect`, `ldap_bind`, `ldap_search`, `ldap_read`, `ldap_get_entries`).
- `LdapUser::getimage()` uses GD (`imagecreatefromstring`, `imagejpeg`). No other file calls `getimage()`.
- `/usr/bin/ssh`, invoked by `Vmware::remotecall()`.
- `/bin/ucat nodes key speciality`, invoked by `getNodes()`. `ucat` is not in this repository. With no filter, a node is kept when the second field contains `ESX`. With a filter, the second field must equal the filter string.
- `vim-cmd` and `vmware -l -v` on the remote hosts.
- A web server that sets `$_SERVER['PHP_AUTH_USER']`. `inc_common.php` reads that value with no fallback.
- Internet Explorer for the console. `content/js/intel.js` loads the MKS control only when `$.browser.msie` is set, and otherwise prints that only IE supports ActiveX.

LDAP controllers, the bind account, and the bind password are hardcoded in `classes/ldap.class.php`. The group DN checked for power and console access is hardcoded in `inc_common.php`.

## Running

The application root is a single absolute path. It is assigned to `$libdir` in `inc_functions.php` and repeated in `content/sync.php`, `classes/vmware.class.php`, and `inc_header.php`. Some snapshot reads use that path; `getHostHardwareInfo()` and `getHostNetworkInfo()` read `content/downloaded/` relative to the process working directory.

`index.php` is the inventory page. It calls `quickList()` with these speciality filters, which `getNodes()` compares to the `ucat` speciality field:

- `ESX-Server.Infra`
- `ESX-Server,ESX-Server.Standalone`
- `ESX-Server,ESX-Server.Standalone,Lab-Server,Lab-Equipment`
- `ESX-Server,ESX-Server.ilab,Lab-Server,Lab-Equipment`

`index_console.php` is the console page. Query parameters `host` and `vmpath` are base64-encoded; `vmid` is not.

The console and the `reset`, `poweron`, and `poweroff` actions run only when the authenticated user is a member of the group in `inc_common.php`. Otherwise the console page prints `You are not a member of` plus that group name, and exits.

The console prompts for a root password in the browser and connects with the MKS control on port 902.

`content/sync.php` calls `sync()`. That function:

1. Removes `content/temp/*` via `cleanupstaleandlogs()`.
2. For each host from `getNodes()`, writes hardware, network, and version output, then per-VM summary, device, and power output, under `content/temp/`.
3. Replaces `content/downloaded/*` with those files, runs `chmod 770` on them, and `chown`s them to `ec_webadm:webadm`.

`content/temp/` is not in the repository. `sync()` writes files there with `fopen`, so that directory has to exist before a sync. SSH uses the identity file path set in `classes/vmware.class.php` and logs in as `root` on the hostname plus the suffix hardcoded in `Vmware::remotecall()`.

`diskPaths()` returns an empty list. The comment in that function says NFS disk paths are intentionally not configured, so `ddisks()` does not collect disk usage.

## License

Application code is under the Apache License 2.0. See `LICENSE`. The vendored vSphere CLI is covered by the documents in `content/cli/share/doc/vmware-vcli/`.
