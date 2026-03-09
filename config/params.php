<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    /** URL для отправки поста при создании (POST JSON). Пусто — не отправлять. */
    'postArticleUrl' => getenv('POST_ARTICLE_URL') ?: '',
    /** Режим браузера для внешнего сервиса публикации. */
    'postArticleHeadless' => filter_var(getenv('HEADLESS') ?: 'false', FILTER_VALIDATE_BOOLEAN),
];
