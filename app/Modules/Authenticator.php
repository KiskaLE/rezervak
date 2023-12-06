<?php

namespace App\Modules;

use Nette;
use Nette\Security\SimpleIdentity;
use Ramsey\Uuid\Uuid;

final class Authenticator implements Nette\Security\Authenticator
{
    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\Passwords $passwords,
    )
    {
    }

    /**
     * Authenticates a user with the given username and password.
     *
     * @param string $username The username of the user to authenticate.
     * @param string $password The password of the user to authenticate.
     * @return SimpleIdentity The authenticated user's identity.
     * @throws Nette\Security\AuthenticationException If the user is not found or the password is invalid.
     */
    public function authenticate(string $username, string $password): SimpleIdentity
    {
        $row = $this->database->table('users')
            ->where('username', $username)
            ->fetch();
        if (!$row) {
            throw new Nette\Security\AuthenticationException('User not found.');
        }

        if (!$this->passwords->verify($password, $row->password)) {
            throw new Nette\Security\AuthenticationException('Invalid password.');
        }

        return new SimpleIdentity(
            $row->id,
            $row->role,
            ['name' => $row->username],
        );
    }

    /**
     * Creates a new user with the given username and password.
     *
     * @param string $username The username for the new user.
     * @param string $password The password for the new user.
     * @return string The username of the newly created user.
     * @throws Some_Exception_Class If there is an error during the user creation process.
     */
    public function createUser($username, $password): string
    {
        $passwordHash = $this->passwords->hash($password);
        $database = $this->database;
        //TODO verify user
        try {
            $database->transaction(function ($database) use ($username, $passwordHash) {
                //create user
                $uuid = Uuid::uuid4();
                $userRow = $database->table("users")->insert([
                    "username" => $username,
                    "password" => $passwordHash,
                    "uuid" => $uuid,
                    "role" => "ADMIN",
                ]);
                $user_id = $userRow->id;
                //create user settings
                $settingsRow = $database->table("settings")->insert([
                    "user_id" => $user_id
                ]);
                $settings_id = $settingsRow->id;
                //create workinghours
                for ($i = 0; $i < 7; $i++) {
                    $database->table("workinghours")->insert([
                        "weekday" => $i,
                        "user_id" => $user_id
                    ]);
                }
            });
        } catch (\Throwable $te) {
            return false;
        }
        return true;
    }
}