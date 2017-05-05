<?php
	
	if ( isset($_POST['interval']) and is_numeric($_POST['interval']) ) {
		$interval = $_POST['interval'];
	}
	else
		$interval = 1;

	if ( isset($_GET['mode']) && ($_GET['mode'] == 'cli') )
		$mode = 0;
	else {	
		$mode = 1;
		require_once('../lib/class/execTime.php');
		$e = new execTime();
	}

?>
<html>

<head>
	<title>Oracle - RMAN Backup Report</title>

	<link rel="stylesheet" href="../css/common.css" type="text/css" />
	<script type="text/javascript" src="sortable.js"></script>
	
</head>

<body>

<div id ="geral">

<h1>RMAN Backup Report - <?php echo $interval;  ?> dia(s)</h1>



<?php
	require_once "connection.php";
	require_once "database.php";
	require_once "rmaninfo.php";

	$databases = new dbSet();
	$infos = new dbInfo();

/*
	$catalogo = new conn("sd005dtc", "CAT11G");
	$stmt = oci_parse($catalogo->dbconn, "select a.hostname, b.name, a.dbid from rman.metro_hosts a join rman.rc_database b on a.dbid = b.dbid order by a.hostname");
	oci_execute($stmt, OCI_DEFAULT);
	// adiciona cada database encontrada no catalogo no objeto da classe dbSet
	while (($row = oci_fetch_array($stmt, OCI_BOTH)) != false) 
		$databases->addInstance($row['HOSTNAME'], $row['NAME'], $row['DBID']);	
*/

	$catalogo = new conn("sd043cld", "CATRMAN", TRUE);
	$stmt = oci_parse($catalogo->dbconn, "select a.hostname, b.name, a.dbid, a.application, a.env from rman.metro_hosts a join rman.rc_database b on a.dbid = b.dbid order by a.application, a.hostname");
	oci_execute($stmt, OCI_DEFAULT);

	// adiciona cada database encontrada no catalogo no objeto da classe dbSet
	while (($row = oci_fetch_array($stmt, OCI_BOTH)) != false) 
		$databases->addInstance($row['HOSTNAME'], $row['NAME'], $row['DBID'], $row['APPLICATION'], $row['ENV']);



	// realiza conexoes com todas as instancias encontradas no catalogo
	$connections = new connSet($databases);

	if (isset($_GET['DBID']) and isset($_GET['SESSION'])) {
		if (is_numeric($_GET['DBID']) and is_numeric($_GET['SESSION'])) {
			$DBID = htmlentities($_GET['DBID']);
			$SESSIONRECID = htmlentities($_GET['SESSION']);
			$infos->getRmanLog($connections, $DBID, $SESSIONRECID);
		}
	}
	else {

		if ($mode == 1) {
?>
<form action='<?php echo htmlentities($_SERVER['PHP_SELF']); ?>' method="post">
Intervalo <select name="interval">
<option value="1">1 dia</option>
<option value="3">3 dias</option>
<option value="7">7 dias</option>
</select>
<input type="submit" value="OK" />
</form>
<?php
		}
		$infos->getRmanInfo($connections, $interval, $mode);
		$infos->countMax();
		$infos->printStruct($databases, $mode);
	}

?>
<div id='push'>
</div>

</div>

</body>
</html>
