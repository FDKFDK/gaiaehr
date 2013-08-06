<?php
/**
 * GaiaEHR (Electronic Health Records)
 * Copyright (C) 2013 Certun, inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
include_once('../classes/MatchaHelper.php');
include_once('../lib/HL7/HL7.php');

class HL7Messages {

	/**
	 * @var HL7
	 */
	public $hl7;
	/**
	 * @var MatchaCUP HL7Messages
	 */
	private $m;
	/**
	 * @var MatchaCUP Facility
	 */
	private $f;
	/**
	 * @var MatchaCUP HL7Recipients
	 */
	private $r;
	/**
	 * @var MatchaCUP PatientImmunization
	 */
	private $i;
	/**
	 * @var MatchaCUP Patient
	 */
	private $p;
	/**
	 * @var array
	 */
	private $msg;
	/**
	 * @var int|array
	 */
	private $to;
	/**
	 * @var int|array
	 */
	private $from;
	/**
	 * @var int|array
	 */
	private $patient;
	/**
	 * @var string
	 */
	private $type;


	function __construct(){
		$this->hl7 = new HL7();
		$this->m = MatchaModel::setSenchaModel('App.model.administration.HL7Messages');
		$this->r = MatchaModel::setSenchaModel('App.model.administration.HL7Recipients');
		$this->f = MatchaModel::setSenchaModel('App.model.administration.Facility');
	}

	private function setMSH(){
		// set these globally
		$this->to = $this->r->load($this->to)->one();
		$this->from = $this->f->load($this->from)->one();
	    //
		$msh = $this->hl7->addSegment('MSH');
		$msh->setValue('3.1','GaiaEHR');                            // Sending Application
		$msh->setValue('4.1', addslashes($this->from['name']));                 // Sending Facility
		$msh->setValue('5.1', $this->to['recipient_application']);  // Receiving Application
		$msh->setValue('6.1', $this->to['recipient_facility']);     // Receiving Facility
		$msh->setValue('11.1','P');     // D = Debugging P = Production T = Training
		$msh->setValue('12.1','2.5.1'); // HL7 version
		return $msh;
	}

	private function setPID(){
		// set patient globally
		$this->p = MatchaModel::setSenchaModel('App.model.patient.Patient');
		$this->patient = $this->p->load($this->patient)->one();

		// TODO
		$pid = $this->hl7->addSegment('PID');
		$pid->setValue('3.1', '15485');         //IDNumber
		$pid->setValue('3.4.1', 'MPI');         //Namespace ID
		$pid->setValue('3.4.2', '2.16.840.1.113883.19.3.2.1'); //Universal ID
		$pid->setValue('3.4.3', 'ISO');         //Universal ID Type (HL70301)
		$pid->setValue('3.5', 'MR');            //IDNumber Type (HL70203)
		$pid->setValue('5.1.1', 'Rodriguez');   //Surname
		$pid->setValue('5.2', 'Ernesto');       //GivenName
		$pid->setValue('7.1', '19780123');      //Date of Birth
		$pid->setValue('8','M');                //Administrative Sex
		$pid->setValue('10.1','2106-3');        //Race Identifier
		$pid->setValue('10.2','White');         //Race Text
		$pid->setValue('10.3','HL70005');       //Race Name of Coding System
		$pid->setValue('11.1.1','Stret');            //Street or Mailing Address
		$pid->setValue('11.3','San Juan');              //City
		$pid->setValue('11.4','PR');              //State
		$pid->setValue('11.5','00987');              //Zip Code
		/**
		 * B Firm/Business
		 * BA Bad address
		 * BDL Birth delivery location (address where birth occurred)
		 * BR Residence at birth (home address at time of birth)
		 * C Current Or Temporary
		 * F Country Of Origin
		 * H Home
		 * L Legal Address
		 * M Mailing
		 * N Birth (nee) (birth address, not otherwise specified)
		 * O Office
		 * P Permanent
		 */
		$pid->setValue('11.7','P');              //Address Type
		$pid->setValue('13.2','PRN');           //PhoneNumber‐Home
		$pid->setValue('13.6','787');           //Area/City Code
		$pid->setValue('13.7','7525561');       //LocalNumber
		$pid->setValue('22.1','H');                  //EthnicGroup Identifier
		$pid->setValue('22.2','Hispanic or Latino'); //EthnicGroup Text
		$pid->setValue('22.3','HL70189');            //Name of Coding System
	}

	function sendVXU($params){
		// set these globally to be used by MSH and PID
		$this->to = $params->to;
		$this->from = $params->from;
		$this->patient = $params->pid;
		$this->type = 'VXU';

		// MSH
		$msh = $this->setMSH();
		$msh->setValue('9.1','VXU');
		$msh->setValue('9.2','V04');
		$msh->setValue('9.3','VXU_V04');
		// PID
		$this->setPID();

		$this->i = MatchaModel::setSenchaModel('App.model.patient.PatientImmunization');
		include_once($_SESSION['root'] . '/dataProvider/Immunizations.php');
		$immunization = new Immunizations();

		// immunizations loop
		foreach($params->immunizations AS $i){

			$immu = $this->i->load($i)->one();

			// ROC
			$roc = $this->hl7->addSegment('ORC');
			$roc->setValue('1', 'RE');                              //HL70119
			// RXA
			$rxa = $this->hl7->addSegment('RXA');
			$rxa->setValue('3.1', str_replace(array(' ',':','-'),'',$immu['administered_date']));      //Date/Time Start of Administration
			$rxa->setValue('4.1', str_replace(array(' ',':','-'),'',$immu['administered_date']));      //Date/Time End of Administration
			//Administered Code
			$rxa->setValue('5.1', $immu['code']);                   //Identifier
			$rxa->setValue('5.2', $immu['vaccine_name']);           //Text
			$rxa->setValue('5.3', $immu['code_type']);              //Name of Coding System
			if(!isset($immu['administer_amount']) && $immu['administer_amount'] != ''){
				$rxa->setValue('6', $immu['administer_amount']);    //Administered Amount
				//AdministeredUnits(ml, etc)
				$rxa->setValue('7.1', $immu['administer_units']);   //Identifier
				$rxa->setValue('7.2', 'millimeters');               //Text
				$rxa->setValue('7.3', 'ISO+');                      //Name of Coding System HL70396
			}else{
				$rxa->setValue('6', '999');                         //Administered Amount
			}
			$rxa->setValue('15', $immu['lot_number']);              //Substance LotNumbers
			// get immunization manufacturer info
			$mvx = $immunization->getMvxByCode($immu['manufacturer']);
			$mText = isset($mvx['manufacturer']) ? $mvx['manufacturer'] : '';
			//Substance ManufacturerName
			$rxa->setValue('17.1', $immu['manufacturer']);          //Identifier
			$rxa->setValue('17.2', $mText);                         //Text
			$rxa->setValue('17.3', 'MVX');                          //Name of Coding System HL70396
			$rxa->setValue('21', 'A');                              //Action Code

		}
		$this->initMsg();

		if($this->to['recipient_type'] == 'file'){
			return $this->Save();
		}else{
			return $this->Send();
		}
	}

	public function initMsg(){
		$foo = new stdClass();
		$foo->msg_type = $this->type;
		$foo->message = $this->hl7->getMessage();
		$foo->date_processed = date('Y-m-d H:i:s');
		$foo->isOutbound = true;
		$foo->status = 1; // processing
		$foo->foreign_address = $this->to['recipient'];
		$foo->foreign_facility = $this->to['recipient_facility'];
		$foo->foreign_application = $this->to['recipient_application'];
		$foo = $this->m->save($foo);
		$this->msg = $foo['data'];
	}

	private function Save(){

		$filename = rtrim($this->to['recipient'],'/'). '/' . $this->msg['msg_type'] . '-' . str_replace('.','',microtime(true)). '.txt';
		$error = false;

		if (!$handle = fopen($filename, 'w')) {
			$error = "Could not create file ($filename)";
		}
		if (fwrite($handle, $this->msg['message']) === false) {
			$error = "Cannot write to file ($filename)";
		}

		fclose($handle);

		if($error !== false){
			$this->msg['status'] = 4; // error
			$this->msg['error'] = '[] '. $error;
		}else{
			$this->msg['status'] = 3; // processed
			$this->msg['response'] = "File created - $filename";
		}

		$this->m->save((object) $this->msg);
		return array(
			'success' => $error === false,
			'message' => $this->msg
		);
	}

    public function Send(){
        $msg = $this->msg['message'];
        $ch = curl_init($this->to['recipient']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER , 0);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/hl7-v2; charset=ISO-8859-4',
            'Content-Length: ' . strlen($msg))
        );

	    $response = curl_exec($ch);
	    $error = curl_errno($ch);
	    if($error !== 0){
		    $this->msg['status'] = 4; // error
		    $this->msg['error'] = '['.$error.'] '. curl_error($ch);
	    }else{
		    $this->msg['status'] = 3; // processed
		    $this->msg['response'] = $response;
	    }
	    curl_close($ch);
	    $this->m->save((object) $this->msg);
        return array(
	        'success' => $error === 0,
	        'message' => $this->msg
        );
    }


	public function getMessages($params){
		return $this->m->load($params)->all();
	}

	public function getRecipients($params){
		return $this->r->load($params)->all();
	}
}

//print '<pre>';
//$hl7 = new HL7Messages();
//print_r($hl7->sendVXU());