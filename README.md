Deployr!
========

Simply copy files from source to destination using rsync.


## Installation

Pre-requisite : PHP 7, rsync and correct rights on folders.

```
composer install rseon/deployr
```

## Simple usage
* Create (and protect) a folder accessible from Internet (example : `_secure23x45`).
* Create a file (example : `deploy.php`) and add this content :

```php
require '../../vendor/autoload.php';

$deployr = new Deployr\Application('mysupersecretkey'); // Change the key !
$deployr->setOptions([
    'allowed_ip' => ['127.0.0.1', '::1', 'MY.SUP.ER.IP'],
]);
$deployr->run();
```

* Access to `https://my-website.com/_secure23x45/deploy.php?access_key=mysupersecretkey`, review files and publish them !

At first run, the database will be created and assets copied into new folder.


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
