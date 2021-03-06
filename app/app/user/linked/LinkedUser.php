<?php

namespace app\user\linked;

use app\database\Database;
use app\session\SessionManager;
use app\user\User;
use carbon\core\datetime\DateTime;
use Exception;
use PDO;

// Prevent direct requests to this file due to security reasons
defined('APP_INIT') or die('Access denied!');

class LinkedUser {

    /** @var int The linked user ID. */
    private $id;

    /**
     * Constructor.
     *
     * @param int $id Linked user ID.
     */
    public function __construct($id) {
        $this->id = $id;
    }

    /**
     * Get the linked user ID.
     *
     * @return int The linked user ID.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get a value from the database from this specific linked user.
     *
     * @param string $columnName The column name.
     *
     * @return mixed The value.
     *
     * @throws Exception Throws if an error occurred.
     */
    private function getDatabaseValue($columnName) {
        // Prepare a query for the database to list users with this ID
        $statement = Database::getPDO()->prepare('SELECT ' . $columnName . ' FROM ' . LinkedUserManager::getDatabaseTableName() . ' WHERE linked_id=:linked_id');
        $statement->bindParam(':linked_id', $this->id, PDO::PARAM_INT);

        // Execute the prepared query
        if(!$statement->execute())
            throw new Exception('Failed to query the database.');

        // Return the result
        return $statement->fetch(PDO::FETCH_ASSOC)[$columnName];
    }

    /**
     * Get the user ID of the owner.
     *
     * @return int User ID of the owner.
     *
     * @throws Exception Throws an exception if an error occurred.
     */
    public function getOwnerId() {
        return $this->getDatabaseValue('linked_owner_user_id');
    }

    /**
     * Get the user.
     *
     * @return User Linked User user.
     *
     * @throws Exception Throws an exception if an error occurred.
     */
    public function getOwner() {
        return new User($this->getOwnerId());
    }

    /**
     * Check whether a specific user is the owner.
     *
     * @param user $owner The user to check for.
     *
     * @return bool
     * @throws Exception
     */
    public function isOwner($owner) {
        // Make sure the owner is valid
        if(!($owner instanceof User))
            throw new Exception('Invalid user instance.');

        // Check whether this is the owner, return the result
        return $this->getOwnerId() === $owner->getId();
    }

    /**
     * Get the user ID of the linked user.
     *
     * @return int User ID of the linked user.
     *
     * @throws Exception Throws an exception if an error occurred.
     */
    public function getUserId() {
        return $this->getDatabaseValue('linked_user_id');
    }

    /**
     * Get the linked user.
     *
     * @return User Linked user.
     *
     * @throws Exception Throws an exception if an error occurred.
     */
    public function getUser() {
        return new User($this->getUserId());
    }

    /**
     * Check whether a specific user is the user.
     *
     * @param user $user The user to check for.
     *
     * @return bool
     * @throws Exception
     */
    public function isUser($user) {
        // Make sure the user is valid
        if(!($user instanceof User))
            throw new Exception('Invalid user instance.');

        // Check whether this is the user, return the result
        return $this->getUserId() === $user->getId();
    }

    /**
     * Get the raw linked user creation date.
     *
     * @return string Raw linked user creation date.
     *
     * @throws Exception Throws an exception if an error occurred.
     */
    public function getCreationDateTimeRaw() {
        return $this->getDatabaseValue('linked_creation_datetime');
    }

    /**
     * Get the linked user creation date.
     *
     * @return DateTime Linked user creation date.
     *
     * @throws Exception Throws an exception if an error occurred.
     */
    public function getCreationDateTime() {
        // TODO: Use the proper timezone!
        return new DateTime($this->getCreationDateTimeRaw());
    }

    /**
     * Get the raw linked user last usage date.
     *
     * @return DateTime Raw linked user last usage date.
     *
     * @throws Exception Throws an exception if an error occurred.
     */
    public function getLastUsageDateTimeRaw() {
        return $this->getDatabaseValue('linked_usage_datetime');
    }

    /**
     * Get the linked user last usage date.
     *
     * @return DateTime Linked user last usage date.
     *
     * @throws Exception Throws an exception if an error occurred.
     */
    public function getLastUsageDateTime() {
        // TODO: Use the proper timezone!
        return new DateTime($this->getLastUsageDateTimeRaw());
    }

    /**
     * Delete this linked user.
     *
     * @throws Exception Throws if an error occurred.
     */
    public function delete() {
        // Check whether this user has this linked user selected as active, reset the active user if that's the case
        if(SessionManager::getActiveUser()->getId() === $this->getUserId())
            SessionManager::setActiveUser(null, true);

        // Prepare a query for the linked user being deleted
        $statement = Database::getPDO()->prepare('DELETE FROM ' . LinkedUserManager::getDatabaseTableName() . ' WHERE linked_id=:linked_id');
        $statement->bindValue(':linked_id', $this->getId(), PDO::PARAM_INT);

        // Execute the prepared query
        if(!$statement->execute())
            throw new Exception('Failed to query the database.');
    }
}
