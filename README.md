# Deployr

Simply copy files from source to destination using rsync.


## Installation

Pre-requisite : PHP 7, rsync, PDO Sqlite and correct rights on folders.

```
composer install rseon/deployr
```

## Simple usage
* Create (and protect) a folder accessible from Internet (example : `_secure23x45`).
* Create a file (example : `deploy.php`) and add this content :

```php
require '../../vendor/autoload.php';

$deployr = new Deployr\Application('mysupersecretkey'); // Change the key !
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
| `key` | **required** The secure key used to access to the script from internet | - |
| `param_key` | The name of the GET parameter | `access_key` |
| `database` | Absolute path to the database file | `./deployr.db` |
| `restrict_ip` | Restrict access to these IP | `['127.0.0.1', '::1']` |


## Diving deeper

### Localization

Feel free to add your translations into the `src/Deployr/i18n` folder adding JSON file (named as your lang).
The format is simple : `{ "My string" : "My translated string" }`
Then add it into the file `src/Deployr/i18n/available.json`
