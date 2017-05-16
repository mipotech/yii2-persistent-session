# Yii2 Persistent Session

This package provides a simple way to implement persistent sessions, otherwise known as "server-side cookies".

## Installation

This extension requires [MongoDB PHP Extension](http://us1.php.net/manual/en/set.mongodb.php) version 1.0.0 or higher.

This extension requires MongoDB server version 3.0 or higher.

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mipotech/yii2-persistent-session
```

or add

```
"mipotech/yii2-persistent-session": "~1.0"
```

to the require section of your composer.json.


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
