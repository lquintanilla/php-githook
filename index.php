<?php
/**
 * GitHub webhook handler template.
 * 
 * @see  https://developer.github.com/webhooks/
 * @author  Miloslav Hůla (https://github.com/milo)
 */

function execute($cmd, $workdir = null) {

    if (is_null($workdir)) {
        $workdir = __DIR__;
    }

    $descriptorspec = array(
       0 => array("pipe", "r"),  // stdin
       1 => array("pipe", "w"),  // stdout
       2 => array("pipe", "w"),  // stderr
    );

    $process = proc_open($cmd, $descriptorspec, $pipes, $workdir, null);

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    return [
        'code' => proc_close($process),
        'out' => trim($stdout),
        'err' => trim($stderr),
    ];
}

$hookSecret = 'HAKq49n83nd8Fk9s';  # set NULL to disable check
set_error_handler(function($severity, $message, $file, $line) {
	throw new \ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
	header('HTTP/1.1 500 Internal Server Error');
	echo "Error on line {$e->getLine()}: " . htmlSpecialChars($e->getMessage());
	die();
});
$rawPost = NULL;
if ($hookSecret !== NULL) {
	if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE'])) {
		throw new \Exception("HTTP header 'X-Hub-Signature' is missing.");
	} elseif (!extension_loaded('hash')) {
		throw new \Exception("Missing 'hash' extension to check the secret code validity.");
	}
	list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
	if (!in_array($algo, hash_algos(), TRUE)) {
		throw new \Exception("Hash algorithm '$algo' is not supported.");
	}
	$rawPost = file_get_contents('php://input');
	if ($hash !== hash_hmac($algo, $rawPost, $hookSecret)) {
		throw new \Exception('Hook secret does not match.');
	}
};
if (!isset($_SERVER['CONTENT_TYPE'])) {
	throw new \Exception("Missing HTTP 'Content-Type' header.");
} elseif (!isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
	throw new \Exception("Missing HTTP 'X-Github-Event' header.");
}
switch ($_SERVER['CONTENT_TYPE']) {
	case 'application/json':
		$json = $rawPost ?: file_get_contents('php://input');
		break;
	case 'application/x-www-form-urlencoded':
		$json = $_POST['payload'];
		break;
	default:
		throw new \Exception("Unsupported content type: $_SERVER[HTTP_CONTENT_TYPE]");
}
# Payload structure depends on triggered event
# https://developer.github.com/v3/activity/events/types/
$payload = json_decode($json);
switch (strtolower($_SERVER['HTTP_X_GITHUB_EVENT'])) {
	case 'ping':
		echo 'pong';
		break;
	case 'pull_request':
		echo "pull request/ " ;
		if( $payload->action == "closed" ){
			echo "closed/ ";
			if( $payload->pull_request->merged == true ){
                        echo "merged/ ";
			$repo = $payload->repository->name;
                        $base = $payload->pull_request->base->ref;
                            switch ($repo){
                                case 'webapp':
                                    echo 'webapp/ ';
                                    $authorized_branch = 'staging';
                                    if($base == $authorized_branch){
                                        echo $base.'/ ';
                                        $res = execute('git pull origin '.$authorized_branch,'/home/ubuntu/staging-webapp/helpdev.faveomoves.com');
                                        echo $res['code'].'/ ';
                                        echo $res['out'].'/ ';
                                        echo $res['err'].'/ ';
                                    }else{
                                        echo "Ref $base received. Doing nothing: only the $authorized_branch branch may be deployed on this server.";
                                    }
                                    break;
                                case 'landing_onboarding':
                                    echo 'landing_onboarding/ ';
                                    $authorized_branch = 'master';
                                    if($base == $authorized_branch){
                                        echo $base.'/ ';
                                        $res = execute('git pull origin '.$authorized_branch,'/home/ubuntu/staging-landing/drupaldev.faveomoves.com/onboarding');
                                        echo $res['code'].'/ ';
                                        echo $res['out'].'/ ';
                                        echo $res['err'].'/ ';
                                    }else{
                                        echo "Ref $base received. Doing nothing: only the $authorized_branch branch may be deployed on this server.";
                                    }
                                    break;    
                                case 'faveo-landing':
                                    echo 'faveo-landing/ ';
                                    $authorized_branch = 'master';
                                    if($base == $authorized_branch){
                                        echo $base.'/ ';
                                        $res = execute('git pull origin '.$authorized_branch,'/home/ubuntu/staging-landing/landing.faveomoves.com/wp-content/themes');
                                        echo $res['code'].'/ ';
                                        echo $res['out'].'/ ';
                                        echo $res['err'].'/ ';

                                    }else{
                                        echo "Ref $base received. Doing nothing: only the $authorized_branch branch may be deployed on this server.";
                                    }
                                    break;
				case 'FaveoApp':
                                    echo 'faveo-landing/ ';
                                    $authorized_branch = 'staging';
                                    if($base == $authorized_branch){
                                        echo $base.'/ ';
                                        $res = execute('git pull origin '.$authorized_branch,'/home/ubuntu/staging-backend/backdev.faveomoves.com');
                                        echo $res['code'].'/ ';
                                        echo $res['out'].'/ ';
                                        echo $res['err'].'/ ';
					

                                    }else{
                                        echo "Ref $base received. Doing nothing: only the $authorized_branch branch may be deployed on this server.";
                                    }
                                    break;

                                default :
                                echo 'Not Pulled to server';    
                            }
                        }else{
                            echo 'Not Pulled to server';    
                        }
		}
//		echo "Event:$_SERVER[HTTP_X_GITHUB_EVENT] Payload:\n";
//                print_r($payload); # For debug only. Can be found in GitHub hook log.
		
		break;
//	case 'creat:
//		break;
	default:
		header('HTTP/1.0 404 Not Found');
		echo "Event:$_SERVER[HTTP_X_GITHUB_EVENT] Payload:\n";
		print_r($payload); # For debug only. Can be found in GitHub hook log.
		die();
}
