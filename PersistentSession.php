<?php

namespace mipotech\persistentsession;

use Yii;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\mongodb\Connection;

/**
 *
 * @see  https://github.com/yiisoft/yii2-mongodb/blob/master/Session.php
 * @author Chaim Leichman, MIPO Technologies Ltd
 *
 * Here's the current to do list:
 * @todo Implement \IteratorAggregate
 * @todo Implement \ArrayAccess
 * @todo Implement close() function
 * @todo Implmenet regenerateID() function
 */
class PersistentSession extends \yii\base\Component implements \Countable
{
    /**
     * @var string The application component representing the MongoDB connection
     */
    public $db = 'mongodb';

    /**
     * @var string|array the name of the MongoDB collection that stores the session data.
     * Please refer to [[Connection::getCollection()]] on how to specify this parameter.
     * This collection is better to be pre-created with fields 'id' and 'expire' indexed.
     */
    public $collection = 'persistent_session';

    /**
     *
     * @var string The class to use to generate a new cookie using
     *      Yii::$app->response->cookies->add(...)
     */
    public $cookieClass = 'yii\web\Cookie';

    /**
     *
     * @var string The cookie key to use for identifying the persistent session
     */
    public $cookieKey = 'session-id';

    /**
     * @var array parameter-value pairs to override default session cookie parameters that are used for session_set_cookie_params() function
     * Array may have the following possible keys: 'lifetime', 'path', 'domain', 'secure', 'httponly'
     * @see http://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    public $cookieParams = ['httpOnly' => true, 'secure' => true];

    /**
     *
     * @link http://php.net/manual/en/function.uniqid.php
     * @var string The prefix to use for generating a new session identifier
     */
    public $uniqidPrefix = '';

    /**
     *
     * @var string The session ID
     *      The session ID is stored in a cookie and used as the primary key
     *      of the mongodb record
     */
    protected static $id;


    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[db]] is invalid.
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * Returns the number of items in the session.
     * This method is required by [[\Countable]] interface.
     * @return int number of items in the session.
     */
    public function count(): int
    {
        return $this->getCount();
    }

    /**
     * Frees all session variables and destroys all data registered to a session.
     */
    public function destroy()
    {
        return $this->deleteRecord();
    }

    /**
     * Returns the session variable value with the session variable name.
     * If the session variable does not exist, the `$defaultValue` will be returned.
     * @param string $key the session variable name
     * @param mixed $defaultValue the default value to be returned when the session variable does not exist.
     * @return mixed the session variable value, or $defaultValue if the session variable does not exist.
     */
    public function get(string $key, $defaultValue = null)
    {
        $this->open();
        if ($rec = $this->getRecord()) {
            return $rec[0][$key] ?? $defaultValue;
        }
        return $defaultValue;
    }

    /**
     * Returns the number of items in the session.
     * @return int the number of session variables
     */
    public function getCount(): int
    {
        $this->open();
        $count = 0;
        if ($rec = $this->getRecord()) {
            $count = count($rec[0]) - 1; // don't count the primary key field
        }
        return $count;
    }

    /**
     * Gets the session ID.
     * This is a wrapper for [PHP session_id()](http://php.net/manual/en/function.session-id.php).
     * @return string the current session ID
     */
    public function getId(): string
    {
        if (empty(static::$id) && $this->getIsActive()) {
            static::$id = Yii::$app->request->cookies->get($this->cookieKey);
        }
        return static::$id;
    }

    /**
     * Checks if there is an active key for this session
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return Yii::$app->request->cookies->has($this->cookieKey) || !empty(static::$id);
    }

    /**
     * @param mixed $key session variable name
     * @return bool whether there is the named session variable
     */
    public function has(string $key): bool
    {
        $this->open();
        if ($rec = $this->getRecord()) {
            return isset($rec[0][$key]);
        }
        return false;
    }

    /**
     * Assert that the persistent session cookie exists, and create if not
     *
     */
    public function open()
    {
        if ($this->getIsActive()) {
            return;
        }

        $sessionId = uniqid($this->uniqidPrefix, true);  // generate a random 23-character identifier
        $cookieParams = ArrayHelper::merge(['name' => $this->cookieKey, 'value' => $sessionId], $this->cookieParams);
        if (!isset($cookieParams['expire'])) {
            $cookieParams['expire'] = time() + 3600 * 24 * 365 * 5;
        }
        Yii::$app->response->cookies->add(new $this->cookieClass($cookieParams));
        $this->setId($sessionId);
    }

    /**
     * Removes a session variable.
     * @link http://www.yiiframework.com/forum/index.php/topic/72072-delete-a-field-of-document-of-mongodb-using-active-record-while-updating-a-document/
     * @param string $key the name of the session variable to be removed
     * @return mixed the removed value, null if no such session variable.
     */
    public function remove(string $key)
    {
        $this->open();
        $id = $this->getId();
        return Yii::$app->mongodb->getCollection($this->collection)
            ->update(['_id' => $id], ['$unset' => [$key => '']]);
    }

    /**
     * Removes all session variables from this record
     */
    public function removeAll()
    {
        $this->open();
        $this->deleteRecord();
        $this->createRecord();
    }

    /**
     * Adds a session variable.
     * If the specified name already exists, the old value will be overwritten.
     * @param string $key session variable name
     * @param mixed $value session variable value
     */
    public function set(string $key, $value)
    {
        $this->open();
        if($record = $this->getRecord()) {
            return $this->updateRecord([$key => $value]);
        } else {
            return $this->createRecord([$key => $value]);
        }
    }

    /**
     * Sets the session cookie parameters.
     * The cookie parameters passed to this method will be merged with the result
     * of `session_get_cookie_params()`.
     * @param array $value cookie parameters, valid keys include: `lifetime`, `path`, `domain`, `secure` and `httponly`.
     * @throws InvalidParamException if the parameters are incomplete.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    public function setCookieParams(array $params)
    {
        $this->cookieParams = ArrayHelper::merge($this->cookieParams, $params);
    }

    /**
     * Sets the session ID.
     * This is a wrapper for [PHP session_id()](http://php.net/manual/en/function.session-id.php).
     * @param string $value the session ID for the current session
     */
    public function setId(string $value)
    {
        static::$id = $value;
    }


    /**
     * Create a new persistent session record and optionally add new data
     * at the same time.
     *
     * @param array $recordData
     */
    protected function createRecord(array $recordData = [])
    {
        $this->open();
        $id = $this->getId();
        $doc = ArrayHelper::merge(['_id' => $id], $recordData);
        return $this->db->createCommand()->insert($this->collection, $doc);
    }

    /**
     *
     * @return MongoDB\Driver\WriteResult
     */
    protected function deleteRecord()
    {
        $this->open();
        $id = $this->getId();
        return $this->db->createCommand()->delete($this->collection, ['_id' => $id]);
    }

    /**
     *
     * @return array
     */
    protected function getRecord(): array
    {
        $this->open();
        $id = $this->getId();
        return $this->db->createCommand()->find($this->collection, ['_id' => $id])->toArray();
    }

    /**
     *
     * @link http://www.yiiframework.com/doc-2.0/yii-mongodb-command.html#update()-detail
     * @param array $recordData
     * @return \MongoDB\Driver\WriteResult
     */
    protected function updateRecord(array $recordData)
    {
        $this->open();
        $id = $this->getId();
        $condition = ['_id' => $id];
        return $this->db->createCommand()->update($this->collection, $condition, $recordData);
    }
}
