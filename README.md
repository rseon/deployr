Deployr!
========

Simply copy files from source to destination using rsync.

## Simple usage
* Create (and protect) a folder accessible from Internet (example : `_secure23x45`).
* Create a file (example : `deploy.php`) and add this content :

```php
require '../vendor/autoload.php'; // If installed with composer
// require '../deployr/src/autoload.php'; // If installed without composer

$deployr = new Deployr\Application('mysupersecretkey'); // Change the key !
$deployr->setOptions([
    'allowed_ip' => ['127.0.0.1', '::1', 'MY.SUP.ER.IP'],
]);
$deployr->run();
```

* Access to `https://my-website.com/_secure23x45/deploy.php?access_key=mysupersecretkey`, review files and publish them !

At first run, the database will be created and assets copied into new folder.

You will be asked to set the settings. Be careful with folder path and excluded files. By default, the following paths are excluded :

- The path of this tool (for example `/_secure23x45`)
- `/node_modules/` and `/vendor/` (because of large amount of files.)


### Notice
__Don't use this tool for first deployment !__

If you add folders or files in excluded paths, like storage path, it will not be created even thought it is necessary.


## Installation

Pre-requisite : PHP 7, rsync and correct rights on folders.

### With composer
```
composer install rseon/deployr
```

Add this line at start of your deployer file : `require '../vendor/autoload.php';`

### Without composer
* Download this repository as ZIP
* Upload it on your server 
* Dezip file at root of your project
* Add this line at start of your deployer file : `require '../deployr/src/autoload.php';`


## Methods

| Name | Description |
|------|-------------|
| `run()` | Run the application |
| `setOptions(array $options)` | Set options to the application |


## Options

| Name | Description | Default |
|------|-------------|---------|
| `access_key_name` | The name of the GET parameter | `access_key` |
| `allowed_ip` | Allow access for only these IP addresses | `['127.0.0.1', '::1']` |


## Diving deeper

### Localization

Feel free to add your translations into the `src/i18n` folder adding JSON file (named as your lang).

The format is simple : `{ "My string" : "My translated string" }`

Then add it into the file `src/i18n/available.json`
