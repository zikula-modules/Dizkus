Zikula Dizkus Forums
====================

Dizkus is a discussion forum module for the Zikula Application Framework

This version **requires** Zikula Core 1.4.3+

The code is currently under development, but typically is in a functional
state. Feel free to test and report issues. thank you.

Before you pull:

  1. uninstall and delete the module.
  2. delete your local repo and all the files.
  3. clone the repo into `/modules/Dizkus/`

Your directory structure should look like so:

```
  /modules
      /zikula
          /dizkus-module
            /Api
            /Block
            etc...
```

UPGRADING from Dizkus 3.1
-------------------------

you must add to `personal_config.php`:

`$ZConfig['System']['prefix'] = 'pn';`

(or whatever your table prefix is from your old installation)

note that upgrading can take a long time and may require updating your `.htaccess` or `php.ini` file to increase
time limits and memory allowed.