# Yii2 Persistent Session

This package provides a simple way to implement persistent sessions, otherwise known as "server-side cookies".


## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Simply add this line:

```
"mipotech/yii2-persistent-session": "*",
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
        //'db' => '...',            // MongoDB application component. Defaults to 'mongodb'
        //'collection' => '...',    // The name of the collection to store the session data. Defaults to 'persistent_session'
        //'cookieClass' => '...'    // The class to used to generate a new cookie. Defaults to 'yii\web\Cookie'
        //'cookieKey' => '...',     // The cookie key to use for identifying the persistent session. Defaults to 'session-id'
        //'cookieParams' => '...',  // The default cookie parameters. Defaults to ['httpOnly' => true, 'secure' => true]
        //'uniqidPrefix' => '...',  // The prefix to use for generating a new session identifier. Defaults to ''
    ]
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

// destroys all data registered to a session.
$persistentSession->destroy();
```

### Accessing Session Data


```php
$persistentSession = Yii::$app->persistentSession;

// get a session variable.
$language = $persistentSession->get('language');

// set a session variable. The following usages are equivalent:
$persistentSession->set('language', 'en-US');

// remove a session variable. The following usages are equivalent:
$persistentSession->remove('language');

// check if a session variable exists. The following usages are equivalent:
if ($persistentSession->has('language')) ...
```
