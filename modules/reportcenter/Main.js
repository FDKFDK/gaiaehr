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

Ext.define('Modules.reportcenter.Main',
{
	extend : 'Modules.Module',
	constructor : function()
	{
		var me = this;
		/**
		 * @param panel     (Ext.component)     Component to add to MainPanel
		 */
		me.addAppPanel(Ext.create('Modules.reportcenter.view.ReportCenter'));
		me.addAppPanel(Ext.create('Modules.reportcenter.view.ReportPanel'));
		/**
		 * funtion to add navigation links
		 * @param parentId  (string)            navigation node parent ID,
		 * @param node      (object || array)   navigation node configuration properties
		 */
		me.addNavigationNodes('root',
		{
			//text	:i18n('client_list_report'),
			text : 'Report Center',
			leaf : true,
			cls : 'file',
			iconCls : 'icoReport',
			id : 'Modules.reportcenter.view.ReportCenter'
		});
		me.callParent();
	}
}); 