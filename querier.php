<?php
	include("macro.php");

	$connect=mysql_connect(DB_ADDRESS, DB_USER, DB_PASSWORD);
	if(!$connect){
		exit("Error! Connect MYSQL failure.");
	}
	$db = mysql_select_db(DB_NAME);
	if(!$db){
		exit("Error! Select database failure.");
	}
	$query = "SELECT `chair_name` from ".DB_TABLE.
			" where `seat_surface`=".$_GET["surface"].
			" and `backrest`=".$_GET["backrest"].
			" and `back_bracket`=".$_GET["backBracket"].
			" and `headrest`=".$_GET["headrest"].
			" and `armrest`=".$_GET["armrest"].
			" and `legs`=".$_GET["legs"];
	$result = mysql_query( $query );
	if( !$result ){
		exit("Error! Execute query failure.");
	}
	else{
		$row = mysql_fetch_array( $result );
		$chairName = htmlspecialchars($row["chair_name"]);
	}
	header("Location: gen3d.php?chairName=".$chairName);
?>