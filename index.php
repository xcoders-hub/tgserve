<?php
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';
//include 'vendor/autoload.php';
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Socket\Server;
use Psr\Log\NullLogger;

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$Mmsgid= 'BQACAgUAAxUHYoaeqpXDTkhwShi-zDb7eIPHr5AAAmAGAAIqoTBUHCxEZq_Kev4gBA';
$MadelineProto->start();
$me = $MadelineProto->getSelf();
$MadelineProto->logger($me);
?>