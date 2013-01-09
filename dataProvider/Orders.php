<?php
/*
 GaiaEHR (Electronic Health Records)
 Orders.php
 Orders dataProvider
 Copyright (C) 2012 Ernesto J. Rodriguez (Certun)

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
if (!isset($_SESSION)) {
    session_name("GaiaEHR");
    session_start();
    session_cache_limiter('private');
}
include_once ($_SESSION['root'] . '/classes/dbHelper.php');
include_once ($_SESSION['root'] . '/dataProvider/Patient.php');
include_once ($_SESSION['root'] . '/dataProvider/User.php');
include_once ($_SESSION['root'] . '/dataProvider/Encounter.php');
include_once ($_SESSION['root'] . '/dataProvider/Services.php');
include_once ($_SESSION['root'] . '/dataProvider/Facilities.php');
include_once ($_SESSION['root'] . '/dataProvider/Documents.php');
class Orders
{

    function __construct()
    {
        $this->db = new dbHelper();
        $this->user = new User();
        $this->patient = new Patient();
        $this->services = new Services();
        $this->facility = new Facilities();
        $this->documents = new Documents();
        return;
    }

    public function addOrdersLabs($params)
    {

        foreach ($params->labs as $lab) {
            $foo = array();
            $foo['uid'] = $_SESSION['user']['id'];
            $foo['pid'] = $_SESSION['patient']['pid'];
            $foo['document_id'] = $params->document_id;
            $foo['labs'] = $lab->laboratories;
            $this->db->setSQL($this->db->sqlBind($foo, 'patient_orders', 'I'));
            $this->db->execLog();
        }
    }

    public function getPatientLabOrders($params)
    {
        $this->db->setSQL("SELECT * FROM `patient_orders` WHERE `order_type` = 'lab' AND pid = '$params->pid'");
        return $this->db->fetchRecords(PDO::FETCH_ASSOC);
    }

    public function addPatientLabOrder($params)
    {
        $data = get_object_vars($params);
        unset($data['id']);
        $this->db->setSQL($this->db->sqlBind($data, 'patient_orders', 'I'));
        $this->db->execLog();
        $params->id = $this->db->lastInsertId;
        return $params;
    }

    public function updatePatientLabOrder($params)
    {
        $data = get_object_vars($params);
        unset($data['id']);
        $this->db->setSQL($this->db->sqlBind($data, 'patient_orders', 'U', array('id' => $params->id)));
        $this->db->execLog();
        return $params;
    }



    public function getPatientXrayCtOrders($params)
    {
        $this->db->setSQL("SELECT * FROM `patient_orders` WHERE `order_type` = 'img' AND pid = '$params->pid'");
        return $this->db->fetchRecords(PDO::FETCH_ASSOC);
    }

    public function addPatientXrayCtOrder($params)
    {
        $data = get_object_vars($params);
        unset($data['id']);
        $this->db->setSQL($this->db->sqlBind($data, 'patient_orders', 'I'));
        $this->db->execLog();
        $params->id = $this->db->lastInsertId;
        return $params;
    }

    public function updatePatientXrayCtOrder($params)
    {
        $data = get_object_vars($params);
        unset($data['id']);
        $this->db->setSQL($this->db->sqlBind($data, 'patient_orders', 'U', array('id' => $params->id)));
        $this->db->execLog();
        return $params;
    }

}
