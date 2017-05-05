<?php
/*
	function errorHandler($errno, $errstr, $errfile, $errline) {
		throw new Exception($errstr, $errno);
	}
	set_error_handler('errorHandler');
*/


class connSet {
	
	public $connArray = array();

	function __construct ($dbSet) {
		foreach ($dbSet->dbInstArray as $dia) 
			$this->connArray[$dia->dbid] = new conn ($dia->hostname, $dia->instance);
	}

	function __destruct () {
		$this->connArray = NULL;
	}
}

class conn {

	// variavel que contem o objeto de conexao
	public $dbconn;

	function __construct ($hostname, $instance, $catalog = NULL) {
		
		if ($instance != 'PAR')
			$tns = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = ".$hostname.")(PORT = 1521)))(CONNECT_DATA=(SERVICE_NAME=".$instance."))) ";
		else
			$tns = "(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = ".$hostname.")(PORT = 1523)))(CONNECT_DATA=(SERVICE_NAME=".$instance."))) ";
		try {
			$this->dbconn = @oci_connect('rman_report', 'password', $tns);
		}
		catch (PDOException $e) {
			echo "=======================";
			echo "$hostname / $instance";
			echo $e->getMessage();
			echo "=======================";
			exit ();
		}
	}

	function __destruct () {
		$this->dbconn = NULL;
	}
}

?>
