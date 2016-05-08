<?php
	
	include("macro.php");
	
	$urlParam = urldecode($_SERVER["QUERY_STRING"]);
	$param = explode("=", $urlParam);
	if(strlen($param[1]) > 0){
		$seg   = explode(",",$param[1]);
	}
	if(strlen($param[3]) > 0){
		$quad  = explode(",",$param[3]);
	}
	if(strlen($param[5]) > 0){
		$oval  = explode(",",$param[5]);
	}
	$segCnt  = 0;
	$quadCnt = 0;
	$ovalCnt = 0;
	
	// components of chair
	$seatSurface = array();
	$backrest    = array();
	$headrest    = array();
	$armrest     = array();
	$legs        = array();
	$armBracket  = array();
	$backBracket = array();
	$legBracket  = array();
	
	// geometric centre point array of elements
	$centrePoint = array();
	
	// get segment set
	for( $i=0; $i < count($seg); $i+=4 ){
		$segCnt++ ;
		$segSet[$i/4] = array("sx"=>$seg[$i], "sy"=>(1000-$seg[$i+1]), "ex"=>$seg[$i+2], "ey"=>(1000-$seg[$i+3]));
	}
	
	// get quad set
	for( $i=0; $i < count($quad); $i+=8 ){
		$quadCnt++ ;
		$quadSet[ $segCnt+$i/8 ] = array("p1x"=>$quad[$i], "p1y"=>(1000-$quad[$i+1]), "p2x"=>$quad[$i+2], "p2y"=>(1000-$quad[$i+3]), "p3x"=>$quad[$i+4], "p3y"=>(1000-$quad[$i+5]), "p4x"=>$quad[$i+6], "p4y"=>(1000-$quad[$i+7]));
	}
	
	// get ellipse set
	for( $i=0; $i < count($oval); $i+=4 ){
		$ovalCnt++ ;
		$ovalSet[ $segCnt+$quadCnt+$i/4 ] = array("x1"=>$oval[$i], "y1"=>(1000-$oval[$i+1]), "x2"=>$oval[$i+2], "y2"=>(1000-$oval[$i+3]));
	}
		
	// get the geometric centre of element
	function geoCentre( $elementID ){
		global $segCnt;
		global $quadCnt;
		global $segSet;
		global $quadSet;
		global $ovalSet;
		$geoCen = array();
		if( $elementID < $segCnt ){
			$geoCen["x"] = ( $segSet[$elementID]["sx"] + $segSet[$elementID]["ex"] ) / 2;
			$geoCen["y"] = ( $segSet[$elementID]["sy"] + $segSet[$elementID]["ey"] ) / 2;
		}
		else if( $elementID < $segCnt + $quadCnt ){
			if( $quadSet[$elementID]["p1x"] == $quadSet[$elementID]["p3x"] ){
				$k2 = ( $quadSet[$elementID]["p2y"] - $quadSet[$elementID]["p4y"] ) / ( $quadSet[$elementID]["p2x"] - $quadSet[$elementID]["p4x"] );
				$c2 = $quadSet[$elementID]["p2y"] - $quadSet[$elementID]["p2x"] * $k2;
				$geoCen["x"] = $quadSet[$elementID]["p1x"];
				$geoCen["y"] = $k2 * $geoCen["x"] + $c2;
			}
			else if( $quadSet[$elementID]["p2x"] == $quadSet[$elementID]["p4x"] ){
				$k1 = ( $quadSet[$elementID]["p1y"] - $quadSet[$elementID]["p3y"] ) / ( $quadSet[$elementID]["p1x"] - $quadSet[$elementID]["p3x"] );
				$c1 = $quadSet[$elementID]["p1y"] - $quadSet[$elementID]["p1x"] * $k1;
				$geoCen["x"] = $quadSet[$elementID]["p2x"];
				$geoCen["y"] = $k1 * $geoCen["x"] + $c1;
			}
			else{
				$k1 = ( $quadSet[$elementID]["p1y"] - $quadSet[$elementID]["p3y"] ) / ( $quadSet[$elementID]["p1x"] - $quadSet[$elementID]["p3x"] );
				$k2 = ( $quadSet[$elementID]["p2y"] - $quadSet[$elementID]["p4y"] ) / ( $quadSet[$elementID]["p2x"] - $quadSet[$elementID]["p4x"] );
				$c1 = $quadSet[$elementID]["p1y"] - $quadSet[$elementID]["p1x"] * $k1;
				$c2 = $quadSet[$elementID]["p2y"] - $quadSet[$elementID]["p2x"] * $k2;
				$geoCen["x"] = ( $c2 - $c1 ) / ( $k1 - $k2 );
				$geoCen["y"] = $k1 * $geoCen["x"] + $c1;
			}
		}
		else{
			$geoCen["x"] = ( $ovalSet[$elementID]["x1"] + $ovalSet[$elementID]["x2"] ) / 2;
			$geoCen["y"] = ( $ovalSet[$elementID]["y1"] + $ovalSet[$elementID]["y2"] ) / 2;
		}
		return $geoCen;
	}
	
	for( $i=0; $i<$segCnt+$quadCnt+$ovalCnt; $i++ ){
		$centrePoint[$i] = geoCentre( $i );
	}
	
	// divide wireframe into three parts horizontally
	$minY = PHP_INT_MAX;
	$maxY = -PHP_INT_MAX;
	for( $i=0; $i<$segCnt; $i++ ){
		$minY = min( $minY, $segSet[$i]["sy"], $segSet[$i]["ey"] );
		$maxY = max( $maxY, $segSet[$i]["sy"], $segSet[$i]["ey"] );
	}
	for( $i=$segCnt; $i<$segCnt+$quadCnt; $i++ ){
		$minY = min( $minY, $quadSet[$i]["p1y"], $quadSet[$i]["p2y"], $quadSet[$i]["p3y"], $quadSet[$i]["p4y"] );
		$maxY = max( $maxY, $quadSet[$i]["p1y"], $quadSet[$i]["p2y"], $quadSet[$i]["p3y"], $quadSet[$i]["p4y"] );
	}
	for( $i=$segCnt+$quadCnt; $i<$segCnt+$quadCnt+$ovalCnt; $i++ ){
		$minY = min( $minY, $ovalSet[$i]["y1"], $ovalSet[$i]["y2"] );
		$maxY = max( $maxY, $ovalSet[$i]["y1"], $ovalSet[$i]["y2"] );
	}
	$hr1 = $minY + ( $maxY - $minY ) / 3;
	$hr2 = $maxY - ( $maxY - $minY ) / 3;

	// get the points between hr1 and hr2
	function getEffectivePoints( $x1, $y1, $x2, $y2 ){
		global $hr1;
		global $hr2;
		$points = array();
		if( $y1 > $hr2 && $y2 < $hr2 ){
			if($y2 >= $hr1){
				if( $x1 == $x2 ){
					array_push( $points, $x1, $hr2 );
				}
				else{
					$k = ($y2-$y1) / ($x2-$x1);
					$c = $y1 - $x1*$k;
					array_push( $points, ($hr2-$c)/$k, $hr2 );
				}
			}
			else{
				if( $x1 == $x2 ){
					array_push( $points, $x1, $hr2, $x1, $hr1 );
				}
				else{
					$k = ($y2-$y1) / ($x2-$x1);
					$c = $y1 - $x1*$k;
					array_push( $points, ($hr2-$c)/$k, $hr2, ($hr1-$c)/$k, $hr1 );
				}
			}
		}
		else if( $y1 == $hr2 && $y2 < $hr1 ){
			array_push( $points, $x1, $y1 );
			if( $x1 == $x2 ){
				array_push( $points, $x1, $hr1 );
			}
			else{
				$k = ($y2-$y1) / ($x2-$x1);
				$c = $y1 - $x1*$k;
				array_push( $points, ($hr1-$c)/$k, $hr1 );
			}
		}
		else if( $y1 > $hr1 && $y1 < $hr2 && ( $y2 > $hr2 || $y2 < $hr1 ) ){
			array_push( $points, $x1, $y1 );
			if( $y2 > $hr2 ){
				if( $x1 == $x2 ){
					array_push( $points, $x1, $hr2 );
				}
				else{
					$k = ($y2-$y1) / ($x2-$x1);
					$c = $y1 - $x1*$k;
					array_push( $points, ($hr2-$c)/$k, $hr2 );
				}
			}
			else{
				if( $x1 == $x2 ){
					array_push( $points, $x1, $hr2 );
				}
				else{
					$k = ($y2-$y1) / ($x2-$x1);
					$c = $y1 - $x1*$k;
					array_push( $points, ($hr1-$c)/$k, $hr1 );
				}
			}
		}
		else if( $y1 == $hr1 && $y2 > $hr2 ){
			array_push( $points, $x1, $y1 );
			if( $x1 == $x2 ){
				array_push( $points, $x1, $hr2 );
			}
			else{
				$k = ($y2-$y1) / ($x2-$x1);
				$c = $y1 - $x1*$k;
				array_push( $points, ($hr2-$c)/$k, $hr2 );
			}
		}
		else if( $y1 < $hr1 && $y2 > $hr1 ){
			if($y2 <= $hr2){
				if( $x1 == $x2 ){
					array_push( $points, $x1, $hr1 );
				}
				else{
					$k = ($y2-$y1) / ($x2-$x1);
					$c = $y1 - $x1*$k;
					array_push( $points, ($hr1-$c)/$k, $hr1 );
				}
			}
			else{
				if( $x1 == $x2 ){
					array_push( $points, $x1, $hr1, $x1, $hr2 );
				}
				else{
					$k = ($y2-$y1) / ($x2-$x1);
					$c = $y1 - $x1*$k;
					array_push( $points, ($hr1-$c)/$k, $hr1, ($hr2-$c)/$k, $hr2 );
				}
			}
		}
		return $points;
	}
	
	// get the effective area (the area between $hr1 and $hr2) of element
	function effectiveArea( $elementID ){
		global $hr1;
		global $hr2;
		global $segCnt;
		global $quadCnt;
		global $quadSet;
		global $ovalSet;
		if( $elementID < $segCnt ){
			$effArea = 0;
		}
		else if( $elementID < $segCnt + $quadCnt ){
			$effPoints = array();
			$partPoints = getEffectivePoints( $quadSet[$elementID]["p1x"], $quadSet[$elementID]["p1y"], $quadSet[$elementID]["p2x"], $quadSet[$elementID]["p2y"] );
			for( $i=0; $i<count($partPoints); $i++ ){
				array_push( $effPoints, $partPoints[$i] );
			}
			$partPoints = getEffectivePoints( $quadSet[$elementID]["p2x"], $quadSet[$elementID]["p2y"], $quadSet[$elementID]["p3x"], $quadSet[$elementID]["p3y"] );
			for( $i=0; $i<count($partPoints); $i++ ){
				array_push( $effPoints, $partPoints[$i] );
			}
			$partPoints = getEffectivePoints( $quadSet[$elementID]["p3x"], $quadSet[$elementID]["p3y"], $quadSet[$elementID]["p4x"], $quadSet[$elementID]["p4y"] );
			for( $i=0; $i<count($partPoints); $i++ ){
				array_push( $effPoints, $partPoints[$i] );
			}
			$partPoints = getEffectivePoints( $quadSet[$elementID]["p4x"], $quadSet[$elementID]["p4y"], $quadSet[$elementID]["p1x"], $quadSet[$elementID]["p1y"] );
			for( $i=0; $i<count($partPoints); $i++ ){
				array_push( $effPoints, $partPoints[$i] );
			}
			$effArea = 0;
			for( $i=2; $i<count($effPoints)/2; $i++ ){
				$effArea += ( abs( ( $effPoints[2*($i-1)] - $effPoints[0] )*( $effPoints[2*$i+1] - $effPoints[1] ) ) + abs( ( $effPoints[2*$i] - $effPoints[0] )*( $effPoints[2*($i-1)+1] - $effPoints[1] ) ) ) / 2;
			}
		}
		else{
			$y1 = min($ovalSet[$elementID]["y1"], $ovalSet[$elementID]["y2"]);
			$y2 = max($ovalSet[$elementID]["y1"], $ovalSet[$elementID]["y2"]);
			$a = abs($ovalSet[$elementID]["x2"] - $ovalSet[$elementID]["x1"]) / 2;
			$b = abs($ovalSet[$elementID]["y2"] - $ovalSet[$elementID]["y1"]) / 2;
			if( $y1>=$hr1 && $y2<=$hr2 ){
				$effArea = $a*$b*pi();
			}
			else if( $y1>=$hr1 && $y2>$hr2 ){
				$my2 = abs($hr2 - ($ovalSet[$elementID]["y1"] + $ovalSet[$elementID]["y2"])/2);
				$mx2 = sqrt( $a*$a*(1-($my2*$my2)/($b*$b)) );
				$effArea = $a*$b*pi()/2 + $mx2*$my2 + $a*$b*acos( $mx2/$a );
			}
			else if( $y1<$hr1 && $y2<=$hr2 ){
				$my1 = abs($hr1 - ($ovalSet[$elementID]["y1"] + $ovalSet[$elementID]["y2"])/2);
				$mx1 = sqrt( $a*$a*(1-($my1*$my1)/($b*$b)) );
				$effArea = $a*$b*pi()/2 + $mx1*$my1 + $a*$b*acos( $mx1/$a );
			}
			else if( $y1<$hr1 && $y2>$hr2 ){
				$my1 = abs($hr1 - ($ovalSet[$elementID]["y1"] + $ovalSet[$elementID]["y2"])/2);
				$mx1 = sqrt( $a*$a*(1-($my1*$my1)/($b*$b)) );
				$my2 = abs($hr2 - ($ovalSet[$elementID]["y1"] + $ovalSet[$elementID]["y2"])/2);
				$mx2 = sqrt( $a*$a*(1-($my2*$my2)/($b*$b)) );
				$effArea = $mx1*$my1 + $a*$b*acos( $mx1/$a ) + $mx2*$my2 + $a*$b*acos( $mx2/$a );
			}
			
		}
		return $effArea;
	}
	
	// get the relative position relationship among four points of quad
	function getPointRelation( $elementID ){
		global $quadSet;
		$points = array();
		$maxPy = max( $quadSet[$elementID]["p1y"], $quadSet[$elementID]["p2y"], $quadSet[$elementID]["p3y"], $quadSet[$elementID]["p4y"] );
		if( $quadSet[$elementID]["p1y"] == $maxPy ){
			if( $quadSet[$elementID]["p1y"] == $quadSet[$elementID]["p4y"] ){
				if( $quadSet[$elementID]["p1x"] <= $quadSet[$elementID]["p4x"] ){
					$points["up"]["x"] = $quadSet[$elementID]["p1x"];
					$points["up"]["y"] = $quadSet[$elementID]["p1y"];
					$points["right"]["x"] = $quadSet[$elementID]["p4x"];
					$points["right"]["y"] = $quadSet[$elementID]["p4y"];
					$points["left"]["x"] = $quadSet[$elementID]["p2x"];
					$points["left"]["y"] = $quadSet[$elementID]["p2y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p3x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p3y"];
				}
				else{
					$points["up"]["x"] = $quadSet[$elementID]["p4x"];
					$points["up"]["y"] = $quadSet[$elementID]["p4y"];
					$points["right"]["x"] = $quadSet[$elementID]["p1x"];
					$points["right"]["y"] = $quadSet[$elementID]["p1y"];
					$points["left"]["x"] = $quadSet[$elementID]["p3x"];
					$points["left"]["y"] = $quadSet[$elementID]["p3y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p2x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p2y"];
				}
			}
			else if( $quadSet[$elementID]["p1y"] == $quadSet[$elementID]["p2y"] ){
				if( $quadSet[$elementID]["p1x"] <= $quadSet[$elementID]["p2x"] ){
					$points["up"]["x"] = $quadSet[$elementID]["p1x"];
					$points["up"]["y"] = $quadSet[$elementID]["p1y"];
					$points["right"]["x"] = $quadSet[$elementID]["p2x"];
					$points["right"]["y"] = $quadSet[$elementID]["p2y"];
					$points["left"]["x"] = $quadSet[$elementID]["p4x"];
					$points["left"]["y"] = $quadSet[$elementID]["p4y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p3x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p3y"];
					
				}
				else{
					$points["up"]["x"] = $quadSet[$elementID]["p2x"];
					$points["up"]["y"] = $quadSet[$elementID]["p2y"];
					$points["right"]["x"] = $quadSet[$elementID]["p1x"];
					$points["right"]["y"] = $quadSet[$elementID]["p1y"];
					$points["left"]["x"] = $quadSet[$elementID]["p3x"];
					$points["left"]["y"] = $quadSet[$elementID]["p3y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p4x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p4y"];
				}
			}
			else{
				$points["up"]["x"] = $quadSet[$elementID]["p1x"];
				$points["up"]["y"] = $quadSet[$elementID]["p1y"];
				if( $quadSet[$elementID]["p2x"] >= $quadSet[$elementID]["p4x"] ){
					$points["right"]["x"] = $quadSet[$elementID]["p2x"];
					$points["right"]["y"] = $quadSet[$elementID]["p2y"];
					$points["left"]["x"] = $quadSet[$elementID]["p4x"];
					$points["left"]["y"] = $quadSet[$elementID]["p4y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p3x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p3y"];
				}
				else{
					$points["right"]["x"] = $quadSet[$elementID]["p4x"];
					$points["right"]["y"] = $quadSet[$elementID]["p4y"];
					$points["left"]["x"] = $quadSet[$elementID]["p2x"];
					$points["left"]["y"] = $quadSet[$elementID]["p2y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p3x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p3y"];
				}
			}
		}
		else if( $quadSet[$elementID]["p2y"] == $maxPy ){
			if( $quadSet[$elementID]["p2y"] == $quadSet[$elementID]["p1y"] ){
				if( $quadSet[$elementID]["p2x"] <= $quadSet[$elementID]["p1x"] ){
					$points["up"]["x"] = $quadSet[$elementID]["p2x"];
					$points["up"]["y"] = $quadSet[$elementID]["p2y"];
					$points["right"]["x"] = $quadSet[$elementID]["p1x"];
					$points["right"]["y"] = $quadSet[$elementID]["p1y"];
					$points["left"]["x"] = $quadSet[$elementID]["p3x"];
					$points["left"]["y"] = $quadSet[$elementID]["p3y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p4x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p4y"];
				}
				else{
					$points["up"]["x"] = $quadSet[$elementID]["p1x"];
					$points["up"]["y"] = $quadSet[$elementID]["p1y"];
					$points["right"]["x"] = $quadSet[$elementID]["p2x"];
					$points["right"]["y"] = $quadSet[$elementID]["p2y"];
					$points["left"]["x"] = $quadSet[$elementID]["p4x"];
					$points["left"]["y"] = $quadSet[$elementID]["p4y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p3x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p3y"];
				}
			}
			else if( $quadSet[$elementID]["p2y"] == $quadSet[$elementID]["p3y"] ){
				if( $quadSet[$elementID]["p2x"] <= $quadSet[$elementID]["p3x"] ){
					$points["up"]["x"] = $quadSet[$elementID]["p2x"];
					$points["up"]["y"] = $quadSet[$elementID]["p2y"];
					$points["right"]["x"] = $quadSet[$elementID]["p3x"];
					$points["right"]["y"] = $quadSet[$elementID]["p3y"];
					$points["left"]["x"] = $quadSet[$elementID]["p1x"];
					$points["left"]["y"] = $quadSet[$elementID]["p1y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p4x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p4y"];
					
				}
				else{
					$points["up"]["x"] = $quadSet[$elementID]["p3x"];
					$points["up"]["y"] = $quadSet[$elementID]["p3y"];
					$points["right"]["x"] = $quadSet[$elementID]["p2x"];
					$points["right"]["y"] = $quadSet[$elementID]["p2y"];
					$points["left"]["x"] = $quadSet[$elementID]["p4x"];
					$points["left"]["y"] = $quadSet[$elementID]["p4y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p1x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p1y"];
				}
			}
			else{
				$points["up"]["x"] = $quadSet[$elementID]["p2x"];
				$points["up"]["y"] = $quadSet[$elementID]["p2y"];
				if( $quadSet[$elementID]["p3x"] >= $quadSet[$elementID]["p1x"] ){
					$points["right"]["x"] = $quadSet[$elementID]["p3x"];
					$points["right"]["y"] = $quadSet[$elementID]["p3y"];
					$points["left"]["x"] = $quadSet[$elementID]["p1x"];
					$points["left"]["y"] = $quadSet[$elementID]["p1y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p4x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p4y"];
				}
				else{
					$points["right"]["x"] = $quadSet[$elementID]["p1x"];
					$points["right"]["y"] = $quadSet[$elementID]["p1y"];
					$points["left"]["x"] = $quadSet[$elementID]["p3x"];
					$points["left"]["y"] = $quadSet[$elementID]["p3y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p4x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p4y"];
				}
			}
		}
		else if( $quadSet[$elementID]["p3y"] == $maxPy ){
			if( $quadSet[$elementID]["p3y"] == $quadSet[$elementID]["p2y"] ){
				if( $quadSet[$elementID]["p3x"] <= $quadSet[$elementID]["p2x"] ){
					$points["up"]["x"] = $quadSet[$elementID]["p3x"];
					$points["up"]["y"] = $quadSet[$elementID]["p3y"];
					$points["right"]["x"] = $quadSet[$elementID]["p2x"];
					$points["right"]["y"] = $quadSet[$elementID]["p2y"];
					$points["left"]["x"] = $quadSet[$elementID]["p4x"];
					$points["left"]["y"] = $quadSet[$elementID]["p4y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p1x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p1y"];
				}
				else{
					$points["up"]["x"] = $quadSet[$elementID]["p2x"];
					$points["up"]["y"] = $quadSet[$elementID]["p2y"];
					$points["right"]["x"] = $quadSet[$elementID]["p3x"];
					$points["right"]["y"] = $quadSet[$elementID]["p3y"];
					$points["left"]["x"] = $quadSet[$elementID]["p1x"];
					$points["left"]["y"] = $quadSet[$elementID]["p1y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p4x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p4y"];
				}
			}
			else if( $quadSet[$elementID]["p3y"] == $quadSet[$elementID]["p4y"] ){
				if( $quadSet[$elementID]["p3x"] <= $quadSet[$elementID]["p4x"] ){
					$points["up"]["x"] = $quadSet[$elementID]["p3x"];
					$points["up"]["y"] = $quadSet[$elementID]["p3y"];
					$points["right"]["x"] = $quadSet[$elementID]["p4x"];
					$points["right"]["y"] = $quadSet[$elementID]["p4y"];
					$points["left"]["x"] = $quadSet[$elementID]["p2x"];
					$points["left"]["y"] = $quadSet[$elementID]["p2y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p1x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p1y"];
					
				}
				else{
					$points["up"]["x"] = $quadSet[$elementID]["p4x"];
					$points["up"]["y"] = $quadSet[$elementID]["p4y"];
					$points["right"]["x"] = $quadSet[$elementID]["p3x"];
					$points["right"]["y"] = $quadSet[$elementID]["p3y"];
					$points["left"]["x"] = $quadSet[$elementID]["p1x"];
					$points["left"]["y"] = $quadSet[$elementID]["p1y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p2x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p2y"];
				}
			}
			else{
				$points["up"]["x"] = $quadSet[$elementID]["p3x"];
				$points["up"]["y"] = $quadSet[$elementID]["p3y"];
				if( $quadSet[$elementID]["p4x"] >= $quadSet[$elementID]["p2x"] ){
					$points["right"]["x"] = $quadSet[$elementID]["p4x"];
					$points["right"]["y"] = $quadSet[$elementID]["p4y"];
					$points["left"]["x"] = $quadSet[$elementID]["p2x"];
					$points["left"]["y"] = $quadSet[$elementID]["p2y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p1x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p1y"];
				}
				else{
					$points["right"]["x"] = $quadSet[$elementID]["p2x"];
					$points["right"]["y"] = $quadSet[$elementID]["p2y"];
					$points["left"]["x"] = $quadSet[$elementID]["p4x"];
					$points["left"]["y"] = $quadSet[$elementID]["p4y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p1x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p1y"];
				}
			}
		}
		else{
			if( $quadSet[$elementID]["p4y"] == $quadSet[$elementID]["p3y"] ){
				if( $quadSet[$elementID]["p4x"] <= $quadSet[$elementID]["p3x"] ){
					$points["up"]["x"] = $quadSet[$elementID]["p4x"];
					$points["up"]["y"] = $quadSet[$elementID]["p4y"];
					$points["right"]["x"] = $quadSet[$elementID]["p3x"];
					$points["right"]["y"] = $quadSet[$elementID]["p3y"];
					$points["left"]["x"] = $quadSet[$elementID]["p1x"];
					$points["left"]["y"] = $quadSet[$elementID]["p1y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p2x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p2y"];
				}
				else{
					$points["up"]["x"] = $quadSet[$elementID]["p3x"];
					$points["up"]["y"] = $quadSet[$elementID]["p3y"];
					$points["right"]["x"] = $quadSet[$elementID]["p4x"];
					$points["right"]["y"] = $quadSet[$elementID]["p4y"];
					$points["left"]["x"] = $quadSet[$elementID]["p2x"];
					$points["left"]["y"] = $quadSet[$elementID]["p2y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p1x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p1y"];
				}
			}
			else if( $quadSet[$elementID]["p4y"] == $quadSet[$elementID]["p1y"] ){
				if( $quadSet[$elementID]["p4x"] <= $quadSet[$elementID]["p1x"] ){
					$points["up"]["x"] = $quadSet[$elementID]["p4x"];
					$points["up"]["y"] = $quadSet[$elementID]["p4y"];
					$points["right"]["x"] = $quadSet[$elementID]["p1x"];
					$points["right"]["y"] = $quadSet[$elementID]["p1y"];
					$points["left"]["x"] = $quadSet[$elementID]["p3x"];
					$points["left"]["y"] = $quadSet[$elementID]["p3y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p2x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p2y"];
					
				}
				else{
					$points["up"]["x"] = $quadSet[$elementID]["p1x"];
					$points["up"]["y"] = $quadSet[$elementID]["p1y"];
					$points["right"]["x"] = $quadSet[$elementID]["p4x"];
					$points["right"]["y"] = $quadSet[$elementID]["p4y"];
					$points["left"]["x"] = $quadSet[$elementID]["p2x"];
					$points["left"]["y"] = $quadSet[$elementID]["p2y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p3x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p3y"];
				}
			}
			else{
				$points["up"]["x"] = $quadSet[$elementID]["p4x"];
				$points["up"]["y"] = $quadSet[$elementID]["p4y"];
				if( $quadSet[$elementID]["p1x"] >= $quadSet[$elementID]["p3x"] ){
					$points["right"]["x"] = $quadSet[$elementID]["p1x"];
					$points["right"]["y"] = $quadSet[$elementID]["p1y"];
					$points["left"]["x"] = $quadSet[$elementID]["p3x"];
					$points["left"]["y"] = $quadSet[$elementID]["p3y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p2x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p2y"];
				}
				else{
					$points["right"]["x"] = $quadSet[$elementID]["p3x"];
					$points["right"]["y"] = $quadSet[$elementID]["p3y"];
					$points["left"]["x"] = $quadSet[$elementID]["p1x"];
					$points["left"]["y"] = $quadSet[$elementID]["p1y"];
					$points["bottom"]["x"] = $quadSet[$elementID]["p2x"];
					$points["bottom"]["y"] = $quadSet[$elementID]["p2y"];
				}
			}
		}
		return $points;
	}
	
	// get the ID and type of element which stands for seat surface;
	// decide the existence of backrest
	$maxArea = -1;
	$seatSurface["id"] = -1;
	$hasBackrest = 1;
	for( $i=$segCnt; $i<$segCnt+$quadCnt+$ovalCnt; $i++ ){
		$elementCentre = $centrePoint[$i];
		if( $hr1<$elementCentre["y"] && $elementCentre["y"]<$hr2 ){
			$effArea = effectiveArea( $i );
			if( $effArea > $maxArea ){
				$maxArea = $effArea;
				$seatSurface["id"] = $i;
			}
		}
	}
	if( $seatSurface["id"] == -1 ){
		$hasBackrest = 0;
		for( $i=$segCnt; $i<$segCnt+$quadCnt+$ovalCnt; $i++ ){
			$elementCentre = $centrePoint[$i];
			if( $elementCentre["y"] > $hr2 ){
				$effArea = effectiveArea( $i );
				if( $effArea > $maxArea ){
					$maxArea = $effArea;
					$seatSurface["id"] = $i;
				}
			}
		}
	}
	if( $seatSurface["id"] >= $segCnt+$quadCnt ){
		$seatSurface["type"] = SURFACE_OVAL;
	}
	else{
		$seatSurface["type"] = SURFACE_QUAD;
	}
	
	// get the ID and type of elements which stands for backrest and headrest
	if( $hasBackrest == 0 ){
		$backrest["id"]   = -1;
		$backrest["type"] = BACKREST_NONE;
		$headrest["id"]   = -1;
		$headrest["type"] = HEADREST_NONE;
	}
	else{
		$backrestArray = array();
		if( $seatSurface["type"] == SURFACE_OVAL ){
			$surfaceCentre = $centrePoint[ $seatSurface["id"] ];
			for( $i=$segCnt; $i<$segCnt+$quadCnt+$ovalCnt; $i++ ){
				if( $centrePoint[$i]["y"] > $surfaceCentre["y"] ){
					array_push( $backrestArray, $i );
				}
			}
		}
		else{
			$surfacePoints = getPointRelation( $seatSurface["id"] );
			$pointA = $surfacePoints["up"];
			$pointB = $surfacePoints["right"];
			for( $i=$segCnt; $i<$segCnt+$quadCnt+$ovalCnt; $i++ ){
				$pointC = $centrePoint[$i];
				$Sabc = ( $pointB["x"] - $pointA["x"] )*( $pointC["y"] - $pointA["y"] ) - ( $pointB["y"] - $pointA["y"] )*( $pointC["x"] - $pointA["x"] );
				if( $Sabc > 0 ){
					array_push( $backrestArray, $i );
				}
			}
		}
		if( count($backrestArray) == 1 ){
			$backrest["id"] = $backrestArray[0];
			if( $backrest["id"] >= $segCnt+$quadCnt ){
				$backrest["type"] = BACKREST_OVAL;
			}
			else{
				$backrest["type"] = BACKREST_QUAD;
			}
			$headrest["id"]   = -1;
			$headrest["type"] = HEADREST_NONE;
		}
		else{
			if( $centrePoint[ $backrestArray[0] ]["y"] > $centrePoint[ $backrestArray[1] ]["y"] ){
				$backrest["id"]   = $backrestArray[1];
				$headrest["id"]   = $backrestArray[0];
			}
			else{
				$backrest["id"]   = $backrestArray[0];
				$headrest["id"]   = $backrestArray[1];
			}
			if( $backrest["id"] >= $segCnt+$quadCnt ){
				$backrest["type"] = BACKREST_OVAL;
			}
			else{
				$backrest["type"] = BACKREST_QUAD;
			}
			if( $headrest["id"] >= $segCnt+$quadCnt ){
				$headrest["type"] = HEADREST_OVAL;
			}
			else{
				$headrest["type"] = HEADREST_QUAD;
			}
		}
	}
	
	// Distance between PA and PB
	function getPPDistance( $PA, $PB ){
		return sqrt( ($PA["x"] - $PB["x"])*($PA["x"] - $PB["x"]) + ($PA["y"] - $PB["y"])*($PA["y"] - $PB["y"]) );
	}
	
	// Nearest distance between PC and segment PA-PB
	function getNearDistance( $PA, $PB, $PC ){
		$a = getPPDistance( $PB, $PC );
		if( $a<0.00001 ){
			return 0;
		}
		$b = getPPDistance( $PA, $PC );
		if( $b<0.00001 ){
			return 0;
		}
		$c = getPPDistance( $PA, $PB );
		if( $c<0.00001 ){
			return $a;
		}
		if( $a*$a >= $b*$b + $c*$c ){
			return $b;
		}
		if( $b*$b >= $a*$a + $c*$c ){
			return $a;
		}
		$k = ($a+$b+$c)/2;
		$s = sqrt( $k*($k-$a)*($k-$b)*($k-$c) );
		return 2*$s/$c;
	}
	
	// whether or not point( x, y) is linking with backrest
	function conjointBackrest( $x, $y ){
		global $backrest;
		global $quadSet;
		global $ovalSet;
		if( $backrest["type"] == BACKREST_NONE ){
			return false;
		}
		else if( $backrest["type"] == BACKREST_QUAD ){
			$points = getPointRelation( $backrest["id"] );
			$PC = array("x"=>$x, "y"=>$y);
			$dis = getNearDistance( $points["left"], $points["bottom"], $PC );
			if( $dis <= OFFSET ){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			$a = abs($ovalSet[$backrest["id"]]["x2"] - $ovalSet[$backrest["id"]]["x1"]) / 2;
			$b = abs($ovalSet[$backrest["id"]]["y2"] - $ovalSet[$backrest["id"]]["y1"]) / 2;
			$newX = $x - ($ovalSet[$backrest["id"]]["x1"]+$ovalSet[$backrest["id"]]["x2"])/2;
			$newY = $y - ($ovalSet[$backrest["id"]]["y1"]+$ovalSet[$backrest["id"]]["y2"])/2;
			if( abs($newY) - $b <= OFFSET && abs($newY) - $b > 0 ){
				return true;
			}
			else if( abs($newY) - $b <= 0 ){
				$ovalX = sqrt( $a*$a*(1 - ($newY*$newY)/($b*$b)) );
				if( abs( $newX - $ovalX )<=OFFSET || abs( $newX + $ovalX )<=OFFSET ){
					return true;
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}
	}
	
	// when the type of seat surface is oval
	if( $seatSurface["type"] == SURFACE_OVAL ){
		for( $i=0; $i<$segCnt; $i++ ){
			$a = abs( $ovalSet[$seatSurface["id"]]["x2"] - $ovalSet[$seatSurface["id"]]["x1"] ) / 2;
			$b = abs( $ovalSet[$seatSurface["id"]]["y2"] - $ovalSet[$seatSurface["id"]]["y1"] ) / 2;
			$segX = $segSet[$i]["sx"] - ($ovalSet[$seatSurface["id"]]["x1"]+$ovalSet[$seatSurface["id"]]["x2"])/2;
			$segY = $segSet[$i]["sy"] - ($ovalSet[$seatSurface["id"]]["y1"]+$ovalSet[$seatSurface["id"]]["y2"])/2;
			if( abs($segY) - $b <= OFFSET && abs($segY) - $b > 0 ){
				if( $segSet[$i]["sy"] < $segSet[$i]["ey"] ){
					if( conjointBackrest($segSet[$i]["ex"], $segSet[$i]["ey"]) == true ){
						array_push($backBracket, $i);
					}
					else{
						array_push($armBracket, $i);
					}
				}
				else{
					array_push($legBracket, $i);
				}
				continue;
			}
			else if( abs($segY) - $b <= 0 ){
				$ovalX = sqrt( $a*$a*(1 - ($segY*$segY)/($b*$b)) );
				if( abs($segX-$ovalX)<=OFFSET || abs($segX+$ovalX)<=OFFSET ){ // conjoint
					if( $segSet[$i]["sy"] < $segSet[$i]["ey"] ){
						if( conjointBackrest($segSet[$i]["ex"], $segSet[$i]["ey"]) == true ){
							array_push($backBracket, $i);
						}
						else{
							array_push($armBracket, $i);
						}
					}
					else{
						array_push($legBracket, $i);
					}
					continue;
				}
			}
			$segX = $segSet[$i]["ex"] - ($ovalSet[$seatSurface["id"]]["x1"]+$ovalSet[$seatSurface["id"]]["x2"])/2;
			$segY = $segSet[$i]["ey"] - ($ovalSet[$seatSurface["id"]]["y1"]+$ovalSet[$seatSurface["id"]]["y2"])/2;
			if( abs($segY) - $b <= OFFSET && abs($segY) - $b > 0 ){
				if( $segSet[$i]["ey"] < $segSet[$i]["sy"] ){
					if( conjointBackrest($segSet[$i]["sx"], $segSet[$i]["sy"]) == true ){
						array_push($backBracket, $i);
					}
					else{
						array_push($armBracket, $i);
					}
				}
				else{
					array_push($legBracket, $i);
				}
			}
			else if( abs($segY) - $b <= 0 ){
				$ovalX = sqrt( $a*$a*(1 - ($segY*$segY)/($b*$b)) );
				if( abs($segX-$ovalX)<=OFFSET || abs($segX+$ovalX)<=OFFSET ){ // conjoint
					if( $segSet[$i]["ey"] < $segSet[$i]["sy"] ){
						if( conjointBackrest($segSet[$i]["sx"], $segSet[$i]["sy"]) == true ){
							array_push($backBracket, $i);
						}
						else{
							array_push($armBracket, $i);
						}
					}
					else{
						array_push($legBracket, $i);
					}
				}
			}
		}
	}
	
	// when the type of seat surface is quad
	else{
		$surfacePoints = getPointRelation( $seatSurface["id"] );
		for( $i=0; $i<$segCnt; $i++ ){
			$segPoint = array("x"=>$segSet[$i]["sx"], "y"=>$segSet[$i]["sy"]);
			$dis1 = getNearDistance( $surfacePoints["right"] , $surfacePoints["up"]   , $segPoint );
			$dis2 = getNearDistance( $surfacePoints["right"], $surfacePoints["bottom"], $segPoint );
			$dis3 = getNearDistance( $surfacePoints["left"] , $surfacePoints["up"]    , $segPoint );
			if( $dis1 <= OFFSET || $dis2 <= OFFSET || $dis3 <= OFFSET ){
				if( conjointBackrest($segSet[$i]["ex"], $segSet[$i]["ey"]) == true ){
					array_push($backBracket, $i);
					continue;
				}
			}
			$segPoint = array("x"=>$segSet[$i]["ex"], "y"=>$segSet[$i]["ey"]);
			$dis1 = getNearDistance( $surfacePoints["right"] , $surfacePoints["up"]   , $segPoint );
			$dis2 = getNearDistance( $surfacePoints["right"], $surfacePoints["bottom"], $segPoint );
			$dis3 = getNearDistance( $surfacePoints["left"] , $surfacePoints["up"]    , $segPoint );
			if( $dis1 <= OFFSET || $dis2 <= OFFSET || $dis3 <= OFFSET ){
				if( conjointBackrest($segSet[$i]["sx"], $segSet[$i]["sy"]) == true ){
					array_push($backBracket, $i);
					continue;
				}
			}
			$segPoint = array("x"=>$segSet[$i]["sx"], "y"=>$segSet[$i]["sy"]);
			$dis1 = getNearDistance( $surfacePoints["left"] , $surfacePoints["bottom"], $segPoint );
			$dis2 = getNearDistance( $surfacePoints["right"], $surfacePoints["bottom"], $segPoint );
			$dis3 = getNearDistance( $surfacePoints["left"] , $surfacePoints["up"]    , $segPoint );
			if( $dis1 <= OFFSET || $dis2 <= OFFSET || $dis3 <= OFFSET ){
				if( $segSet[$i]["sy"] < $segSet[$i]["ey"] ){
					array_push($armBracket, $i);
				}
				else{
					array_push($legBracket, $i);
				}
				continue;
			}
			$segPoint = array("x"=>$segSet[$i]["ex"], "y"=>$segSet[$i]["ey"]);
			$dis1 = getNearDistance( $surfacePoints["left"] , $surfacePoints["bottom"], $segPoint );
			$dis2 = getNearDistance( $surfacePoints["right"], $surfacePoints["bottom"], $segPoint );
			$dis3 = getNearDistance( $surfacePoints["left"] , $surfacePoints["up"]    , $segPoint );
			if( $dis1 <= OFFSET || $dis2 <= OFFSET || $dis3 <= OFFSET ){
				if( $segSet[$i]["ey"] < $segSet[$i]["sy"] ){
					array_push($armBracket, $i);
				}
				else{
					array_push($legBracket, $i);
				}
			}
		}
	}
	
	// get the type of armrest
	if( count($armBracket) == 4 ){
		$armrest["type"] = ARMREST_DOUBLE;
	}
	else if( count($armBracket) == 2 ){
		$armrest["type"] = ARMREST_SINGLE;
	}
	else if( count($armBracket) == 0 ){
		if( $seatSurface["type"] == SURFACE_OVAL ){
			$armrest["type"] = ARMREST_NONE;
		}
		else{
			$sofa = 0;
			for( $i=$segCnt; $i<$segCnt+$quadCnt; $i++ ){
				if( $i == $seatSurface["id"] ){
					continue;
				}
				$surfacePoints = getPointRelation( $seatSurface["id"] );
				$quadPoints = getPointRelation( $i );
				$A = $surfacePoints["bottom"];
				$B = $surfacePoints["right"];
				$C = $quadPoints["bottom"];
				$D = $quadPoints["right"];
				$product1 = ($B["x"]-$A["x"])*($D["y"]-$C["y"]) - ($D["x"]-$C["x"])*($B["y"]-$A["y"]);
				$product2 = ($C["x"]-$A["x"])*($D["y"]-$C["y"]) - ($D["x"]-$C["x"])*($C["y"]-$A["y"]);
				$offset = OFFSET*( ($D["x"]-$C["x"]) + ($D["y"]-$C["y"]) );
				if( abs($product1) <= $offset && abs($product2) <= $offset ){
					$sofa = 1;
					break;
				}
			}
			if( $sofa == 1 ){
				$armrest["type"] = ARMREST_SOFA;
			}
			else{
				$armrest["type"] = ARMREST_NONE;
			}
		}
	}
	
	// get the type of leg
	if( count($legBracket) == 0 ){
		$legs["type"] = LEG_SOFA;
	}
	else if( count($legBracket) == 1 ){
		$PA = array("x"=>$segSet[$legBracket[0]]["sx"], "y"=>$segSet[$legBracket[0]]["sy"]);
		$PB = array("x"=>$segSet[$legBracket[0]]["ex"], "y"=>$segSet[$legBracket[0]]["ey"]);
		$star = 0;
		for( $i=0; $i<$segCnt; $i++ ){
			if( $legBracket[0] == $i ){
				continue;
			}
			$segPoint = array("x"=>$segSet[$i]["sx"], "y"=>$segSet[$i]["sy"]);
			$dis = getNearDistance( $PA , $PB, $segPoint );
			if( $dis <= OFFSET ){
				$star++;
				continue;
			}
			$segPoint = array("x"=>$segSet[$i]["ex"], "y"=>$segSet[$i]["ey"]);
			$dis = getNearDistance( $PA , $PB, $segPoint );
			if( $dis <= OFFSET ){
				$star++;
			}
		}
		$wheel = $ovalCnt;
		if( $seatSurface["type"] == SURFACE_OVAL ){
			$wheel--;
		}
		if( $headrest["type"] == HEADREST_OVAL ){
			$wheel--;
		}
		if( $backrest["type"] == BACKREST_OVAL ){
			$wheel--;
		}
		if( $star == 0 ){
			$legs["type"] = LEG_1_DISK;
		}
		else if( $star == 3 && $wheel >= 3 ){
			$legs["type"] = LEG_1_3STAR_WHEEL;
		}
		else if( $star == 5 && $wheel >= 5 ){
			$legs["type"] = LEG_1_5STAR_WHEEL;
		}
		else if( $star == 3 ){
			$legs["type"] = LEG_1_3STAR;
		}
		else{
			$legs["type"] = LEG_1_5STAR;
		}
	}
	else if( count($legBracket) == 4 ){
		$tmpTop = array();
		$tmpBot = array();
		for( $i=0; $i<4; $i++ ){
			if( $segSet[$legBracket[$i]]["sy"] < $segSet[$legBracket[$i]]["ey"] ){
				$botP["x"] = $segSet[$legBracket[$i]]["sx"];
				$botP["y"] = $segSet[$legBracket[$i]]["sy"];
				$topP["x"] = $segSet[$legBracket[$i]]["ex"];
				$topP["y"] = $segSet[$legBracket[$i]]["ey"];
			}
			else{
				$botP["x"] = $segSet[$legBracket[$i]]["ex"];
				$botP["y"] = $segSet[$legBracket[$i]]["ey"];
				$topP["x"] = $segSet[$legBracket[$i]]["sx"];
				$topP["y"] = $segSet[$legBracket[$i]]["sy"];
			}
			array_push( $tmpBot, $botP );
			array_push( $tmpTop, $topP );
		}
		for( $i=0; $i<3; $i++ ){
			for( $j=1; $j<4-$i; $j++ ){
				if( $tmpBot[$j]["x"] < $tmpBot[$j-1]["x"] ){
					$tmpPoint     = $tmpBot[$j];
					$tmpBot[$j]   = $tmpBot[$j-1];
					$tmpBot[$j-1] = $tmpPoint;
					$tmpPoint     = $tmpTop[$j];
					$tmpTop[$j]   = $tmpTop[$j-1];
					$tmpTop[$j-1] = $tmpPoint;
					$tmpPoint         = $legBracket[$j];
					$legBracket[$j]   = $legBracket[$j-1];
					$legBracket[$j-1] = $tmpPoint;
				}
			}
		}
		$frontLA = $tmpTop[0];
		$frontRA = $tmpTop[1];
		$backLA  = $tmpTop[2];
		$backRA  = $tmpTop[3];
		$frontLB = $tmpBot[0];
		$frontRB = $tmpBot[1];
		$backLB  = $tmpBot[2];
		$backRB  = $tmpBot[3];
		$frontBack = 0;
		$leftRight = 0;
		for( $i=0; $i<$segCnt; $i++ ){
			if( $legBracket[0] == $i || $legBracket[1] == $i || $legBracket[2] == $i || $legBracket[3] == $i ){
				continue;
			}
			$segPoint = array("x"=>$segSet[$i]["sx"], "y"=>$segSet[$i]["sy"]);
			$dis = getNearDistance( $frontRA , $frontRB, $segPoint );
			if( $dis <= OFFSET ){
				$segPoint = array("x"=>$segSet[$i]["ex"], "y"=>$segSet[$i]["ey"]);
				$dis = getNearDistance( $frontLA , $frontLB, $segPoint );
				if( $dis <= OFFSET ){
					$frontBack = 1;
					continue;
				}
				$dis = getNearDistance( $backRA , $backRB, $segPoint );
				if( $dis <= OFFSET ){
					$leftRight = 1;
					continue;
				}
			}
			$segPoint = array("x"=>$segSet[$i]["ex"], "y"=>$segSet[$i]["ey"]);
			$dis = getNearDistance( $frontRA , $frontRB, $segPoint );
			if( $dis <= OFFSET ){
				$segPoint = array("x"=>$segSet[$i]["sx"], "y"=>$segSet[$i]["sy"]);
				$dis = getNearDistance( $frontLA , $frontLB, $segPoint );
				if( $dis <= OFFSET ){
					$frontBack = 1;
					continue;
				}
				$dis = getNearDistance( $backRA , $backRB, $segPoint );
				if( $dis <= OFFSET ){
					$leftRight = 1;
				}
			}
		}
		if( $frontBack == 0 && $leftRight == 0 ){
			$legs["type"] = LEG_4;
		}
		else if( $frontBack == 1 && $leftRight == 1 ){
			$legs["type"] = LEG_4_FBRL;
		}
		else if( $frontBack == 1 && $leftRight == 0 ){
			$legs["type"] = LEG_4_FB;
		}
		else if( $frontBack == 0 && $leftRight == 1 ){
			$legs["type"] = LEG_4_RL;
		}
		else{
			// reservation
		}
	}
	
	// all characteristics
	$character = "surface=".strval($seatSurface["type"]).
				"&backrest=".strval($backrest["type"]).
				"&backBracket=".
				strval(count($backBracket)).
				"&headrest=".strval($headrest["type"]).
				"&armrest=".strval($armrest["type"]).
				"&legs=".strval($legs["type"]);
	
	header("Location: querier.php?".$character);
?>