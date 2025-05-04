<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$workerId = $argv[1] ?? uniqid('worker_');

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Garante a existência da exchange
$channel->exchange_declare('notificacao_exchange', 'direct', false, true, false);

// Declara a fila principal (deve bater com a do producer)
$channel->queue_declare('notificacao_fila', false, true, false, false);
$channel->queue_bind('notificacao_fila', 'notificacao_exchange', 'notificacao_key');

echo " [*] Worker {$workerId} aguardando mensagens...\n";

$callback = function (AMQPMessage $msg) use ($workerId) {
    echo " [{$workerId}] Recebido: " . $msg->body . "\n";
    sleep(1); // Simula processamento
    $msg->ack();
};

$channel->basic_consume('notificacao_fila', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
