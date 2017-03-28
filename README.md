# Yii2-devlogin

This package provides a simple way to password-protect an entire site,
typically when in dev or test mode.

## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

First add this entry to the `repositories` section of your composer.json:

```
"repositories": [{
    ...
},{
    "type": "git",
    "url": "https://github.com/mipotech/yii2-persistent-session.git"
},{
    ...
}],
```

then add this line:

```
"mipotech/yii2-persistent-session": "dev-master",
```

to the `require` section of your `composer.json` file and perform a composer update.

## Configuration

Add `persistentSession` as an application component in @app/config/web.php:

```php
'components' => [
    ...
    'persistentSession' => [
        /* Required settings */
        'class' => 'mipotech\persistentsession\PersistentSession',
        
        /* Optional settings */
        //'db' => '...',      // MongoDB application component. Defaults to mongodb
    ],
    ...
]
```

That's it. The package is set up and ready to go.

## Usage

The functionality of this component is intended to be as close as possible to native Yii2 sessions ([API documentation](http://www.yiiframework.com/doc-2.0/yii-web-session.html) and [user guide](http://www.yiiframework.com/doc-2.0/guide-runtime-sessions-cookies.html#sessions)).

### Opening and Closing Sessions

```php
$persistentSession = Yii::$app->persistentSession;

// check if a session is already open
if ($persistentSession->isActive) ...

// open a session
$persistentSession->open();

// close a session
$persistentSession->close();

// destroys all data registered to a session.
$persistentSession->destroy();
```

### Accessing Session Data


```php
$persistentSession = Yii::$app->persistentSession;

// get a session variable. The following usages are equivalent:
$language = $persistentSession->get('language');
$language = $persistentSession['language'];

// set a session variable. The following usages are equivalent:
$persistentSession->set('language', 'en-US');
$persistentSession['language'] = 'en-US';

// remove a session variable. The following usages are equivalent:
$persistentSession->remove('language');
unset($persistentSession['language']);

// check if a session variable exists. The following usages are equivalent:
if ($persistentSession->has('language')) ...
if (isset($persistentSession['language'])) ...

// traverse all session variables.
foreach ($persistentSession as $name => $value) ...
```
