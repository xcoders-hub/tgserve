<?php
set_time_limit(0);
ini_set('log_errors', 0);
@ini_set('display_errors', 0);
@ini_set('implicit_flush', 1); 
@ini_set('zlib.output_compression', 0);
if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

function no_cache($status, $wut)
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    http_response_code($status);
    echo $wut;
    die;
}

//settings to connect telegram}
$MadelineProto = new \danog\MadelineProto\API('session.madeline');

$MadelineProto->start();

$me = $MadelineProto->get_self();

\danog\MadelineProto\Logger::log($me);

$message = $MadelineProto->channels->getMessages(['channel'=> '@csdbch', 'id' => [$_GET['id']] ]);


if ($_SERVER['REQUEST_URI'] == '/') {
    header("HTTP/1.1 418 I'm a teapot");
    //analytics(true, '/', null, $dbuser, $dbpassword);
    exit('<html><h1>418 I&apos;m a teapot.</h1><br><p>My little teapot, my little teapot, oooh oooh oooh oooh...</p></html>');
}
try {
    $servefile = $_SERVER['REQUEST_METHOD'] !== 'HEAD';
    $homedir = __DIR__.'/';
    $pwrhomedir = __DIR__.'/';
    $file_path = urldecode(preg_replace("/^\/*/", '', $_SERVER['REQUEST_URI']));

    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (count($range) == 1) {
            $range[1] = '';
        }
        list($size_unit, $range_orig) = $range;
        if ($size_unit == 'bytes') {
            //multiple ranges could be specified at the same time, but for simplicity only serve the first range
            //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
            $list = explode(',', $range_orig, 2);
            if (count($list) == 1) {
                $list[1] = '';
            }
            list($range, $extra_ranges) = $list;
        } else {
            $range = '';
            //analytics(false, $file_path, null, $dbuser, $dbpassword);
            no_cache(416, '<html><body><h1>416 Requested Range Not Satisfiable.</h1><br><p>Could not use selected range.</p></body></html>');
        }
    } else {
        $range = '';
    }
    $listseek = explode('-', $range, 2);
    if (count($listseek) == 1) {
        $listseek[1] = '';
    }
    list($seek_start, $seek_end) = $listseek;
    $seek_end = empty($seek_end) ? ($message['messages'][0]['media']['document']['size'] - 1) : min(abs(intval($seek_end)), $message['messages'][0]['media']['document']['size'] - 1);
    if (!empty($seek_start) && $seek_end < abs(intval($seek_start))) {
        //analytics(false, $file_path, null, $dbuser, $dbpassword);
        no_cache(416, '<html><body><h1>416 Requested Range Not Satisfiable.</h1><br><p>Could not use selected range.</p></body></html>');
    }
    $seek_start = empty($seek_start) ? 0 : abs(intval($seek_start));
    if ($servefile) {
        $MadelineProto->API->getting_state = true;
        if ($seek_start > 0 || $seek_end < $message['messages'][0]['media']['document']['size'] - 1) {
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$message['messages'][0]['media']['document']['size']);
            header('Content-Length: '.($seek_end - $seek_start + 1));
        } else {
            header('Content-Length: '.$message['messages'][0]['media']['document']['size']);
        }
        header('Content-Type: '.$message['messages'][0]['media']['document']['mime_type']);
        header('Cache-Control: max-age=31556926;');
        header('Content-Transfer-Encoding: Binary');
        header('Accept-Ranges: bytes');
        $MadelineProto->download_to_stream($message['messages'][0]['media']['document'], fopen('php://output', 'w'), function ($percent) {
            flush();
            ob_flush();
            \danog\MadelineProto\Logger::log('Download status: '.$percent.'%');
        }, $seek_start, $seek_end + 1);
        ////analytics(true, $file_path, $MadelineProto->get_self()['id'], $dbuser, $dbpassword);
        $MadelineProto->API->getting_state = false;
        $MadelineProto->API->store_db([], true);
        $MadelineProto->API->reset_session();
    } else {
        if ($seek_start > 0 || $seek_end < $message['messages'][0]['media']['document']['size'] - 1) {
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$message['messages'][0]['media']['document']['size']);
            header('Content-Length: '.($seek_end - $seek_start + 1));
        } else {
            header('Content-Length: '.$message['messages'][0]['media']['document']['size']);
        }
        header('Content-Type: '.$message['messages'][0]['media']['document']['mime_type']);
        header('Cache-Control: max-age=31556926;');
        header('Content-Transfer-Encoding: Binary');
        header('Accept-Ranges: bytes');
        //analytics(true, $file_path, null, $dbuser, $dbpassword);
        header('Content-Disposition: attachment; filename="image.jpg"');
        
    }
} catch (\danog\MadelineProto\ResponseException $e) {
    error_log('Exception thrown: '.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()));
    error_log($e->getTLTrace());
    //analytics(false, $file_path, null, $dbuser, $dbpassword);
    no_cache(500, '<html><body><h1>500 internal server error</h1><br><p>'.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()).'</p></body></html>');
} catch (\danog\MadelineProto\Exception $e) {
    error_log('Exception thrown: '.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()));
    error_log($e->getTLTrace());
    //analytics(false, $file_path, null, $dbuser, $dbpassword);
    no_cache(500, '<html><body><h1>500 internal server error</h1><br><p>'.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()).'</p></body></html>');
} catch (\danog\MadelineProto\RPCErrorException $e) {
    if (in_array($e->rpc, ['AUTH_KEY_UNREGISTERED', 'SESSION_REVOKED', 'USER_DEACTIVATED'])) {
        foreach (glob($madeline.'*') as $file) {
            unlink($file);
        }
        if (isset($MadelineProto)) {
            $MadelineProto->session = null;
        }
        //analytics(false, $file_path, null, $dbuser, $dbpassword);
        no_cache(500, '<html><body><h1>500 internal server error</h1><br><p>The token/session was revoked</p></body></html>');
    }
    error_log('Exception thrown: '.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()));
    error_log($e->getTLTrace());
    //analytics(false, $file_path, null, $dbuser, $dbpassword);
    no_cache(500, '<html><body><h1>500 internal server error</h1><br><p>Telegram said: '.$e->getMessage().'</p></body></html>');
} catch (\danog\MadelineProto\TL\Exception $e) {
    error_log('Exception thrown: '.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()));
    error_log($e->getTLTrace());
    //analytics(false, $file_path, null, $dbuser, $dbpassword);
    no_cache(500, '<html><body><h1>500 internal server error</h1><br><p>'.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()).'</p></body></html>');
} catch (\PDOException $e) {
    error_log('Exception thrown: '.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()));
    error_log($e->getTLTrace());
    //analytics(false, $file_path, null, $dbuser, $dbpassword);
    no_cache(500, '<html><body><h1>500 internal server error</h1><br><p>'.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()).'</p></body></html>');
} catch (\Exception $e) {
    error_log('Exception thrown: '.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()));
    error_log($e->getTLTrace());
    //analytics(false, $file_path, null, $dbuser, $dbpassword);
    no_cache(500, '<html><body><h1>500 internal server error</h1><br><p>'.$e->getMessage().' on line '.$e->getLine().' of '.basename($e->getFile()).'</p></body></html>');
}