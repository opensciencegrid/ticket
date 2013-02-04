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

            //connect and get channel
            $cnn->connect();
            $ch = new AMQPChannel($cnn);

            // Declare a new exchange
            $ex = new AMQPExchange($ch);
            $ex->setName(config()->event_exchange);
            $ex->setType(AMQP_EX_TYPE_TOPIC);
            $ex->declare();
            $ex->publish($msg, $key);

            $cnn->disconnect();
        } catch(Exception $e) {
            elog("Failed to publish event");
            elog($e);
        }
    } 
}
