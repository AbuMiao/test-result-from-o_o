<?php
namespace core\tools;

\Flight::map('new3DPosInsByGeographicCoor', array("core\\tools\\ClsCoordinateTools", "new3DPosInsByGeographicCoor"));

class Cls3DPosition{
	public function __construct($x, $y, $z){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}
	var $x = 0;
	var $y = 0;
	var $z = 0;
}

class ClsGeographicCoor{
	public function __construct($longitude, $latitude){
		$this->longitude = $longitude;
		$this->latitude = $latitude;
	}
	var $longitude = 120;
	var $latitude = 30;
}

class ClsCoordinateTools
{
    static public function new3DPosInsByGeographicCoor($geographic_coor){
		$latitude = $geographic_coor->latitude * ClsCoordinateTools::$w_deg_ratio;
		$longitude = $geographic_coor->longitude * ClsCoordinateTools::$w_deg_ratio;

		$r = ClsCoordinateTools::$earth_radius * cos($latitude);
		$cos_longitude = cos($longitude);
		$sin_longitude = sin($longitude);
		return new Cls3DPosition($r * $cos_longitude, $r * $sin_longitude, ClsCoordinateTools::$earth_radius * sin($latitude));
	}

	static private $w_deg_ratio = 0.01745329;
	static private $earth_radius = 6371393;	//单位:米
}
?>