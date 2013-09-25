<?php
/**
GaiaEHR (Electronic Health Records)
Copyright (C) 2013 Certun, inc.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Globals extends MatchaHelper
{

	/**
	 * @var bool|MatchaCUP
	 */
	private static $g = null;

	/**
	 * @return array
	 */
	public static function getGlobals()
	{
		$conn = new MatchaHelper();
        if(self::$g == null) self::$g = MatchaModel::setSenchaModel('App.model.administration.Globals');
		$conn->setSQL("SELECT gl_name, gl_index, gl_value FROM globals");
		$rows = array();
		foreach($conn->fetchRecords() as $row){
			$rows[$row[0]] = $row[2];
		}
		return $rows;
	}

	/**
	 * @param stdClass $params
	 * @return stdClass
	 */
	public function updateGlobals(stdClass $params)
	{
        if(self::$g == null) self::$g = MatchaModel::setSenchaModel('App.model.administration.Globals');
		$data = get_object_vars($params);
		foreach($data as $key => $value){
			if(is_int($value)){
				$rec = trim($value);
			} else{
				$rec = $value;
			}
			$this->setSQL("UPDATE globals
                SET   gl_value ='" . $rec . "'" . "
                WHERE gl_name  ='" . $key . "'");
			$this->execLog();
		}

		$this->setGlobals();

		return $params;
	}

	/**
	 * @static
	 * @return mixed
	 */
	public static function setGlobals()
	{
        if(self::$g == null) self::$g = MatchaModel::setSenchaModel('App.model.administration.Globals');
		foreach(self::$g->load()->all() as $setting)
        {
			$_SESSION['global_settings'][$setting['gl_name']] = $setting['gl_value'];
		}
		$_SESSION['global_settings']['timezone_offset'] = -14400;
		$_SESSION['global_settings']['date_time_display_format'] = $_SESSION['global_settings']['date_display_format'] . ' ' . $_SESSION['global_settings']['time_display_format'];
		return $_SESSION['site']['path'];
	}

}

//print '<pre>';
//$g = new Globals();
//print_r($g->getAllGlobals());
