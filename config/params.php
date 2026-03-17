<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    /** Базовый URL Dify Workflow API. */
    'difyApiBaseUrl' => getenv('DIFY_API_BASE_URL') ?: 'https://dify-generator.ru/v1',
    /** API ключ для server-side запросов в Dify. */
    'difyApiKey' => getenv('DIFY_API_KEY') ?: '',
    /** URL для отправки поста при создании (POST JSON). Пусто — не отправлять. */
    'postArticleUrl' => getenv('POST_ARTICLE_URL') ?: '',
    /** Режим браузера для внешнего сервиса публикации. */
    'postArticleHeadless' => filter_var(getenv('HEADLESS') ?: 'false', FILTER_VALIDATE_BOOLEAN),
];
