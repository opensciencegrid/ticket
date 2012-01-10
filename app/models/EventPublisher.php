<?php

class EventPublisher {
    function publish($msg, $key) {

        try {
            // Create a connection
            $cnn = new AMQPConnection();
            $cnn->setHost(config()->event_host);
            $cnn->setLogin(config()->event_user);
            $cnn->setPassword(config()->event_pass);
            $cnn->setVhost(config()->event_vhost);

            $cnn->connect();

            // Declare a new exchange
            $ex = new AMQPExchange($cnn);
            $ex->declare(config()->event_exchange, AMQP_EX_TYPE_TOPIC);
            $ex->publish($msg, $key);

            $cnn->disconnect();
        } catch(Exception $e) {
            elog("Failed to publish event");
            elog($e);
        }
    } 
}
