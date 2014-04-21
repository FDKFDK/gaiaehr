<?php
/**
 * GaiaEHR (Electronic Health Records)
 * Copyright (C) 2013 Certun, LLC.
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
if (!isset($_SESSION)){
	session_name('GaiaEHR');
	session_start();
	session_cache_limiter('private');
}
//define('_GaiaEXEC', 1);
//include_once(dirname(dirname(__FILE__)) . '/registry.php');

class HL7Server {

	/**
	 * @var HL7
	 */
	private $hl7;
	/**
	 * @var HL7
	 */
	private $ack;
	/**
	 * @var MatchaCUP
	 */
	private $m;
	/**
	 * @var MatchaCUP
	 */
	private $r;
	/**
	 * @var MatchaCUP
	 */
	private $p;
	/**
	 * @var MatchaCUP
	 */
	private $s;
	/**
	 * @var bool
	 */
	private $ackStatus;
	/**
	 * @var string
	 */
	private $ackMessage;
	/**
	 * @var string
	 */
	private $site;
	/**
	 * @var int
	 */
	private $port;
	/**
	 * @var array|bool
	 */
	private $recipient;

	/**
	 * @var string
	 */
	private $msg;

	private $pOrder;
	private $pResult;
	private $pObservation;

	protected $updateKey = 'pid';


	function __construct($site = 'default'){
		$this->site = $site;
		include_once(dirname(dirname(__FILE__))."/sites/{$this->site}/conf.php");
		include_once(dirname(dirname(__FILE__)).'/classes/MatchaHelper.php');
		include_once(dirname(dirname(__FILE__)).'/lib/HL7/HL7.php');
		include_once(dirname(__FILE__).'/HL7ServerHandler.php');
		new MatchaHelper();

		/** HL7 Models */
		$this->s = MatchaModel::setSenchaModel('App.model.administration.HL7Server');
		$this->m = MatchaModel::setSenchaModel('App.model.administration.HL7Messages');
		$this->r = MatchaModel::setSenchaModel('App.model.administration.HL7Recipients');

		/** Patient Model */
		$this->p = MatchaModel::setSenchaModel('App.model.patient.Patient');

		/** Order Models */
		$this->pOrder = MatchaModel::setSenchaModel('App.model.patient.PatientsOrders');
		$this->pResult = MatchaModel::setSenchaModel('App.model.patient.PatientsOrderResult');
		$this->pObservation = MatchaModel::setSenchaModel('App.model.patient.PatientsOrderObservation');
	}

	public function getServers($params){
		$servers = $this->s->load($params)->all();
		foreach($servers['data'] as $i => $server){
			$handler = new HL7ServerHandler();
			$status = $handler->status($server['port']);
			$servers['data'][$i]['online'] = $status['online'];
			unset($handler);
		}

		return $servers;
	}

	public function getServer($params){
		return $this->s->load($params)->one();
	}

	public function addServer($params){
		return $this->s->save($params);
	}

	public function updateServer($params){
		return $this->s->save($params);
	}

	public function deleteServer($params){
		return $this->s->destroy($params);
	}


	public function Process($msg = '', $addSocketCharacters = true){
//		try{
			$this->msg = $msg;

			$this->ackStatus = 'AA';
			$this->ackMessage = '';

			/**
			 * Parse the HL7 Message
			 */
			$hl7 = new HL7();
			$msg = $hl7->readMessage($this->msg);
			$application = $hl7->getSendingApplication();
			$facility = $hl7->getSendingFacility();
			$version =  $hl7->getMsgVersionId();
			/**
			 * check HL7 version
			 */
			if($version != '2.5.1'){
				$this->ackStatus = 'AR';
				$this->ackMessage = 'HL7 version unsupported';
			}
			/**
			 * Check for IP address access
			 */
//			$this->recipient = $this->r->load(array('recipient_application' => $application))->one();
//			if($this->recipient === false){
//				$this->ackStatus = 'AR';
//				$this->ackMessage = "This application '$application' Not Authorized";
//			}
			/**
			 *
			 */
			if($msg === false){
				$this->ackStatus = 'AE';
				$this->ackMessage = 'Unable to parse HL7 message, please contact Support Desk';
			}
			/**
			 *
			 */
			$msgRecord = new stdClass();
			$msgRecord->msg_type = $hl7->getMsgType();
			$msgRecord->message = $this->msg;
			$msgRecord->foreign_facility = $hl7->getSendingFacility();
			$msgRecord->foreign_application = $hl7->getSendingApplication();
			$msgRecord->foreign_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
			$msgRecord->isOutbound = '0';
			$msgRecord->status = '2';
			$msgRecord->date_processed = date('Y-m-d H:i:s');
			$msgRecord = $this->m->save($msgRecord);
			$msgRecord = (array) $msgRecord['data'];

			if($this->ackStatus == 'AA'){
				/**
				 *
				 */
				switch($hl7->getMsgType()){
					case 'ORU':
						$this->ProcessORU($hl7, $msg, $msgRecord);
						break;
					case 'ADT':
						$this->ProcessADT($hl7, $msg, $msgRecord);
						break;
					default:

						break;
				}
			}

			/**
			 *
			 */
			$ack = new HL7();
			$msh = $ack->addSegment('MSH');
			$msh->setValue('3.1','GaiaEHR');                // Sending Application
			$msh->setValue('4.1', 'Gaia');                  // Sending Facility
			$msh->setValue('9.1','ACK');
			$msh->setValue('11.1','P');                     // P = Production
			$msh->setValue('12.1','2.5.1');                 // HL7 version
			$msa =  $ack->addSegment('MSA');
			$msa->setValue('1',$this->ackStatus);           // AA = Positive acknowledgment, AE = Application error, AR = Application reject
			$msa->setValue('2', $hl7->getMsgControlId());   // Message Control ID from MSH
			$msa->setValue('3', $this->ackMessage);         // Error Message
			$ackMsg = $ack->getMessage();

			$msgRecord['response'] = $ackMsg;
			$this->m->save((object)$msgRecord);

			// unset all the variables to release memory
			unset($ack, $hl7, $msg, $msgRecord, $oData, $result);


			return $addSocketCharacters ? "\v".$ackMsg.chr(0x1c).chr(0x0d) : $ackMsg;
//		}catch (Exception $e){
//			error_log($e->getMessage(), 1, "vela1606@gmail.com");
//			return '';
//		}
	}

	/**
	 * @param $hl7 HL7
	 * @param $msg
	 * @param $msgRecord
	 */
	private function ProcessORU($hl7, $msg, $msgRecord){
		foreach($msg->data['PATIENT_RESULT'] AS $patient_result){
			$patient = isset($patient_result['PATIENT']) ? $patient_result['PATIENT'] : null;
			foreach($patient_result['ORDER_OBSERVATION'] AS $order){
				$orc = $order['ORC'];
				$obr = $order['OBR'];
				/**
				 * Check for order number in GaiaEHR
				 */
				$orderId = $orc[2][1];
				$orderRecord = $this->pOrder->load(array('id' => $orderId))->one();
				/**
				 * id not found set the error and break twice to get out of all the loops
				 */
				if($orderRecord === false){
					$this->ackStatus = 'AR';
					$this->ackMessage = "Unable to find order number '$orderId' within the system";
					break 2;
				}
				$foo = new stdClass();
				$foo->order_id = $obr[2][1];
				$foo->lab_order_id = $obr[3][1];
				$foo->lab_name = $this->recipient['recipient_facility'];
				$foo->lab_address = $this->recipient['recipient_address'];
				$foo->observation_time = $hl7->time($obr[7][1]);
				$foo->result_status = $obr[25];
				if(is_array($obr[31][1])){
					$foo = array();
					foreach($obr[31] AS $dx){
						$foo[] = $dx[3].':'.$dx[1];
					}
					$foo->reason_code = implode(',',$foo);
				}else{
					$foo->reason_code = $obr[31][3].':'.$obr[31][1];
				}
				// specimen segment
				if(isset($order['SPECIMEN']) && $order['SPECIMEN'] !== false){
					$spm = $order['SPECIMEN']['SPM'];
					$foo->specimen_code = $spm[4][6] == 'HL70487' ? $spm[4][4] : $spm[4][1];
					$foo->specimen_text = $spm[4][6] == 'HL70487' ? $spm[4][5] : $spm[4][2];
					$foo->specimen_code_type = $spm[4][6] == 'HL70487' ? $spm[4][6] : $spm[4][3];
					$foo->specimen_notes = $spm[4][6] == 'HL70487' ? $spm[4][6] : $spm[4][3];
					// handle multiple SPECIMEN OBX's
//					if(isset($order['SPECIMEN']['OBX']) && $order['SPECIMEN']['OBX'] !== false){
//						foreach($order['SPECIMEN']['OBX'] AS $obx){
//					    	print_r($obx);
//						}
//					}
				}

				$foo->documentId = 'hl7|' . $msgRecord['id'];
				$rResult = (array) $this->pResult->save($foo);
				unset($foo);
				/**
				 * Handle all the observations
				 */
				foreach($order['OBSERVATION'] AS $observation){
					/**
					 * observations and notes
					 */
					$obx = $observation['OBX'];
					$note = $observation['NTE'];
					$foo = new stdClass();
					$foo->result_id = $rResult['id'];
					$foo->code = $obx[3][1];
					$foo->code_text = $obx[3][2];
					$foo->code_type = $obx[3][3];
					/**
					 * handle the dynamics of the value field
					 * based on the OBX-2 value
					 */
					if($obx[2] == 'CWE'){
						$foo->value = $obx[5][2];
					}else{
						$foo->value = $obx[5];
					}
					$foo->units = $obx[6][1];
					$foo->reference_rage = $obx[7];
					$foo->probability = $obx[9];
					$foo->abnormal_flag = $obx[8];
					$foo->nature_of_abnormal = $obx[10];
					$foo->observation_result_status = $obx[11];
					$foo->date_rage_values = $hl7->time($obx[12][1]);
					$foo->date_observation = $hl7->time($obx[14][1]);
					$foo->observer = trim($obx[16][2][1] . ' ' . $obx[16][3]);
					$foo->performing_org_name = $obx[23][1] ;
					$foo->performing_org_address = $obx[24][1][1] . ' ' . $obx[24][3] . ', ' . $obx[24][4] . ' ' . $obx[24][5];
					$foo->date_analysis = $hl7->time($obx[19][1]);
					$foo->notes = $note['3'];
					$this->pObservation->save($foo);
					unset($foo);
				}
				/**
				 * Change the order status to received
				 */
				$foo = new stdClass();
				$foo->id = $orderId;
				$foo->status = 'Received';
				$this->pOrder->save($foo);
				unset($foo);
			}
		}

		unset($patient, $rResult);
	}

	/**
	 * @param HL7       $hl7
	 * @param ADT       $msg
	 * @param stdClass  $msgRecord
	 */
	private function ProcessADT($hl7, $msg, $msgRecord){

		$evt = $hl7->getMsgEventType();

		if($evt == 'A01'){ /** Admit Visit **/
			$patient = $this->PidToPatient($msg->data['PID'], $hl7);


		} elseif($evt == 'A04'){ /** Register a Patient **/
			$patientObj = $this->PidToPatient($msg->data['PID'], $hl7);
			$patient = $this->p->load($patientObj->{$this->updateKey})->one();
			$patient = array_merge($patient, $patientObj);
			$this->p->save((object) $patient);

		} elseif($evt == 'A08'){ /** Update Patient Information **/
			$patientData = $this->PidToPatient($msg->data['PID'], $hl7);
			$patient = $this->p->load($patientData[$this->updateKey])->one();
			$patient = array_merge($patient, $patientData);

			foreach($msg->data['INSURANCE'] as $insuranceGroup){
				foreach($insuranceGroup as $key => $insurance){
					if($insurance == false) continue;
					if($key == 'IN1'){
						$in1 = $this->IN1ToInsuranceObj($insurance, $hl7);


					}elseif($key == 'IN2'){
						$in2 = $this->IN2ToInsuranceObj($insurance, $hl7);


					}elseif($key == 'IN3'){
						foreach($insurance as $IN3){
							$in3 = $this->IN3ToInsuranceObj($IN3, $hl7);



						}
					}
				}
			}


		} elseif($evt == 'A09'){ /** Patient Departing - Tracking **/

		} elseif($evt == 'A10'){ /** Patient Arriving - Tracking **/

		} elseif($evt == 'A18'){ /** Merge Patient Information **/

		} elseif($evt == 'A28'){ /** Add Person or Patient Information **/

		} elseif($evt == 'A29'){ /** Delete Person Information **/

		} elseif($evt == 'A31'){ /** Update Person Information **/

		} elseif($evt == 'A32'){ /** Cancel Patient Arriving - Tracking **/

		} elseif($evt == 'A33'){ /** Cancel Patient Departing - Tracking **/

		} elseif($evt == 'A39'){ /** Merge Person - Patient ID **/

		} elseif($evt == 'A40'){ /** Merge Patient - Patient Identifier List **/

		} elseif($evt == 'A41'){ /** Merge Account - Patient Account Number **/

		}





		unset($patient);
	}

	/**
	 * @param array $PID
	 * @param HL7   $hl7
	 *
	 * @return array
	 */
	private function PidToPatient($PID, $hl7){
		$p = array();
		if($this->notEmpty($PID[2][1]))     $p['pubpid'] = $PID[2][1];          // Patient ID (External ID)
		if($this->notEmpty($PID[3][1]))     $p['pid'] = $PID[3][1];              // Patient ID (Internal ID)
		if($this->notEmpty($PID[5][2]))     $p['fname'] = $PID[5][2];           // Patient Name...
		if($this->notEmpty($PID[5][3]))     $p['mname'] = $PID[5][3];           //
		if($this->notEmpty($PID[5][1][1]))  $p['lname'] = $PID[5][1][1];        //
		if($this->notEmpty($PID[6][3]))     $p['mothers_name'] = "{$PID[6][2]} {$PID[6][3]} {$PID[6][1][1]}";   // Mother’s Maiden Name
		if($this->notEmpty($PID[7][1]))     $p['DOB'] = $hl7->time($PID[7][1]);                                             // Date/Time of Birth
		if($this->notEmpty($PID[8]))        $p['sex'] = $PID[8];                                                // Sex
		if($this->notEmpty($PID[9][3]))     $p['alias'] = "{$PID[9][2]} {$PID[9][3]} {$PID[9][1][1]}";          // Patient Alias
		if($this->notEmpty($PID[10][1]))    $p['race'] = $PID[10][1];                   // Race
		if($this->notEmpty($PID[11][1][1])) $p['address'] = $PID[11][1][1];             // Patient Address
		if($this->notEmpty($PID[11][3]))    $p['city'] = $PID[11][3];                   //
		if($this->notEmpty($PID[11][4]))    $p['state'] = $PID[11][4];                  //
		if($this->notEmpty($PID[11][5]))    $p['zipcode'] = $PID[11][5];                //
		if($this->notEmpty($PID[11][6]))    $p['country'] = $PID[11][6];                // Country Code
		if($this->notEmpty($PID[13][7]))    $p['home_phone'] = "{$PID[13][7]} . '-' . {$PID[13][1]}";   // Phone Number – Home
		if($this->notEmpty($PID[14][7]))    $p['work_phone'] = "{$PID[14][7]} . '-' . {$PID[14][1]}";   // Phone Number – Business
		if($this->notEmpty($PID[15][1]))    $p['language'] = $PID[15][1];                               // Primary Language
		if($this->notEmpty($PID[16][1]))    $p['marital_status'] = $PID[16][1];                         // Marital Status
//		if($this->notEmpty($PID[17]))       $p['00'] = $PID[17];                                        // Religion
		if($this->notEmpty($PID[18][1]))    $p['pubaccount'] = $PID[18][1];                             // Patient Account Number
		if($this->notEmpty($PID[19]))       $p['SS'] = $PID[19];                                        // SSN Number – Patient
		if($this->notEmpty($PID[20][1]))    $p['drivers_license'] = $PID[20][1];                        // Driver’s License Number - Patient
		if($this->notEmpty($PID[20][2]))    $p['drivers_license_state'] = $PID[20][2];                  // Driver’s License State - Patient
		if($this->notEmpty($PID[20][3]))    $p['drivers_license_exp'] = $PID[20][3];                    // Driver’s License Exp Date - Patient
//		if($this->notEmpty($PID[21]))       $p['00'] = $PID[21];                            // Mother’s Identifier
		if($this->notEmpty($PID[22][1]))    $p['ethnicity'] = $PID[22][1];                  // Ethnic Group
		if($this->notEmpty($PID[23]))       $p['birth_place'] = $PID[23];                   // Birth Place
		if($this->notEmpty($PID[24]))       $p['birth_multiple'] = $PID[24];                // Multiple Birth Indicator
		if($this->notEmpty($PID[25]))       $p['birth_order'] = $PID[25];                   // Birth Order
		if($this->notEmpty($PID[26][1]))    $p['citizenship'] = $PID[26][1];                // Citizenship
		if($this->notEmpty($PID[27][1]))    $p['is_veteran'] = $PID[27][1];                 // Veterans Military Status
		if($this->notEmpty($PID[27][1]))    $p['death_date'] = $PID[29][1];                 // Patient Death Date and Time
		if($this->notEmpty($PID[30]))       $p['deceased'] = $PID[30];                      // Patient Death Indicator
		if($this->notEmpty($PID[33][1]))    $p['update_date'] = $hl7->time($PID[33][1]);    // Last update time stamp
		return $p;
	}

	/**
	 * @param array $IN1
	 * @param HL7   $hl7
	 *
	 * @return stdClass
	 */
	private function IN1ToInsuranceObj($IN1, $hl7) {
		$obj = new stdClass();
		if($this->notEmpty($IN1[0]))     $p['pid'] = $IN1[0];

		return $obj;
	}

	/**
	 * @param array $IN1
	 * @param HL7   $hl7
	 *
	 * @return stdClass
	 */
	private function IN2ToInsuranceObj($IN1, $hl7) {
		$obj = new stdClass();
		if($this->notEmpty($IN1[0]))     $p['pid'] = $IN1[0];

		return $obj;
	}

	/**
	 * @param array $IN1
	 * @param HL7   $hl7
	 *
	 * @return stdClass
	 */
	private function IN3ToInsuranceObj($IN1, $hl7) {
		$obj = new stdClass();
		if($this->notEmpty($IN1[0]))     $p['id'] = $IN1[0];

		return $obj;
	}
	/**
	 * @param array $p
	 * @param HL7   $hl7
	 *
	 * @return \HL7
	 */
	private function PatientObjToPID($p, $hl7){
		$PID = $hl7->addSegment('PID');
		if($this->notEmpty($p['pubpid'])) $PID->setValue('2.3',$p['pubpid']);
		if($this->notEmpty($p['pid'])) $PID->setValue('3.1',$p['pid']);
		if($this->notEmpty($p['fname'])) $PID->setValue('5.2',$p['fname']);
		if($this->notEmpty($p['mname'])) $PID->setValue('5.3',$p['mname']);
		if($this->notEmpty($p['lname'])) $PID->setValue('5.1.1',$p['lname']);
		if($this->notEmpty($p['mothers_name'])) $PID->setValue('6.2',$p['mothers_name']);
		if($this->notEmpty($p['DOB'])) $PID->setValue('7.1',$p['DOB']);
		if($this->notEmpty($p['sex'])) $PID->setValue('8',$p['sex']);
		if($this->notEmpty($p['alias'])) $PID->setValue('9.2',$p['alias']);
		if($this->notEmpty($p['race'])) $PID->setValue('10.1',$p['race']);
		if($this->notEmpty($p['address'])) $PID->setValue('11.1.1',$p['address']);
		if($this->notEmpty($p['city'])) $PID->setValue('11.3',$p['city']);
		if($this->notEmpty($p['state'])) $PID->setValue('11.4',$p['state']);
		if($this->notEmpty($p['zipcode'])) $PID->setValue('11.5',$p['zipcode']);
		if($this->notEmpty($p['country'])) $PID->setValue('11.6',$p['country']);
		if($this->notEmpty($p['home_phone'])) $PID->setValue('',$p['home_phone']);
		if($this->notEmpty($p['work_phone'])) $PID->setValue('',$p['work_phone']);
		if($this->notEmpty($p['language'])) $PID->setValue('15.1',$p['language']);
		if($this->notEmpty($p['marital_status'])) $PID->setValue('16.1',$p['marital_status']);
		if($this->notEmpty($p['pubaccount'])) $PID->setValue('18.1',$p['pubaccount']);
		if($this->notEmpty($p['SS'])) $PID->setValue('19',$p['SS']);
		if($this->notEmpty($p['drivers_license'])) $PID->setValue('20.1',$p['drivers_license']);
		if($this->notEmpty($p['drivers_license_state'])) $PID->setValue('20.2',$p['drivers_license_state']);
		if($this->notEmpty($p['drivers_license_exp'])) $PID->setValue('20.3',$p['drivers_license_exp']);
		if($this->notEmpty($p['ethnicity'])) $PID->setValue('22.1',$p['ethnicity']);
		if($this->notEmpty($p['birth_place'])) $PID->setValue('23',$p['birth_place']);
		if($this->notEmpty($p['birth_multiple'])) $PID->setValue('24',$p['birth_multiple']);
		if($this->notEmpty($p['birth_order'])) $PID->setValue('25',$p['birth_order']);
		if($this->notEmpty($p['citizenship'])) $PID->setValue('26.1',$p['citizenship']);
		if($this->notEmpty($p['is_veteran'])) $PID->setValue('27.1',$p['is_veteran']);
		if($this->notEmpty($p['death_date'])) $PID->setValue('29.1',$p['death_date']);
		if($this->notEmpty($p['deceased'])) $PID->setValue('30',$p['deceased']);
		if($this->notEmpty($p['update_date'])) $PID->setValue('33.1',$p['update_date']);
		return $hl7;
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	private function notEmpty($data){
		return isset($data) && ($data != '' && $data != '""' && $data != '\'\'');
	}
}
//$msg = <<<EOF
//MSH|^~\&|^2.16.840.1.113883.3.72.5.20^ISO|^2.16.840.1.113883.3.72.5.21^ISO||^2.16.840.1.113883.3.72.5.23^ISO|20110531140551-0500||ORU^R01^ORU_R01|NIST-LRI-GU-002.00|T|2.5.1|||AL|NE|||||LRI_Common_Component^^2.16.840.1.113883.9.16^ISO~LRI_GU_Component^^2.16.840.1.113883.9.12^ISO~LRI_RU_Component^^2.16.840.1.113883.9.14^ISO
//PID|1||PATID1234^^^&2.16.840.1.113883.3.72.5.30.2&ISO^MR||Jones^William^A||19610615|M||2106-3^White^HL70005
//ORC|RE|6^^2.16.840.1.113883.3.72.5.24^ISO|R-991133^^2.16.840.1.113883.3.72.5.25^ISO|GORD874233^^2.16.840.1.113883.3.72.5.24^ISO||||||||57422^Radon^Nicholas^^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^NPI
//OBR|1|6^^2.16.840.1.113883.3.72.5.24^ISO|R-991133^^2.16.840.1.113883.3.72.5.25^ISO|57021-8^CBC W Auto Differential panel in Blood^LN^4456544^CBC^99USI^^^CBC W Auto Differential panel in Blood|||20110103143428-0800|||||||||57422^Radon^Nicholas^^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^NPI||||||20110104170028-0800|||F|||10093^Deluca^Naddy^^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^NPI|||||||||||||||||||||CC^Carbon Copy^HL70507
//OBX|1|NM|26453-1^Erythrocytes [#/volume] in Blood^LN^^^^^^Erythrocytes [#/volume] in Blood||4.41|10*6/uL^million per microliter^UCUM|4.3 to 6.2|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|2|NM|718-7^Hemoglobin [Mass/volume] in Blood^LN^^^^^^Hemoglobin [Mass/volume] in Blood||12.5|g/mL^grams per milliliter^UCUM|13 to 18|L|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|3|NM|20570-8^Hematocrit [Volume Fraction] of Blood^LN^^^^^^Hematocrit [Volume Fraction] of Blood||41|%^percent^UCUM|40 to 52|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|4|NM|26464-8^Leukocytes [#/volume] in Blood^LN^^^^^^Leukocytes [#/volume] in Blood||105600|{cells}/uL^cells per microliter^UCUM|4300 to 10800|HH|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|5|NM|26515-7^Platelets [#/volume] in Blood^LN^^^^^^Platelets [#/volume] in Blood||210000|{cells}/uL^cells per microliter^UCUM|150000 to 350000|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|6|NM|30428-7^Erythrocyte mean corpuscular volume [Entitic volume]^LN^^^^^^Erythrocyte mean corpuscular volume [Entitic volume]||91|fL^femtoliter^UCUM|80 to 95|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|7|NM|28539-5^Erythrocyte mean corpuscular hemoglobin [Entitic mass]^LN^^^^^^Erythrocyte mean corpuscular hemoglobin [Entitic mass]||29|pg/{cell}^picograms per cell^UCUM|27 to 31|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|8|NM|28540-3^Erythrocyte mean corpuscular hemoglobin concentration [Mass/volume]^LN^^^^^^Erythrocyte mean corpuscular hemoglobin concentration [Mass/volume]||32.4|g/dL^grams per deciliter^UCUM|32 to 36|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|9|NM|30385-9^Erythrocyte distribution width [Ratio]^LN^^^^^^Erythrocyte distribution width [Ratio]||10.5|%^percent^UCUM|10.2 to 14.5|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|10|NM|26444-0^Basophils [#/volume] in Blood^LN^^^^^^Basophils [#/volume] in Blood||0.1|10*3/uL^thousand per microliter^UCUM|0 to 0.3|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|11|NM|30180-4^Basophils/100 leukocytes in Blood^LN^^^^^^Basophils/100 leukocytes in Blood||0.1|%^percent^UCUM|0 to 2|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|12|NM|26484-6^Monocytes [#/volume] in Blood^LN^^^^^^Monocytes [#/volume] in Blood||3|10*3/uL^thousand per microliter^UCUM|0.0 to 13.0|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|13|NM|26485-3^Monocytes/100 leukocytes in Blood^LN^^^^^^Monocytes/100 leukocytes in Blood||3|%^percent^UCUM|0 to 10|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|14|NM|26449-9^Eosinophils [#/volume] in Blood^LN^^^^^^Eosinophils [#/volume] in Blood||2.1|10*3/uL^thousand per microliter^UCUM|0.0 to 0.45|HH|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|15|NM|26450-7^Eosinophils/100 leukocytes in Blood^LN^^^^^^Eosinophils/100 leukocytes in Blood||2|%^percent^UCUM|0 to 6|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|16|NM|26474-7^Lymphocytes [#/volume] in Blood^LN^^^^^^Lymphocytes [#/volume] in Blood||41.2|10*3/uL^thousand per microliter^UCUM|1.0 to 4.8|HH|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|17|NM|26478-8^Lymphocytes/100 leukocytes in Blood^LN^^^^^^Lymphocytes/100 leukocytes in Blood||39|%^percent^UCUM|15.0 to 45.0|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|18|NM|26499-4^Neutrophils [#/volume] in Blood^LN^^^^^^Neutrophils [#/volume] in Blood||58|10*3/uL^thousand per microliter^UCUM|1.5 to 7.0|HH|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|19|NM|26511-6^Neutrophils/100 leukocytes in Blood^LN^^^^^^Neutrophils/100 leukocytes in Blood||55|%^percent^UCUM|50 to 73|N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|20|CWE|38892-6^Anisocytosis [Presence] in Blood^LN^^^^^^Anisocytosis [Presence] in Blood||260348001^Present ++ out of ++++^SCT^^^^^^Moderate Anisocytosis|||A|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|21|CWE|30400-6^Hypochromia [Presence] in Blood^LN^^^^^^Hypochromia [Presence] in Blood||260415000^not detected^SCT^^^^^^None seen|||N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|22|CWE|30424-6^Macrocytes [Presence] in Blood^LN^^^^^^Macrocytes [Presence] in Blood||260415000^not detected^SCT^^^^^^None seen|||N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|23|CWE|30434-5^Microcytes [Presence] in Blood^LN^^^^^^Microcytes [Presence] in Blood||260415000^not detected^SCT^^^^^^None seen|||N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|24|CWE|779-9^Poikilocytosis [Presence] in Blood by Light microscopy^LN^^^^^^Poikilocytosis [Presence] in Blood by Light microscopy||260415000^not detected^SCT^^^^^^None seen|||N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|25|CWE|10378-8^Polychromasia [Presence] in Blood by Light microscopy^LN^^^^^^Polychromasia [Presence] in Blood by Light microscopy||260415000^not detected^SCT^^^^^^None seen|||N|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|26|TX|6742-1^Erythrocyte morphology finding [Identifier] in Blood^LN^^^^^^Erythrocyte morphology finding [Identifier] in Blood||Many spherocytes present.|||A|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|27|TX|11156-7^Leukocyte morphology finding [Identifier] in Blood^LN^^^^^^Leukocyte morphology finding [Identifier] in Blood||Reactive morphology in lymphoid cells.|||A|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|28|TX|11125-2^Platelet morphology finding [Identifier] in Blood^LN^^^^^^Platelet morphology finding [Identifier] in Blood||Platelets show defective granulation.|||A|||F|||20110103143428-0800|||||20110103163428-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//SPM|1|||119297000^BLD^SCT^^^^^^Blood|||||||||||||20110103143428-0800
//EOF;

//$msg = <<<EOF
//MSH|^~\&|^2.16.840.1.113883.3.72.5.20^ISO|^2.16.840.1.113883.3.72.5.21^ISO||^2.16.840.1.113883.3.72.5.23^ISO|20110531140551-0500||ORU^R01^ORU_R01|NIST-LRI-GU-003.00|T|2.5.1|||AL|NE|||||LRI_Common_Component^^2.16.840.1.113883.9.16^ISO~LRI_GU_Component^^2.16.840.1.113883.9.12^ISO~LRI_RU_Component^^2.16.840.1.113883.9.14^ISO
//PID|1||8^^^&2.16.840.1.113883.3.72.5.30.2&ISO^MR||Jones^William^A||19610615|M||2106-3^White^HL70005
//ORC|RE|8^^2.16.840.1.113883.3.72.5.24^ISO|R-220713^^2.16.840.1.113883.3.72.5.25^ISO|GORD874244^^2.16.840.1.113883.3.72.5.24^ISO||||||||57422^Radon^Nicholas^^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^NPI
//OBR|1|8^^2.16.840.1.113883.3.72.5.24^ISO|R-220713^^2.16.840.1.113883.3.72.5.25^ISO|24331-1^Lipid 1996 panel in Serum or Plasma^LN^345789^Lipid Panel^99USI^^^Lipid 1996 panel in Serum or Plasma|||20110531123551-0800||||||56388000^hyperlipidemia^99USI^3744001^hyperlipoproteinemia^SCT^^^hyperlipoproteinemia|||57422^Radon^Nicholas^^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^NPI||||||20110611140428-0800|||F|||10092^Hamlin^Pafford^^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^NPI|||||||||||||||||||||BCC^Blind Copy^HL70507
//OBX|1|NM|2093-3^Cholesterol [Mass/volume] in Serum or Plasma^LN^^^^^^Cholesterol [Mass/volume] in Serum or Plasma||196|mg/dL^milligrams per deciliter^UCUM|Recommended: <200; Moderate Risk: 200-239 ; High Risk: >240|N|||F|||20110531123551-0800|||||20110601130551-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|2|NM|2571-8^Triglyceride [Mass/volume] in Serum or Plasma^LN^^^^^^Triglyceride [Mass/volume] in Serum or Plasma||100|mg/dL^milligrams per deciliter^UCUM|40 to 160|N|||F|||20110531123551-0800|||||20110601130551-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|3|NM|2085-9^Cholesterol in HDL [Mass/volume] in Serum or Plasma^LN^^^^^^Cholesterol in HDL [Mass/volume] in Serum or Plasma||60|mg/dL^milligrams per deciliter^UCUM|29 to 72|N|||F|||20110531123551-0800|||||20110601130551-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//OBX|4|NM|2089-1^Cholesterol in LDL [Mass/volume] in Serum or Plasma^LN^^^^^^Cholesterol in LDL [Mass/volume] in Serum or Plasma||116|mg/dL^milligrams per deciliter^UCUM|Recommended: <130; Moderate Risk: 130-159; High Risk: >160|N|||F|||20110531123551-0800|||||20110601130551-0800||||Century Hospital^^^^^&2.16.840.1.113883.3.72.5.30.1&ISO^XX^^^987|2070 Test Park^^Los Angeles^CA^90067^^B|2343242^Knowsalot^Phil^^^Dr.^^^&2.16.840.1.113883.3.72.5.30.1&ISO^L^^^DN
//SPM|1|||119297000^BLD^SCT^^^^^^Blood|||||||||||||20110531123551-0800
//EOF;

//$msg = <<<EOF
//MSH|^~\&|EPIC|EPICADT|SMS|SMSADT|199912271408|CHARRIS|ADT^A04|1817457|D|2.5|
//PID||0493575^^^2^ID 1|454721||DOE^JOHN^^^^|DOE^JOHN^^^^|19480203|M||B|254 MYSTREET AVE^^MYTOWN^OH^44123^USA||(216)123-4567|||M|NON|400003403~1129086|
//NK1||ROE^MARIE^^^^|SPO||(216)123-4567||EC|||||||||||||||||||||||||||
//PV1||O|168 ~219~C~PMA^^^^^^^^^||||277^ALLEN MYLASTNAME^BONNIE^^^^|||||||||| ||2688684|||||||||||||||||||||||||199912271408||||||002376853
//EOF;

$msg = <<<EOF
MSH|^~\&|REGADT|GOOD HEALTH HOSPITAL|GHH LAB||200712311501||ADT^A08^ADT_A01|000001|P|2.5.1|||
EVN|A04|200701101500|200701101400|01||200701101410
PID|||2^^^GOOD HEALTH HOSPITAL^MR^GOOD HEALTH HOSPI- TAL^^^USSSA^SS|253763|EVERYMAN^ADAM^A||19560129|M|||2222 HOME STREET^^ISHPEMING^MI^49849^""^||555-555-2004|555-555- 2004||S|C|10199925^^^GOOD HEALTH HOSPITAL^AN|371-66-9256||
NK1|1|NUCLEAR^NELDA|SPOUSE|6666 HOME STREET^^ISHPEMING^MI^49849^""^|555-555- 5001|555-555-5001~555-555-5001|EC1^FIRST EMERGENCY CONTACT
NK1|2|MUM^MARTHA|MOTHER|4444 HOME STREET^^ISHPEMING^MI^49849^""^|555-555 2006|555-555-2006~555-555-2006|EC2^SECOND EMERGENCY CONTACT
NK1|3
NK1|4|||6666 WORKER LOOP^^ISHPEMING^MI^49849^""^||(900)545- 1200|EM^EMPLOYER|19940605||PROGRAMMER|||WORK IS FUN, INC.
PV1||O|O/R||||0148^ATTEND^AARON^A|0148^ATTEND^AARON^A|0148^ATTEND^AARON^A|AMB|||| |||0148^ATTEND^AARON^A|S|1400|A|||||||||||||||||||GOOD HEALTH HOSPI- TAL|||||199501101410|
PV2||||||||200701101400||||||||||||||||||||||||||200301101400
OBX||ST|1010.1^BODY WEIGHT||62|kg|||||F
OBX||ST|1010.1^HEIGHT||190|cm|||||F
DG1|1|19||BIOPSY||00|
GT1|1||EVERYMAN^ADAM^A||2222 HOME STREET^^ISHPEMING^MI^49849^""^|444-33 3333|555- 555-2004||||SE^SELF|444-33 3333||||AUTO CLINIC|2222 HOME STREET^^ISHPEMING^MI^49849^""|555-555-2004|
IN1|0|0|UA1|UARE INSURED, INC.|8888 INSURERS CIRCLE^^ISHPEMING^M149849^""^||555- 555-3015|90||||||50 OK|
IN1|2|""|""
EOF;

//$msg = <<<EOF
//MSH|^~\&||OTHER REG MED CTR^1234567890^NPI|||201102171531||ADT^A04^ADT_A01|201102171531956|P|2.5.1
//EVN||201102171531
//PID|1||FL01059711^^^^PI||~^^^^^^U|||F||2106-3^White^CDCREC|^^^12^33821|||||||||||2186-5^Not Hispanic^CDCREC
//PV1||E||E||||||||||7|||||V20220217-00274^^^^VN|||||||||||||||||||||||||201102171522
//PV2|||78907^ABDOMINAL PAIN, GENERALIZED^I9CDX
//OBX|1|HD|SS001^TREATING FACILITY IDENTIFIER^PHINQUESTION||OTHER REG MED CTR^1234567890^NPI||||||F|||201102171531
//OBX|2|CWE|8661-1^CHIEF COMPLAINT:FIND:PT:PATIENT:NOM:REPORTED^LN||^^^^^^^^STOMACH ACHE||||||F|||201102171531
//OBX|3|NM|21612-7^AGE TIME PATIENT REPORTED^LN||43|a^YEAR^UCUM|||||F|||201102171531
//DG1|1||78900^ABDMNAL PAIN UNSPCF SITE^I9CDX|||A
//EOF;



//include_once(dirname(dirname(__FILE__)).'/lib/HL7/HL7.php');

//print '<pre>';
//
//$hl7 = new HL7();
//$msg = $hl7->readMessage($msg);
//print_r($msg);

$hl7 = new HL7Server();
print $hl7->Process($msg);
