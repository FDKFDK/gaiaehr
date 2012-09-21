//******************************************************************************
// ClientListReport.js
// Client List (Patient) Report
// v0.0.1
// 
// Author: Gino Rivera Falu (GI Technologies)
// Modified:
// 
// GaiaEHR (Electronic Health Records) 2012
//******************************************************************************

Ext.define('App.view.reports.ReportCenter', {
	extend       : 'App.classes.RenderPanel',
	id           : 'panelReportCenter',
	pageTitle    : 'Report_Center',
	initComponent: function() {
		var me = this;

        me.reports = Ext.create('Ext.panel.Panel',{
            layout:'auto'
        });

		me.pageBody = [ me.reports ];
		me.callParent(arguments);
	
	},

    addCategory:function(category){
        var me = this;
        return me.reports.add(
            Ext.create('Ext.container.Container',{
                cls:'CategoryContainer',
                width:200,
                layout:'anchor',
                items:[
                    {
                        xtype:'container',
                        cls:'title',
                        margin:'0 0 5 0',
                        html:category
                    }
                ]
            })
        );
    },

    addReportByCategory:function(category, text, fn){
        return category.add(
            Ext.create('Ext.button.Button',{
                anchor:'100%',
                margin:'0 0 5 0',
                textAlign:'left',
                text:text,
                handler:fn
            })
        );
    },

    goToReportPanel:function(){
        app.MainPanel.getLayout().setActiveItem('panelReportPanel');
    },
	
	/**
	 * This function is called from MitosAPP.js when
	 * this panel is selected in the navigation panel.
	 * place inside this function all the functions you want
	 * to call every this panel becomes active
	 */
	onActive: function(callback) 
	{
		callback(true);
	}

}); //ens oNotesPage class