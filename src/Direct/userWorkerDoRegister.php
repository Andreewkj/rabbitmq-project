<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('notificacao_exchange', 'direct', false, true, false);
$channel->queue_declare('usuarios_fila', false, true, false, false);
$channel->queue_bind('usuarios_fila', 'notificacao_exchange', 'notificacao_key');

$callback = function (AMQPMessage $msg) use ($channel) {
    $body = $msg->getBody();
    $dados = json_decode($body, true);
    echo " [>] User com ID: {$dados['user_id']}, registrado com sucesso\n";
    // Aqui você faria a atualização no estoque
    $msg->ack();
};

$channel->basic_consume('usuarios_fila', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}