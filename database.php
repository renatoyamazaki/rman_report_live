<?php

class dbSet {

	// vetor com todos os BDs encontrados no catalogo rman
	public $dbInstArray = array();

	function __construct () {
	}
	
	function __destruct () {
	}

	function addInstance ($hostname, $instance, $dbid, $application, $env) {
		$this->dbInstArray[$dbid] = new dbInst($dbid, $hostname, $instance, $application, $env);
	}

}

class dbInst {
	
	public $dbid;
	public $hostname;
	public $instance;
	public $application;
	public $env;

	function __construct ($dbid, $hostname, $instance, $application, $env) {
		$this->dbid = $dbid;
		$this->hostname = $hostname;
		$this->instance = $instance;
		$this->application = $application;
		$this->env = $env;
	}

	function __destruct () {
	}
}

?>
