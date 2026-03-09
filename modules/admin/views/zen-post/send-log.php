<?php
/** @var yii\web\View $this */
/** @var int $postId */
/** @var string $indexUrl */
/** @var string $statusUrl */
/** @var array $payload */

use yii\helpers\Html;

$this->title = 'Результат отправки поста';
$statusUrlJs = json_encode($statusUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$initialPayloadJs = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<div class="zen-post-send-log">
    <h1>Результат отправки поста #<?= Html::encode((string) $postId) ?></h1>
    <p id="remote-publish-status" class="text-muted">Ожидаем выполнение job...</p>
    <p id="remote-publish-message" class="text-muted"></p>
    <div id="remote-publish-logs" class="border rounded p-3 bg-light small" style="min-height: 240px; max-height: 60vh; overflow-y: auto; font-family: Consolas, Monaco, monospace; white-space: pre-wrap;"></div>
    <p class="mt-3">
        <a class="btn btn-primary" href="<?= Html::encode($indexUrl) ?>">К списку постов</a>
    </p>
</div>
<?php
$this->registerJs(<<<JS
(function () {
    var statusEl = document.getElementById('remote-publish-status');
    var messageEl = document.getElementById('remote-publish-message');
    var logsEl = document.getElementById('remote-publish-logs');
    var statusUrl = {$statusUrlJs};
    var timerId = null;

    function setLines(lines) {
        logsEl.innerHTML = '';
        if (!Array.isArray(lines) || lines.length === 0) {
            logsEl.innerHTML = '<div class="text-muted">Логи пока отсутствуют.</div>';
            return;
        }
        lines.forEach(function (line) {
            var row = document.createElement('div');
            row.className = 'mb-1';
            row.textContent = line;
            logsEl.appendChild(row);
        });
    }

    function render(data) {
        statusEl.textContent = 'Статус: ' + (data.queue_status_label || data.queue_status || '—');
        messageEl.textContent = data.message || '';
        setLines(data.logs || []);
    }

    function refresh() {
        fetch(statusUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(function (data) {
            render(data);
            if (!data.is_finished && timerId !== null) {
                timerId = window.setTimeout(refresh, 2000);
            }
        })
        .catch(function (error) {
            statusEl.textContent = 'Ошибка обновления статуса';
            messageEl.textContent = error.message;
        });
    }

    render({$initialPayloadJs});
    if (!({$initialPayloadJs}).is_finished) {
        timerId = window.setTimeout(refresh, 1000);
    }
})();
JS
);
