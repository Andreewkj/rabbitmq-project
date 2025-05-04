<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Declaração das Exchanges e Filas
$channel->exchange_declare('notificacao_exchange', 'direct', false, true, false);

// Dead Letter Queue
$channel->queue_declare('notificacao_dlq', false, true, false, false);

// Retry com delay de 10 segundos
$channel->queue_declare('notificacao_retry_fila', false, true, false, false, false, [
    'x-dead-letter-exchange'    => ['S', 'notificacao_exchange'],
    'x-dead-letter-routing-key' => ['S', 'notificacao_key'],
    'x-message-ttl'             => ['I', 10000],
]);

// Fila principal com redirecionamento para retry
$channel->queue_declare('notificacao_fila', false, true, false, false, false, [
    'x-dead-letter-exchange'    => ['S', ''],
    'x-dead-letter-routing-key' => ['S', 'notificacao_retry_fila'],
]);

$channel->queue_bind('notificacao_fila', 'notificacao_exchange', 'notificacao_key');

// Consumer
echo " [*] Aguardando mensagens. Para sair, CTRL+C\n";

$callback = function (AMQPMessage $msg) use ($channel) {
    $body = $msg->getBody();
    $dados = json_decode($body, true);
    $headers = $msg->get('application_headers');
    $tentativas = $headers?->getNativeData()['x-retries'] ?? 0;

    echo " [>] Recebido (tentativa {$tentativas}): {$body}\n";

    try {
        // Simula falha
        if (($dados['user_id'] ?? null) !== 123) {
            throw new Exception('Erro ao processar notificação');
        }

        echo " [✓] Processado com sucesso!\n";
        $msg->ack();

    } catch (Throwable $e) {
        echo " [!] Erro: {$e->getMessage()}\n";

        if ($tentativas >= 2) {
            echo " [x] Enviando para a DLQ...\n";

            $novaMsg = new AMQPMessage($body, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);

            // Envia diretamente para a fila da DLQ
            $channel->basic_publish($novaMsg, '', 'notificacao_dlq');
            $msg->ack();
        } else {
            echo " [~] Reenviando para fila de retry...\n";

            $novaMsg = new AMQPMessage($body, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => new \PhpAmqpLib\Wire\AMQPTable([
                    'x-retries' => $tentativas + 1
                ])
            ]);

            // Publica na exchange com a mesma routing key usada na fila principal
            $channel->basic_publish($novaMsg, 'notificacao_exchange', 'notificacao_key');
            $msg->ack();
        }
    }
};

$channel->basic_consume('notificacao_fila', '', false, false, false, false, $callback);

// Loop de escuta
while ($channel->is_consuming()) {
    $channel->wait();
}
