<?php

class dbInfo {

	public $rmanInfoSetArray = array();
	public $countObjMax;

	function __construct () {
	}

	function __destruct () {
	}

	function getRmanInfo ($connections, $interval, $mode) {

		foreach ($connections->connArray as $dbid => $connection) {

			// checa se a conexão é válida
			if (!is_bool($connection->dbconn)) {
				$this->rmanInfoSetArray[$dbid] = new rmanInfoSet();
			
				// ID 375386.1
				$stmt = oci_parse($connection->dbconn, "alter session set optimizer_mode=RULE");
				oci_execute($stmt, OCI_DEFAULT);

				$stmt = oci_parse($connection->dbconn, "select session_recid as sessionrecid, status, to_char(min(start_time), 'DD/MM/YYYY HH24:MI:SS') as timestart, command_id, min(start_time) as st from v\$rman_status where start_time >= sysdate-$interval group by session_recid, status, command_id, start_time order by 5 desc");
				oci_execute($stmt, OCI_DEFAULT);

				while (($row = oci_fetch_array($stmt, OCI_BOTH)) != false)
					$this->rmanInfoSetArray[$dbid]->addRmanInfo($row['SESSIONRECID'], $row['STATUS'], $row['TIMESTART'], $row['COMMAND_ID']);
				// realiza uma contagem de quantos objetos o vetor possui
				$this->rmanInfoSetArray[$dbid]->countObj();
			}
		}

		// Informacoes de execucoes com erro
		if ($mode == 0) {
			foreach ($this->rmanInfoSetArray as $dbid => $risa)
				foreach ($risa->rmanInfoArray as $ri)
					if ($ri->status != 'COMPLETED') 
						$this->getRmanError($connections, $dbid, $ri->sessionRecid);				
		}
		
	}

	function getRmanLog ($connections, $dbid, $sessionrecid) {

		$stmt = oci_parse($connections->connArray[$dbid]->dbconn, "select output from v\$rman_output where session_recid = $sessionrecid");
		oci_execute($stmt, OCI_DEFAULT);
		
		$ERROR="RMAN-";
		$ERROR2="ORA-";
		$ERROR3="ANS";

		while (($row = oci_fetch_array($stmt, OCI_BOTH)) != false) {
			if ((strpos($row['OUTPUT'], $ERROR) !== FALSE ) or (strpos($row['OUTPUT'], $ERROR2) !== FALSE) or (strpos($row['OUTPUT'], $ERROR3) !== FALSE ))
				
				echo "<span class='red'><b>" . $row['OUTPUT'] . "</b></span><br/>";
			else
				echo "<span>" . $row['OUTPUT'] . "</span><br/>";
		}	
	}


	function getRmanError ($connections, $dbid, $sessionrecid) {
		
		$stmt = oci_parse($connections->connArray[$dbid]->dbconn, "select output from v\$rman_output where session_recid = $sessionrecid and regexp_like(output, 'RMAN-|ORA-|ANS')");

		oci_execute($stmt, OCI_DEFAULT);

		while (($row = oci_fetch_array($stmt, OCI_BOTH)) != false)
			$errorLog .= $row['OUTPUT'] . "<br>";

		$this->rmanInfoSetArray[$dbid]->addRmanError($sessionrecid, $errorLog);

	}

	function countMax () {

		foreach ($this->rmanInfoSetArray as $risa)
			if ($risa->countObj > $this->countObjMax)
				$this->countObjMax = $risa->countObj;
	}

	function printStruct ($databases, $mode) {

		if ($mode == 0) {
			$GREEN="<td bgcolor='green'>";
			$LGREEN="<td bgcolor='#00CC99'>";
			$RED="<td bgcolor='red'>";
			$LRED="<td bgcolor='#FF3300'>";
			$BLUE="<td bgcolor='blue'>";
			$YELLOW="<td bgcolor='#FFA500'>";
			$LYELLOW="<td bgcolor='#FFFF00'>";
		}
		else {
			$GREEN="<td class='green'>";
			$LGREEN="<td class='lgreen'>";
			$RED="<td class='red'>";
			$LRED="<td class='lred'>";
			$BLUE="<td class='blue'>";
			$YELLOW="<td class='yellow'>";
			$LYELLOW="<td class='lyellow'>";
		}
		
		///////////////////////////////////////////////////////////////

		echo "<table class='sortable' id='matriz' border=1>\n";
		echo "<tr> <th><b>ENV</b></th> <th><b>SYSTEM</b></th> <th><b>HOST</b></th> <th><b>INSTANCE</b></th> <th><b>DBID</b></th>";

		for ($i = 1 ; $i<= $this->countObjMax ; $i++)
			 echo "<th>TIME</th>";
		echo "</tr>\n";

		foreach ($this->rmanInfoSetArray as $dbid => $risa) {
			echo "<tr> <td>" . $databases->dbInstArray[$dbid]->env . "</td> <td>" . $databases->dbInstArray[$dbid]->application . "</td><td>" . $databases->dbInstArray[$dbid]->hostname . "</td><td>" . $databases->dbInstArray[$dbid]->instance . "</td><td>" . $dbid . "</td>";
			
			$i=1;

			foreach ($risa->rmanInfoArray as $ri) {

				$COLOR="<td>";
		
				if ($ri->status == 'COMPLETED') {
					if ($ri->operation == 'level1')
						$COLOR=$LGREEN;
					else {
						if ($ri->operation == 'level0')
							$COLOR=$GREEN;
						else {
							if ($ri->operation == 'level1cron')
								$COLOR=$LYELLOW;
							else if ($ri->operation == 'level0cron')
								$COLOR=$YELLOW;
						}
					}
				}
				else {
					if ($ri->status == 'RUNNING')
						$COLOR=$BLUE;
					else {
						if (($ri->operation == 'level1') or ($ri->operation == 'level1cron'))
							$COLOR=$LRED;
						else if (($ri->operation == 'level0') or ($ri->operation == 'level0cron'))
							$COLOR=$RED;
					}
				}
				if ($mode == 1)
					echo "$COLOR <a href='?DBID=" . $dbid . "&SESSION=" . $ri->sessionRecid . "'>" . $ri->timeStart . " </a> </td>";
				else
					echo "$COLOR" . $ri->timeStart . "</td>";
				$i++;
			}
			for ($j = $i ; $j<= $this->countObjMax ; $j++)
				echo "<td> </td>";

			echo "</tr>\n";
		}
		echo "</table>\n";

		echo "<table>";
		echo "<tr><th colspan=2> Legenda</th></tr>";
		echo "<tr><td>RMAN Level 0 - OK</td> $GREEN &nbsp; &nbsp; &nbsp;</td></tr>";
		echo "<tr><td>RMAN Level 1 - OK</td> $LGREEN &nbsp; &nbsp; &nbsp;</td></tr>";
		echo "<tr><td>RMAN Level 0 - ERROR</td> $RED &nbsp; &nbsp; &nbsp;</td></tr>";
		echo "<tr><td>RMAN Level 1 - ERROR</td> $LRED &nbsp; &nbsp; &nbsp;</td></tr>";
		echo "<tr><td>RMAN - Running</td> $BLUE &nbsp; &nbsp; &nbsp;</td></tr>";
		echo "<tr><td>RMAN Level 0 CRON - OK</td> $YELLOW &nbsp; &nbsp; &nbsp;</td></tr>";
		echo "<tr><td>RMAN Level 1 CRON - OK</td> $LYELLOW &nbsp; &nbsp; &nbsp;</td></tr>";
		echo "</table>";


		if ($mode == 0) {
			$this->printStructError ($databases);
		}

	}

	function printStructError ($databases) {

		echo "<table class='sortable' id='matriz' border=1>";
		echo "<tr><th><b>HOST</b></th> <th><b>INSTANCE</b></th> <th><b>DBID</b></th> <th><b>TIME</b></th> <th><b>ERROR LOG</b></th> </tr>";
		foreach ($this->rmanInfoSetArray as $dbid => $risa) {
			foreach ($risa->rmanInfoArray as $ri) {
				if (($ri->status != 'COMPLETED') and ($ri->status != 'RUNNING')) {
					echo "<tr> <td>" . $databases->dbInstArray[$dbid]->hostname . "</td><td>" . $databases->dbInstArray[$dbid]->instance . "</td><td>" . $dbid . "</td> <td>$ri->timeStart</td> <td>$ri->errorLog</td>  </tr>";
				}
				
			}
		}
		echo "</table>";
	
	}

}

class rmanInfoSet {

	public $rmanInfoArray = array();
	public $countObj;

	function __construct () {
	}
	
	function __destruct () {
		$rmanInfoArray = NULL;
	}

	function addRmanInfo ($sessionRecid, $status, $timeStart, $operation) {
		if (array_key_exists($sessionRecid, $this->rmanInfoArray))
			$this->rmanInfoArray[$sessionRecid]->upInfo($status, $timeStart);
		else
			$this->rmanInfoArray[$sessionRecid] = new rmanInfo($sessionRecid, $status, $timeStart, $operation);
	}

	function addRmanError ($sessionRecid, $errorLog) {
		$this->rmanInfoArray[$sessionRecid]->upError($errorLog);
	}

	function countObj () {
		$this->countObj = count($this->rmanInfoArray);
	}

}

class rmanInfo {
	
	public $sessionRecid;
	public $status;
	public $timeStart;
	public $operation;
	public $errorLog;
	
	function __construct ($sessionRecid, $status, $timeStart, $operation) {
		$this->sessionRecid = $sessionRecid;
		$this->status = $status;
		$this->timeStart = $timeStart;
		$this->operation = $operation;
	}

	function __destruct () {
	}

	function upInfo ($status, $timeStart) {
		// somente se for diferente de rman 'COMPLETED' atualiza o objeto
		if ($status != 'COMPLETED') {
			$this->status = $status;
			$this->timeStart = $timeStart;
		}
	}

	function upError ($errorLog) {
		$this->errorLog = $errorLog;
	}
}

?>
