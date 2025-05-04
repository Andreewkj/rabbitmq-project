<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Exchange tipo fanout
$channel->exchange_declare('evento_exchange', 'fanout', false, true, false);

for ($i = 1; $i <= 5; $i++) {
    $data = ['produto_id' => $i, 'mensagem' => "Produto vendido #$i"];
    $msg = new AMQPMessage(json_encode($data), ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'evento_exchange');
    echo "[✓] Mensagem enviada: Produto vendido #$i\n";
}

$channel->close();
$connection->close();
