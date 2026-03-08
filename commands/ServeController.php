<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use Yii;

/**
 * Запуск встроенного PHP-сервера для локальной разработки.
 * Использование: php yii serve [порт]
 * По умолчанию: http://localhost:8080
 */
class ServeController extends Controller
{
    public $port = 8080;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['port']);
    }

    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), ['p' => 'port']);
    }

    public function actionIndex(): int
    {
        $web = Yii::getAlias('@app/web');
        $address = '0.0.0.0:' . (int) $this->port;
        $this->stdout("Сервер запущен: http://localhost:{$this->port}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("Документ-рут: {$web}\n");
        $this->stdout("Остановка: Ctrl+C\n\n");
        passthru(sprintf('php -S %s -t %s', $address, escapeshellarg($web)));
        return ExitCode::OK;
    }
}
