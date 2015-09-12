<?php

namespace app\session;

use app\config\Config;
use app\database\Database;
use app\user\User;
use app\user\UserManager;
use carbon\core\cookie\CookieManager;
use carbon\core\datetime\DateTime;
use carbon\core\util\IpUtils;
use Exception;
use PDO;

// Prevent direct requests to this file due to security reasons
defined('APP_INIT') or die('Access denied!');

class SessionManager {

    /** The database table name. */
    const DB_TABLE_NAME = 'session';
    /** The length of a session key. */
    const SESSION_KEY_LENGTH = 64;
    /** The characters a session key can consist of. */
    const SESSION_KEY_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=[]{}\\|/?<>,.`~';
    /** The time it takes for a session to expire. */
    // TODO: Move this value to the registry database to make it configurable
    const SESSION_EXPIRE = '+1 year';
    /** The name of the session cookie. */
    const SESSION_COOKIE_NAME = 'session_key';

    /** @var Session|null The current session if the user is logged in, null otherwise. */
    private static $currentSession = null;
    private static $currentUser = null;
    private static $activeUser = null;

    /**
     * Get the database table name of the sessions.
     *
     * @return string The database table name.
     */
    public static function getDatabaseTableName() {
        return Config::getValue('database', 'table_prefix', '') . static::DB_TABLE_NAME;
    }

    /**
     * Get a list of all sessions.
     * Note: This method is very resource intensive and expensive to execute.
     *
     * @return array An array of sessions.
     *
     * @throws Exception Throws an exception on failure.
     */
    public static function getSessions() {
        // Create a row count query on the database instance
        $statement = Database::getPDO()->query('SELECT session_id FROM ' . static::getDatabaseTableName());

        // Make sure the query succeed
        if($statement === false)
            throw new Exception('Failed to query the database.');

        // The list of sessions
        $sessions = Array();

        // Return the number of rows
        foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $data)
            $sessions[] = new Session($data['session_id']);

        // Return the list of sessions
        return $sessions;
    }

    /**
     * Get the number of active sessions.
     *
     * @return int Number of active sessions.
     *
     * @throws Exception Throws if an error occurred.
     */
    public static function getSessionCount() {
        // Create a row count query on the database instance
        $statement = Database::getPDO()->query('SELECT session_id FROM ' . static::getDatabaseTableName());

        // Make sure the query succeed
        if($statement === false)
            throw new Exception('Failed to query the database.');

        // Return the number of rows
        return $statement->rowCount();
    }

    /**
     * Check if there's any session with the specified ID.
     *
     * @param int $id The ID of the session to check for.
     *
     * @return bool True if any session exists with this ID.
     *
     * @throws Exception Throws if an error occurred.
     */
    public static function isSessionWithId($id) {
        // Make sure the ID isn't null
        if($id === null)
            throw new Exception('Invalid session ID.');

        // Prepare a query for the database to list sessions with this ID
        $statement = Database::getPDO()->prepare('SELECT session_id FROM ' . static::getDatabaseTableName() . ' WHERE session_id=:id');
        $statement->bindParam(':id', $id, PDO::PARAM_INT);

        // Execute the prepared query
        if(!$statement->execute())
            throw new Exception('Failed to query the database.');

        // Return true if there's any session found with this ID
        return $statement->rowCount() > 0;
    }

    /**
     * Get a session instance by a session key.
     *
     * @param string $key The session key.
     *
     * @return Session|null The session, or null if there's no session with this key.
     *
     * @throws Exception Throws if an error occurred.
     */
    public static function getSessionWithKey($key) {
        // Make sure the key isn't null
        if($key === null)
            throw new Exception('Invalid session key.');

        // Prepare a query for the database to list sessions with this key
        $statement = Database::getPDO()->prepare('SELECT session_id FROM ' . static::getDatabaseTableName() . ' WHERE session_key=:session_key');
        $statement->bindParam(':session_key', $key, PDO::PARAM_STR);

        // Execute the prepared query
        if(!$statement->execute())
            throw new Exception('Failed to query the database.');

        // Return the first session with this key
        foreach($statement->fetchAll(PDO::FETCH_ASSOC) as $data)
            return new Session($data['session_id']);

        // No session found, return null
        return null;
    }

    /**
     * Check whether there's any session with this key.
     *
     * @param string $key The session key.
     *
     * @return bool True if there's any session with this key.
     *
     * @throws \Exception Throws if an error occurred.
     */
    public static function isSessionWithKey($key) {
        return static::getSessionWithKey($key) !== null;
    }

    /**
     * Create a new session for the current user with the specified user.
     *
     * @param User $user The user to create a session for.
     *
     * @return bool True if succeed, false otherwise.
     *
     * @throws Exception Throws if an error occurred.
     */
    public static function createSession($user) {
        // Make sure the user isn't null
        if($user === null)
            throw new Exception('Couldn\'t create session, invalid user!');

        // Generate a random session key
        $sessionKey = static::generateRandomSessionKey();

        // Get the IP of the client
        $sessionIp = IpUtils::getClientIp();

        // Get and determine the session dates
        $sessionDate = DateTime::now();
        $sessionDateExpire = DateTime::parse(static::SESSION_EXPIRE);

        // Prepare a query for the session being created
        $statement = Database::getPDO()->prepare('INSERT INTO ' . static::getDatabaseTableName() .
            ' (session_user_id, session_key, session_create_ip, session_create_datetime, session_expire_datetime) ' .
            'VALUES (:session_user_id, :session_key, :session_create_ip, :session_create_datetime, :session_expire_datetime)');
        $statement->bindValue(':session_user_id', $user->getId(), PDO::PARAM_INT);
        $statement->bindValue(':session_key', $sessionKey, PDO::PARAM_STR);
        $statement->bindValue(':session_create_ip', $sessionIp, PDO::PARAM_STR);
        $statement->bindValue(':session_create_datetime', $sessionDate->toString(), PDO::PARAM_STR);
        $statement->bindValue(':session_expire_datetime', $sessionDateExpire->toString(), PDO::PARAM_STR);

        // Execute the prepared query
        if(!$statement->execute())
            throw new Exception('Failed to query the database.');

        // Set a client cookie with the session key
        CookieManager::setCookie(static::SESSION_COOKIE_NAME, $sessionKey, static::SESSION_EXPIRE);

        // Return the result
        return true;
    }

    /**
     * Validate the session of the current user.
     *
     * @throws Exception Throws if an error occurred.
     */
    public static function validateSession() {
        // Make sure the proper cookie is set
        if(!CookieManager::hasCookie(static::SESSION_COOKIE_NAME)) {
            static::setCurrentSession(null);
            static::removeSession(null, true);
            return;
        }

        // Get the session key
        $sessionKey = CookieManager::getCookie(static::SESSION_COOKIE_NAME);

        // Get a session instance by this session key
        $session = static::getSessionWithKey($sessionKey);

        // Make sure the session is valid and isn't expired
        if($session === null || $session->isExpired()) {
            static::setCurrentSession(null);
            static::removeSession(null, true);
            return;
        }

        // Make sure the user of this session still exists
        if(!UserManager::isUserWithId($session->getUser()->getId()))
            return;

        // Get and store the session
        static::setCurrentSession($session);
    }

    /**
     * Logout from the current user session.
     */
    public static function logoutSession() {
        // Remove the current session
        static::removeSession(static::getLoggedInSession(), true);

        // Set the current session
        static::setCurrentSession(null);
    }

    /**
     * Remove a session.
     *
     * @param Session|null $session Session to remove, or null to don't remove any sessions.
     * @param bool $removeCookie [optional] True to remove the current session key from the user cookies.
     *
     * @throws Exception Throws if an error occurred.
     */
    private static function removeSession($session, $removeCookie = true) {
        // Remove the session from the database
        if($session instanceof Session) {
            // Prepare a query for the session being removed
            $statement = Database::getPDO()->prepare('DELETE FROM ' . static::getDatabaseTableName() . ' WHERE session_id=:session_id');
            $statement->bindValue(':session_id', $session->getId(), PDO::PARAM_INT);

            // Execute the prepared query
            if(!$statement->execute())
                throw new Exception('Failed to query the database.');
        }

        // Reset the session key cookie
        if($removeCookie)
            CookieManager::deleteCookie(static::SESSION_COOKIE_NAME);
    }

    /**
     * Check if the current user is logged in.
     * The validateSession() method must be run once before this method works.
     *
     * @return bool True if the user is logged in, false otherwise.
     */
    public static function isLoggedIn() {
        return static::$currentSession instanceof Session;
    }

    /**
     * Get the session of the current logged in user.
     *
     * @return Session|null Session instance, or null if the user isn't logged in.
     */
    public static function getLoggedInSession() {
        return static::$currentSession;
    }

    /**
     * Set the current session.
     *
     * @param Session $session Current session.
     */
    private static function setCurrentSession($session) {
        // Set the current session
        static::$currentSession = $session;

        // Get and set the user and active user instance if the session isn't null
        if(static::$currentSession !== null) {
            static::$currentUser = $session->getUser();
            static::$activeUser = null;
        } else {
            static::$currentUser = null;
            static::$activeUser = null;
        }

        // TODO: Get the active user!
    }

    /**
     * Get the user of the current logged in user.
     *
     * @return User User of the current logged in user.
     */
    public static function getLoggedInUser() {
        // Get the session
        $session = static::getLoggedInSession();
        if($session === null)
            return null;

        // Return the user
        return $session->getUser();
    }

    /**
     * Get the user that is currently active.
     *
     * @return User User that is currently active.
     */
    public static function getActiveUser() {
        // TODO: Return the active user!
        return new User(536703709);
    }

    /**
     * Generate a random session key.
     *
     * @return string Random session key.
     */
    private static function generateRandomSessionKey() {
        // Get the characters a sessoin key can consist of
        $chars = static::SESSION_KEY_CHARS;

        // Generate a random session key, make sure it doesn't exist
        do {
            $randomKey = '';
            for($i = 0; $i < static::SESSION_KEY_LENGTH; $i++)
                $randomKey .= $chars[rand(0, strlen($chars) - 1)];

            // Check whether this session key exists already
            $exists = static::isSessionWithKey($randomKey);

        } while($exists);

        // Return the random key
        return $randomKey;
    }
}
