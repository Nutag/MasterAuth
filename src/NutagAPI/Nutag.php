<?php
	namespace NutagAPI;
	use Ratchet\MessageComponentInterface;
	use Ratchet\ConnectionInterface;

	class Nutag implements MessageComponentInterface 
	{
		protected $clients;
		protected $ldap_server = null;

	    public function __construct() {
	        $this->clients = new \SplObjectStorage;
	    }

	    public function onOpen(ConnectionInterface $conn) {
	        $this->clients->attach($conn);

	        echo "New connection! ({$conn->resourceId})\n";
	    }

	    public function onMessage(ConnectionInterface $from, $msg) {
	    	$msg = trim($msg);
	    	$msg = json_decode($msg);

	    	if((isset($msg->access_token)) && ($msg->access_token == PRIVATE_KEY)) {
	    		$this->ldap_server = $from;
	    		$from->send(json_encode(array("type"=>"success")));
	    	}

	    	if ($from != $this->ldap_server) {
	    		if($this->ldap_server != null) {
	    			if (isset($msg->user) && isset($msg->pass)) {
	    				$aut_infos = json_encode(array("user"=>$msg->user, "pass"=>$msg->pass, "client"=>$from->resourceId));
	    				$this->ldap_server->send(trim($aut_infos));
	    			}
	    		} else {
	    			$from->send(json_encode(array("type"=>"error", "msg"=>"ldap_not_connected")));
	    		}
	    	} else {
	    		if(isset($msg->type) && $msg->type=="success") {
	    			foreach ($this->clients as $client) {
	    				if($client->resourceId == $msg->client) {
	    					$user_infos = json_encode(array("name" => $msg->name, "mail"=> $msg->mail));
	    					$client->send(trim($user_infos));
	    				}
	    			}
	    		} else {
	    			if(isset($msg->client)) {
	    				foreach ($this->clients as $client) {
	    					if($client->resourceId == $msg->client) {
	    						$msg = json_encode(array("type"=>"error", "msg"=>"user_auth_fail"));
	    						$client->send(trim($msg));
	    					}
	    				}
	    			}
	    		}
	    	}
	    
	    }

	    public function onClose(ConnectionInterface $conn) {
	        $this->clients->detach($conn);
	        echo "Connection {$conn->resourceId} has disconnected\n";
	    }

	    public function onError(ConnectionInterface $conn, \Exception $e) {
	        echo "An error has occurred: {$e->getMessage()}\n";
	        $conn->close();
	    }
	
}
?>