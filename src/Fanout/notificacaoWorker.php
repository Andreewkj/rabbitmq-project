<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('evento_exchange', 'fanout', false, true, false);
$channel->queue_declare('notificacao_fila', false, true, false, false);
$channel->queue_bind('notificacao_fila', 'evento_exchange');

$callback = function (AMQPMessage $msg) {
    $data = json_decode($msg->getBody(), true);
    echo " [Notificação] Enviando para cliente sobre o produto ID: {$data['produto_id']}\n";
    $msg->ack();
};

$channel->basic_consume('notificacao_fila', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
