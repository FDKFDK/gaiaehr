/**
 * Created by JetBrains PhpStorm.
 * User: Ernesto J. Rodriguez (Certun)
 * File:
 * Date: 10/29/11
 * Time: 4:45 PM
 */
Ext.define('App.classes.combo.Languages', {
	extend       : 'Ext.form.ComboBox',
	alias        : 'widget.languagescombo',
	initComponent: function() {
		var me = this;

		Ext.define('LanguagesComboModel', {
			extend: 'Ext.data.Model',
			fields: [
				{ name: 'name', type: 'string' },
				{ name: 'value', type: 'string' }
			],
			proxy : {
				type: 'direct',
				api : {
					read: CombosData.getAvailableLanguages
				}
			}
		});

		me.store = Ext.create('Ext.data.Store', {
			model   : 'LanguagesComboModel',
			autoLoad: true
		});

		Ext.apply(this, {
			editable    : false,
			queryMode   : 'local',
			valueField  : 'value',
			displayField: 'name',
			emptyText   : i18n['select'],
			store       : me.store
		}, null);
		me.callParent();
	}
});