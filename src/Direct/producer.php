<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Garante que a exchange esteja declarada
$channel->exchange_declare('notificacao_exchange', 'direct', false, true, false);

for ($i = 1; $i <= 10; $i++) {
    $data = ['user_id' => $i, 'mensagem' => "Mensagem número $i", 'is_client' => rand(0, 1), 'user_age' => rand(5, 30)];
    $msg = new AMQPMessage(json_encode($data), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
    $channel->basic_publish($msg, 'notificacao_exchange', 'notificacao_key');
    echo "[✓] Enviada: Mensagem número $i\n";
}

$channel->close();
$connection->close();
