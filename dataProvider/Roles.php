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

include_once ($_SESSION['root'] . '/dataProvider/ACL.php');

class Roles extends ACL
{

    /**
	 *
	 * @return mixed
	 */
	public function getRoleForm()
	{
		$items = array();
		$perms = array();
		$roles = $this -> getAllRoles();
		$categories = array(
			'General',
			'Calendar',
			'Patients',
			'Encounters',
			'Demographics',
			'Documents',
			'ePrescription',
			'Administrators',
			'Pool Areas',
			'Miscellaneous'
		);
		foreach ($this->getAllPermissions('full') as $perm)
		{
			array_push($perms, $perm);
		}
		foreach ($categories as $cat)
		{
			$item = array();
			$item['xtype'] = 'fieldset';
			$item['title'] = $cat;
			$item['layout'] = 'anchor';
			$item['labelWidth'] = 100;
			$item['defaults'] = array(
				'xtype' => 'fieldcontainer',
				'defaultType' => 'mitos.checkbox',
				'layout' => 'hbox',
				'defaults' => array('padding' => '0 100 0 0'),
				'labelWidth' => 200
			);
			$item['items'] = array();
			foreach ($perms as $perm)
			{
				$row = null;
				if (strtolower($perm['Cat']) == strtolower($item['title']))
				{
					$row['fieldLabel'] = $perm['Name'];
					$checkboxes = array();
					foreach ($roles['row'] as $role)
					{
						//TODO:  for development...  false : false
						$disable = false;
						//(strtolower($role['role_name']) == 'administrator')? false : false;
						$checkbox = array(
							'name' => strtolower($perm['Key']) . '_' . strtolower(str_replace(' ', '_', $role['role_name'])),
							'disabled' => $disable
						);
						array_push($checkboxes, $checkbox);
					}

					$row['items'] = $checkboxes;
					array_push($item['items'], $row);
				}
			}
			array_push($items, $item);
		}
		$rawStr = json_encode($items);
		$regex = '("\w*?":|"Ext\.create|\)"\})';
		$cleanItems = array();
		preg_match_all($regex, $rawStr, $rawItems);
		foreach ($rawItems[0] as $item)
		{
			array_push($cleanItems, str_replace('"', '', $item));
		}
		$itemsJsArray = str_replace('"', '\'', str_replace($rawItems[0], $cleanItems, $rawStr));
		return $itemsJsArray;
	}

	/**
	 * @return array
	 */
	public function getRolesData()
	{
        if($this->ARP == NULL) $this->ARP = MatchaModel::setSenchaModel('App.model.administration.AclRolePermissions');
		foreach ($this->ARP->load()->all() as $row)
		{
			$rows[$row['perm_key'] . '_' . $row['role_key']] = $row['value'];
		}
		return $rows;
	}

	/**
	 * @param stdClass $params
	 * @return string
	 */
	public function saveRolesData(stdClass $params)
	{
		$data = get_object_vars($params);
		function parse_boolean($val)
		{
			return $val;
		}

		foreach ($data as $key => $val)
		{
			$val = parse_boolean($val);
			if (!strpos($key, '_front_office') === false)
			{
				$this -> saveRolePerm('front_office', str_replace('_front_office', '', $key), $val);
			}
			elseif (!strpos($key, '_auditor') === false)
			{
				$this -> saveRolePerm('auditor', str_replace('_auditor', '', $key), $val);
			}
			elseif (!strpos($key, '_clinician') === false)
			{
				$this -> saveRolePerm('clinician', str_replace('_clinician', '', $key), $val);
			}
			elseif (!strpos($key, '_physician') === false)
			{
				$this -> saveRolePerm('physician', str_replace('_physician', '', $key), $val);
			}
			elseif (!strpos($key, '_administrator') === false)
			{
				$this -> saveRolePerm('administrator', str_replace('_administrator', '', $key), $val);
			}
		}

		$this -> conn -> execEvent('User Roles Updated values are: ' . json_encode($data));

		return array('success' => true);
	}

	/**
	 * @param $roleKey
	 * @param $permKey
	 * @param $val
	 * @return void
	 */
	private function saveRolePerm($roleKey, $permKey, $val)
	{
        if($this->ARP == NULL) $this->ARP = MatchaModel::setSenchaModel('App.model.administration.AclRolePermissions');
		$data = array();
		$data['value'] = $val;
        $data['role_key'] = $roleKey;
        $data['perm_key'] = $permKey;
        $this->ARP->save($data);
	}

}
