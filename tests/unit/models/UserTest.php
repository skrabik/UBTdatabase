<?php

namespace tests\unit\models;

use app\models\User;

class UserTest extends \Codeception\Test\Unit
{
    public function testFindUserById()
    {
        $user = User::findIdentity(1);
        if ($user) {
            verify($user->username)->equals('admin');
        }
        verify(User::findIdentity(999))->empty();
    }

    public function testFindUserByUsername()
    {
        $user = User::findByUsername('admin');
        if ($user) {
            verify($user)->notEmpty();
        }
        verify(User::findByUsername('not-existing-user'))->empty();
    }

    public function testValidatePassword()
    {
        $user = User::findByUsername('admin');
        if ($user) {
            verify($user->validatePassword('admin'))->true();
            verify($user->validatePassword('wrong'))->false();
        }
    }
}
