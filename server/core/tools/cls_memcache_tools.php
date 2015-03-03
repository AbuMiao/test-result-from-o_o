<?php
namespace core\tools;

$config_data = parse_ini_file('config/memcache.ini');
\Flight::register("mem_cache", "core\\tools\\ClsMemcacheTools", array($config_data['ip'], $config_data['port']), function($mem){
	$mem->connect();
});

class ClsMemcacheTools{
	public function __construct($ip, $port=11211)
	{
		$this->mem = new \Memcache();
		$this->ip = $ip;
		$this->port = $port;
	}

	public function connect(){
		$this->mem->connect($this->ip, $this->port);
	}
}

?>