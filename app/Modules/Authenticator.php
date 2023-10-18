<?php
namespace App\Modules;

use Nette;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Symfony\Thanks\Command\FundCommand;

final class Authenticator implements Nette\Security\Authenticator {
    public function __construct(
        private Nette\Database\Explorer $database,
        private Nette\Security\Passwords $passwords,
    ) {
    }
    public function authenticate(string $username, string $password): SimpleIdentity {
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
    public function createUser($username, $password): string {
        $passwordHash = $this->passwords->hash($password);

        $this->database->table("users")->insert([
            "username" => $username,
            "password" => $passwordHash
        ]);
        return $username;
    }
}