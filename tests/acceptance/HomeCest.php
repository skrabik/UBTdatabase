<?php

use yii\helpers\Url;

class HomeCest
{
    public function ensureThatRootRedirectsToLogin(AcceptanceTester $I)
    {
        $I->amOnPage(Url::toRoute('/'));
        $I->seeInCurrentUrl('login');
        $I->see('Вход', 'h1');
    }
}
