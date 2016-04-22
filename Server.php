<?php
	use Ratchet\Server\IoServer;
	use NutagAPI\Nutag;

	require __DIR__.'/vendor/autoload.php';
	require __DIR__.'/src/NutagAPI/config.php';
	
	$server = IoServer::factory(
		new Nutag(),
		4050
	);

	$server->run();
?>