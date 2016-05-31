<?php

namespace Devlabs\App;

/**
 * Class User
 * @package Devlabs\App
 */
class User
{
    public $id;
    public $firstName;
    public $lastName;
    public $email;
    public $password;
    public $passwordConfirm;
    public $passwordHash;
    public $selected = '';

    public function __construct($id = null, $firstName = null, $lastName = null, $email = null, $selected = '')
    {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->selected = $selected;
    }

    /**
     * Load user data from database by passing an email address
     *
     * @param $email
     */
    public function loadByEmail($email)
    {
        $query = $GLOBALS['db']->query(
            "SELECT * FROM users WHERE email = :email",
            array('email' => $email)
        );

        if ($query) {
            $this->id = $query[0]['id'];
            $this->firstName = $query[0]['first_name'];
            $this->lastName = $query[0]['last_name'];
            $this->email = $query[0]['email'];
        }
    }

    /**
     * Load user data from database by passing a token
     *
     * @param Token $token
     */
    public function loadByToken(Token $token)
    {
        $query = $GLOBALS['db']->query(
            "SELECT * FROM users JOIN tokens ON tokens.user_id = users.id AND tokens.value = :token_value",
            array('token_value' => $token->value)
        );

        if ($query) {
            $this->id = $query[0]['id'];
            $this->firstName = $query[0]['first_name'];
            $this->lastName = $query[0]['last_name'];
            $this->email = $query[0]['email'];
        }
    }

    /**
     * Method for adding a new user into the database
     *
     * @return mixed
     */
    public function insert()
    {
        $query = $GLOBALS['db']->query(
            "INSERT IGNORE INTO users(first_name,last_name,email,password_hash)
                VALUES(:first_name, :last_name, :email, :password_hash)",
            array('first_name' => $this->firstName, 'last_name' => $this->lastName, 'email' => $this->email,
                'password_hash' => password_hash($this->password, PASSWORD_DEFAULT))
        );

        $this->loadByEmail($this->email);
    }

    /**
     * Method for removing a user from the database
     *
     * @return mixed
     */
    public function remove()
    {
        return $GLOBALS['db']->query(
            "DELETE FROM users WHERE email = :email",
            array('email' => $this->email)
        );
    }

    /**
     * Change a user's first name in the database
     *
     * @param $firstName
     * @return mixed
     */
    public function changeFirstName($firstName)
    {
        return $GLOBALS['db']->query(
            "UPDATE users SET first_name = :first_name WHERE id = :id",
            array('id' => $this->id, 'first_name' => $firstName)
        );
    }

    /**
     * Change a user's last name in the database
     *
     * @param $lastName
     * @return mixed
     */
    public function changeLastName($lastName)
    {
        return $GLOBALS['db']->query(
            "UPDATE users SET last_name = :last_name WHERE id = :id",
            array('id' => $this->id, 'last_name' => $lastName)
        );
    }

    /**
     * Change a user's password in the database
     *
     * @param $password
     * @return mixed
     */
    public function changePassword($password)
    {
        return $GLOBALS['db']->query(
            "UPDATE users SET password_hash = :password_hash WHERE id = :id",
            array('id' => $this->id, 'password_hash' => password_hash($password, PASSWORD_DEFAULT))
        );
    }

    /**
     * Set a user as confirmed in the database
     *
     * @return mixed
     */
    public function setConfirmed()
    {
        return $GLOBALS['db']->query(
            "UPDATE users SET confirmed = 1 WHERE email = :email",
            array('email' => $this->email)
        );
    }

    /**
     * Check if user present in the database
     *
     * @return mixed
     */
    public function lookup()
    {
        return $GLOBALS['db']->query(
            "SELECT * FROM users WHERE email = :email",
            array('email' => $this->email)
        );
    }

    /**
     * Check if user email and password have a match in the database
     *
     * @return bool
     */
    public function checkCredentials()
    {
        $query = $GLOBALS['db']->query(
            "SELECT * FROM users WHERE email = :email AND confirmed = 1",
            array('email' => $this->email)
        );

        if ($query) {
            $password_hash = $query[0]['password_hash'];

            return password_verify($this->password, $password_hash);
        } else {
            return false;
        }
    }
}