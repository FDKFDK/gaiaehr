 /*
 * -----------------------------------------------------------------------------------------------------------
 * brief: Viewport.js (CORE)
 * ----------------------------------------------------------------------------------------------------------- 
 * Description: This are the viewport, the absolute panel of GaiaEHR application
 * this will manage all the panels on the application, this file should not
 * be modified by extrangers. 
 * 
 * Third-party companies: If you want to add a extra app's, widgets, modules, or another other improvement
 * to the application you should create it using the documentation on How To Create (Modules, PlugIns, and Widgets)
 * All other things are going to the CORE of the application.
 * 
 * Remember this is a BETA software, all the structure are subject to change.
 * When the software are more mature, we will maintain the API and CORE for a LTS version (Long Term Support).
 * 
 * Enjoy the application!
 * 
 */
Ext.define('App.view.Viewport', {
	extend  : 'Ext.Viewport',
	requires: window.requires,
    // app settings
	minWidthToFullMode: 1585,       // full mode = nav expanded
	currency          : '$',        // currency used
    activityMonitorInterval: 60,    // in seconds - interval to check for mouse and keyboard activity
    activityMonitorMaxInactive: 10, // in minutes - Maximum time application can be inactive (no mouse or keyboard imput)
    cronTaskInterval:10,            // in seconds - interval to run me.cronTask (check PHP session, refresh Patient Pool Areas, and PHP Cron Job)
    // end app settings

	initComponent: function() 
	{
		Ext.tip.QuickTipManager.init();
		var me = this;
		me.lastCardNode = null;
		me.currCardCmp = null;

        me.fullMode = window.innerWidth >= me.minWidthToFullMode;

        me.patient = {
            name    : null,
            pid     : null,
            sex     : null,
            dob     : null,
            age     : null,
            eid     : null,
            readOnly: false
        };

		me.user = window.user;
		/**
		 * TaskScheduler
		 * This will run all the procedures inside the checkSession
		 */
		me.cronTask = {
			scope   : me,
			run     : function() 
			{
				me.checkSession();
				me.getPatientsInPoolArea();
				CronJob.run();
			},
			interval: me.cronTaskInterval * 1000
		};

		/*
		 * The store for the Navigation Tree menu.
		 */ 
		me.storeTree = Ext.create('App.store.navigation.Navigation',
		{
			autoLoad : true,
			listeners: {
				scope: me,
				load : me.afterNavigationLoad
			}
		});

		/**
		 * This store will handle the patient pool area
		 */
		me.patientPoolStore = Ext.create('App.store.areas.PoolArea');

		/*
		 * TODO: this should be managed by the language files
		 * The language file has a definition for this.
		 */ 
		if(me.currency == '$') 
		{
			me.icoMoney = 'icoDollar';
		} else if(me.currency == '€') 
		{
			me.icoMoney = 'icoEuro';
		} else if(me.currency == '£') 
		{
			me.icoMoney = 'icoLibra';
		} else if(me.currency == '¥') 
		{
			me.icoMoney = 'icoYen';
		}

		/**
		 * GaiaEHR Support Page
		 */
		me.winSupport = Ext.create('Ext.window.Window', 
		{
			title        : i18n['support'],
			closeAction  : 'hide',
			bodyStyle    : 'background-color: #ffffff; padding: 5px;',
			animateTarget: me.Footer,
			resizable    : false,
			draggable    : false,
			maximizable  : false,
			autoScroll   : true,
			maximized    : true,
			dockedItems  : {
				xtype: 'toolbar',
				dock : 'top',
				items: ['-', 
				{
					text   : "List issues",
					iconCls: 'list',
					action : 'http://GaiaEHR.org/projects/GaiaEHR001/issues',
					scope  : me,
					handler: me.showMiframe
				}, '-', 
				{
					text   : "Create an issue",
					iconCls: 'icoAddRecord',
					action : 'http://GaiaEHR.org/projects/GaiaEHR001/issues/new',
					scope  : me,
					handler: me.showMiframe
				}]
			}
		});

		/**
		 * header Panel
		 */
		me.Header = Ext.create('Ext.container.Container', 
		{
			region     : 'north',
			height     : 44,
			split      : false,
			collapsible: false,
			collapsed  : true,
			frame      : false,
			border     : false,
			bodyStyle  : 'background: transparent',
			margins    : '0 0 0 0'
		});

        me.patientBtn = me.Header.add({
            xtype    : 'button',
            scale    : 'large',
            style    : 'float:left',
            margin   : 0,
            scope    : me,
            handler  : me.openPatientSummary,
            listeners: {
                scope      : me,
                afterrender: me.patientBtnRender
            },
            tpl      : me.patientBtnTpl()
        });
        me.patientOpenVisitsBtn = me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:left',
            margin : '0 0 0 3',
            cls    : 'headerLargeBtn',
            padding: 0,
            iconCls: 'icoBackClock',
            scope  : me,
            handler: me.openPatientVisits,
            tooltip: i18n['open_patient_visits_history']
        });
        if(acl['add_encounters']){
            me.patientCreateEncounterBtn = me.Header.add({
                xtype  : 'button',
                scale  : 'large',
                style  : 'float:left',
                margin : '0 0 0 3',
                cls    : 'headerLargeBtn',
                padding: 0,
                iconCls: 'icoClock',
                scope  : me,
                handler: me.createNewEncounter,
                tooltip: i18n['create_new_encounter']
            });
        }
        me.patientCloseCurrEncounterBtn = me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:left',
            margin : '0 0 0 3',
            cls    : 'headerLargeBtn',
            padding: 0,
            iconCls: 'icoArrowDown',
            scope  : me,
            handler: me.stowPatientRecord,
            tooltip: i18n['show_patient_record']
        });
        me.patientCheckOutBtn = me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:left',
            margin : '0 0 0 3',
            cls    : 'headerLargeBtn',
            padding: 0,
            iconCls: 'icoCheckOut',
            scope  : me,
            handler: me.chargePatient,
            tooltip: i18n['check_out_patient']
        });
        me.patientChargeBtn = me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:left',
            margin : '0 0 0 3',
            cls    : 'headerLargeBtn',
            padding: 0,
            iconCls: me.icoMoney,
            scope  : me,
            handler: me.onPaymentEntryWindow,
            tooltip: i18n['payment_entry']
        });
        me.Header.add({
            xtype      : 'panel',
            bodyPadding: '8 11 5 11',
            margin     : '0 0 0 3',
            style      : 'float:left',
            items      : [
                {
                    xtype     : 'patienlivetsearch',
                    emptyText : i18n['patient_live_search'] + '...',
                    fieldStyle: me.fullMode ? 'width:300' : 'width:250',
                    listeners : {
                        scope : me,
                        select: me.liveSearchSelect,
                        blur  : function(combo) {
                            combo.reset();
                        }
                    }
                }
            ]
        });
        me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:left',
            margin : '0 0 0 3',
            padding: 4,
            itemId : 'patientNewReset',
            iconCls: 'icoAddPatient',
            scope  : me,
            handler: me.newPatient,
            tooltip: i18n['create_a_new_patient']
        });
        me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:left',
            margin : '0 0 0 3',
            cls    : 'headerLargeBtn emerBtn',
            overCls: 'emerBtnOver',
            padding: 0,
            itemId : 'createEmergency',
            iconCls: 'icoEmer',
            scope  : me,
            handler: me.createEmergency,
            tooltip: i18n['create_new_emergency']
        });
        me.Header.add({
            xtype    : 'button',
            text     : user.title + ' ' + user.lname,
            scale    : 'large',
            iconCls  : 'icoDoctor',
            iconAlign: 'left',
            cls      : 'drButton',
            style    : 'float:right',
            margin   : '0 0 0 3',
            menu     : [
                {
                    text   : i18n['my_account'],
                    iconCls: 'icoArrowRight',
                    handler: function() {
                        me.navigateTo('panelMyAccount');
                    }
                },
                {
                    text   : i18n['my_settings'],
                    iconCls: 'icoArrowRight',
                    handler: function() {
                        me.navigateTo('panelMySettings');
                    }
                },
                {
                    text   : i18n['logout'],
                    iconCls: 'icoArrowRight',
                    scope  : me,
                    handler: me.appLogout
                }
            ]
        });
        me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:right',
            margin : '0 0 0 3',
            cls    : 'headerLargeBtn',
            padding: 0,
            itemId : 'patientCheckIn',
            iconCls: 'icoLog',
            scope  : me,
            handler: me.onPatientLog,
            tooltip: i18n['arrival_log']
        });
        me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:right',
            margin : '0 0 0 3',
            cls    : 'headerLargeBtn',
            padding: 0,
            itemId : 'patientPoolArea',
            iconCls: 'icoPoolArea',
            scope  : me,
            handler: me.goToPoolAreas,
            tooltip: i18n['pool_areas']
        });
        me.Header.add({
            xtype  : 'button',
            scale  : 'large',
            style  : 'float:right',
            margin : '0 0 0 3',
            cls    : 'headerLargeBtn',
            padding: 0,
            itemId : 'floorPlans',
            iconCls: 'icoZoneAreasBig',
            scope  : me,
            handler: me.goToFloorPlans,
            tooltip: i18n['floor_plans']
        });

        /**
		 * The panel definition for the the TreeMenu & the support button
		 */
		me.navColumn = Ext.create('Ext.panel.Panel', {
			title      : i18n['navigation'],
			stateId    : 'navColumn',
			layout     : 'border',
			region     : 'west',
			width      : 200,
			split      : true,
			collapsible: true,
			collapsed  : false,
			items      : [
				{
					xtype      : 'treepanel',
					region     : 'center',
					cls        : 'nav_tree',
					hideHeaders: true,
					rootVisible: false,
					border     : false,
					store      : me.storeTree,
					width      : 200,
					plugins    : [
						{
                            ptype: 'nodedisabled'
                        }
					],
					//					root       : {
					//						nodeType : 'async',
					//						draggable: false
					//					},
					listeners  : {
						scope          : me,
						selectionchange: me.onNavigationNodeSelected
					}
				},
				me.patientPoolArea = Ext.create('Ext.Panel',{
					title      : i18n['patient_pool_areas'],
					layout     : 'fit',
					region     : 'south',
					bodyPadding: 5,
					height     : 25,
					cls        : 'patient-pool',
					split      : true,
					collapsible: true,
					border     : false,
//                    overflowY: 'auto',
					items      : [
						{
							xtype            : 'dataview',
							loadMask         : false,
							cls              : 'patient-pool-view',
							tpl              : '<tpl for=".">' +
								'<div class="patient-pool-btn x-btn x-btn-default-large {priority}">' +
								'<div class="patient_btn_img"><img src="{photoSrc}" width="35" height="35"></div>' +
								'<div class="patient_btn_info">' +
								'<div class="patient-name">{shortName}</div>' +
								'<div class="patient-name">#{pid} ({poolArea})</div>' +
								'</div>' +
								'</div>' +
								'</tpl>',
							itemSelector     : 'div.patient-pool-btn',
							overItemCls      : 'patient-over',
							selectedItemClass: 'patient-selected',
							singleSelect     : true,
							store            : me.patientPoolStore,
							listeners        : {
								scope : me,
								render: me.initializeOpenEncounterDragZone
							}
						}
                    ]
				})
			],
			dockedItems: [
				{
					xtype  : 'toolbar',
					dock   : 'bottom',
					border : true,
					margin : '3 0 0 0',
					padding: 5,
					layout : {
						type: 'hbox',
						pack: 'center'
					},
					items  : ['-', {
						xtype  : 'button',
						frame  : true,
						text   : 'GaiaEHR Support',
						iconCls: 'icoHelp',
						action : 'http://gaiaehr.org/',
						scope  : me,
						handler: me.showMiframe
					}, '-']
				}
			],
			listeners  : {
				scope         : me,
				beforecollapse: me.navCollapsed,
				beforeexpand  : me.navExpanded

			}
		});

		/**
		 * MainPanel is where all the pages are displayed
		 */
		me.MainPanel = Ext.create('Ext.container.Container', {
			region   : 'center',
			layout   : 'card',
			border   : true,
			itemId   : 'MainPanel',
			defaults : { layout: 'fit', xtype: 'container' },
			listeners: {
				scope      : me,
				afterrender: me.initializeOpenEncounterDropZone
			}
		});
		
        /**
         * General Area
         */
        me.MainPanel.add(Ext.create('App.view.dashboard.Dashboard')); // TODO: panels
        me.MainPanel.add(Ext.create('App.view.calendar.Calendar'));
        me.MainPanel.add(Ext.create('App.view.messages.Messages'));
        me.MainPanel.add(Ext.create('App.view.search.PatientSearch'));
        me.MainPanel.add(Ext.create('App.view.areas.FloorPlan'));

        /**
         * Patient Area
         */
        me.MainPanel.add(Ext.create('App.view.patient.NewPatient'));
        me.MainPanel.add(Ext.create('App.view.patient.Summary'));
        me.MainPanel.add(Ext.create('App.view.patient.Visits'));
        me.MainPanel.add(Ext.create('App.view.patient.Encounter'));
        me.MainPanel.add(Ext.create('App.view.patient.VisitCheckout'));
        /**
         * Fees Area
         */
        me.MainPanel.add(Ext.create('App.view.fees.Billing'));
        me.MainPanel.add(Ext.create('App.view.fees.Payments'));
        /**
         * Miscellaneous
         */
        me.MainPanel.add(Ext.create('App.view.miscellaneous.Addressbook'));
        me.MainPanel.add(Ext.create('App.view.miscellaneous.MyAccount'));
        me.MainPanel.add(Ext.create('App.view.miscellaneous.MySettings'));
        me.MainPanel.add(Ext.create('App.view.miscellaneous.OfficeNotes'));
        me.MainPanel.add(Ext.create('App.view.miscellaneous.Websearch'));
        
        me.ppdz = me.MainPanel.add(Ext.create('App.view.areas.PatientPoolDropZone'));

		if(acl['access_gloabal_settings']) me.MainPanel.add(Ext.create('App.view.administration.Globals'));
		if(acl['access_facilities']) me.MainPanel.add(Ext.create('App.view.administration.Facilities'));
		if(acl['access_users']) me.MainPanel.add(Ext.create('App.view.administration.Users'));
		if(acl['access_practice']) me.MainPanel.add(Ext.create('App.view.administration.Practice'));
		if(acl['access_data_manager']) me.MainPanel.add(Ext.create('App.view.administration.DataManager'));
		if(acl['access_preventive_care']) me.MainPanel.add(Ext.create('App.view.administration.PreventiveCare'));
		if(acl['access_medications']) me.MainPanel.add(Ext.create('App.view.administration.Medications'));
		if(acl['access_floor_plans']) me.MainPanel.add(Ext.create('App.view.administration.FloorPlans'));
		if(acl['access_roles']) me.MainPanel.add(Ext.create('App.view.administration.Roles'));
		if(acl['access_layouts']) me.MainPanel.add(Ext.create('App.view.administration.Layout'));
		if(acl['access_lists']) me.MainPanel.add(Ext.create('App.view.administration.Lists'));
		if(acl['access_event_log']) me.MainPanel.add(Ext.create('App.view.administration.Log'));
		if(acl['access_documents']) me.MainPanel.add(Ext.create('App.view.administration.Documents'));

        me.MainPanel.add(Ext.create('App.view.administration.ExternalDataLoads'));

		/**
		 * Footer Panel
		 */
		me.Footer = Ext.create('Ext.container.Container', {
			height : me.fullMode ? 30 : 60,
			split  : false,
			padding: '3 0',
			region : 'south',
			items  : [
				{
					xtype            : 'dataview',
					margin           : '0 0 3 0',
					hidden           : true,
					hideMode         : 'offsets',
					cls              : 'patient-pool-view-footer x-toolbar x-toolbar-default x-box-layout-ct',
					tpl              : '<div class="x-toolbar-separator x-toolbar-item x-toolbar-separator-horizontal" style="float:left; margin-top:5px;" role="presentation" tabindex="-1"></div>' +
						'<tpl for=".">' +
						'<div class="patient-pool-btn-small x-btn x-btn-default-small {priority}" style="float:left">' +
						'<div class="patient_btn_info">' +
						'<div class="patient-name">{name} ({pid})</div>' +
						'</div>' +
						'</div>' +
						'<div class="x-toolbar-separator x-toolbar-item x-toolbar-separator-horizontal" style="float:left; margin-top:5px; margin-left:3px;" role="presentation" tabindex="-1"></div>' +
						'</tpl>',
					itemSelector     : 'div.patient-pool-btn-small',
					overItemCls      : 'patient-over',
					selectedItemClass: 'patient-selected',
					singleSelect     : true,
					loadMask         : false,
					store            : me.patientPoolStore,
					listeners        : {
						render: me.initializeOpenEncounterDragZone
					}
				},
				{
					xtype: 'toolbar',
					dock : 'bottom',
					items: [
						{
							text    : 'Copyright (C) 2011 GaiaEHR (Electronic Health Records) |:|  Open Source Software operating under GPLv3 ',
							iconCls : 'icoGreen',
							disabled: true,
							action  : 'http://GaiaEHR.org/projects/GaiaEHR001',
							scope   : me,
							handler : me.showMiframe
						},
						'->',
						{
							text   : i18n['news'],
							action : 'http://GaiaEHR.org/projects/GaiaEHR001/news',
							scope  : me,
							handler: me.showMiframe
						},
						'-',
						{
							text   : i18n['wiki'],
							action : 'http://gaiaehr.org/',
							scope  : me,
							handler: me.showMiframe
						},
						'-',
						{
							text   : i18n['issues'],
							action : 'http://gaiaehr.org/',
							scope  : me,
							handler: me.showMiframe
						},
						'-',
						{
							text   : i18n['forums'],
							action : 'http://gaiaehr.org/',
							scope  : me,
							handler: me.showMiframe
						}
					]
				}
			]
		});

		me.MedicalWindow = Ext.create('App.view.patient.windows.Medical');
		me.ChartsWindow = Ext.create('App.view.patient.windows.Charts');
		me.PaymentEntryWindow = Ext.create('App.view.fees.PaymentEntryWindow');
		me.PreventiveCareWindow = Ext.create('App.view.patient.windows.PreventiveCare');
		me.NewDocumentsWindow = Ext.create('App.view.patient.windows.NewDocuments');
		me.DocumentViewerWindow = Ext.create('App.view.patient.windows.DocumentViewer');

		me.layout = { type: 'border', padding: 3 };
		me.defaults = { split: true };
		me.items = [ me.Header, me.navColumn, me.MainPanel, me.Footer ];

		me.listeners = {
			render : me.appRender,
			beforerender: me.beforeAppRender
		};

		me.callParent(arguments);

		me.signature = Ext.create('App.view.signature.SignatureWindow');

	},

	/*
	 * Show the medical window dialog.
	 */ 
	onMedicalWin: function(btn) {
		this.MedicalWindow.show();
		this.MedicalWindow.down('toolbar').getComponent(btn.action).toggle(true);
		this.MedicalWindow.cardSwitch(btn);
	},

	/*
	 * Show the Charts window dialog.
	 */ 
	onChartsWin      : function() {
		this.ChartsWindow.show();
	},
	
	/*
	* Show the Document window dialog.
	*/ 
	onNewDocumentsWin: function(action) {

		this.NewDocumentsWindow.eid = this.patient.eid;
		this.NewDocumentsWindow.pid = this.patient.pid;
		this.NewDocumentsWindow.show();
		this.NewDocumentsWindow.cardSwitch(action);
	},

	onWebCamComplete: function(msg) {
		var panel = this.getActivePanel();
		if(panel.id == 'panelSummary') {
			panel.completePhotoId();
		}
		this.msg('Sweet!', i18n['patient_image_saved']);
	},

	onPatientLog: function() {
		if(this.patientArrivalLog) {
			this.patientArrivalLog.show();
		} else {
			this.patientArrivalLog = Ext.create('App.view.patient.windows.ArrivalLog').show();
		}
	},

	/*
	 * Show the Payment Entry window dialog.
	 */ 
	onPaymentEntryWindow: function() {
		this.PaymentEntryWindow.show();
	},

	/*
	 * Show the new patient form panel.
	 */ 
	newPatient: function() {
		var me = this;
		me.navigateTo('panelNewPatient');
	},

	createEmergency: function() {
		var me = this, emergency;

		Ext.Msg.show({
		     title: i18n['wait'] + '!!!',
		     msg: i18n['are_you_sure_you_want_to_create_a_new'] + ' <span style="color: red">"' + i18n['emergency'] + '"</span>?',
		     buttons: Ext.Msg.YESNO,
		     icon: Ext.Msg.WARNING,
			fn:function(btn){
				if(btn == 'yes'){
					Emergency.createNewEmergency(function(provider, response){
						emergency = response.result.emergency;
						if(response.result.success){
							me.setPatient(emergency.pid,emergency.name, function(){
								me.openEncounter(emergency.eid);
							});
							me.msg('Sweet!',emergency.name + ' ' + i18n['created'])
						}
					});
				}
			}
		});

	},

	/*
	 * Show the Create New Encounter panel.
	 */ 
	createNewEncounter: function() {
		var me = this;
		if(acl['access_encounters'] && acl['add_encounters']) {
			me.navigateTo('panelEncounter', function(success) {
				if(success) {
					me.currCardCmp.newEncounter();
				}
			});
		} else {
			me.accessDenied();
		}
	},

	openPatientSummary: function() {
		var me = this;
//		if(me.currCardCmp == Ext.getCmp('panelSummary')) {
//			var same = true;
//		}
        if(me.currCardCmp == Ext.getCmp('panelSummary')) {
            me.currCardCmp.onActive();
        }else{
            me.navigateTo('panelSummary');
        }
//		me.navigateTo('panelSummary', function() {
//			if(same) {
//				me.currCardCmp.onActive();
//			}
//		});
	},

	stowPatientRecord: function() {
		this.unsetPatient();
		this.navigateTo('panelDashboard');
	},

	openEncounter: function(eid) {
		var me = this;
		if(acl['access_encounters']) {
			me.navigateTo('panelEncounter', function(success) {
				if(success) {
					me.currCardCmp.openEncounter(eid);
				}
			});
		} else {
			me.accessDenied();
		}
	},

	checkOutPatient: function(eid) {
		var me = this;

		me.navigateTo('panelVisitCheckout', function(success) {
			if(success) {
				me.currCardCmp.setPanel(eid);

			}
		});
	},

	chargePatient: function() {
		this.navigateTo('panelVisitPayment');
	},

	openPatientVisits: function() {
		this.navigateTo('panelVisits');
	},

	goToPoolAreas: function() {
		this.navigateTo('panelPoolArea');
	},

	goToFloorPlans: function() {
		this.navigateTo('panelAreaFloorPlan');
	},

	navigateTo: function(id, callback) {
		var tree = this.navColumn.down('treepanel'),
			treeStore = tree.getStore(),
			sm = tree.getSelectionModel(),
			node = treeStore.getNodeById(id);

		sm.select(node);
		if(typeof callback == 'function') {
			callback(true);
		}
	},

	navigateToDefault: function() {
		this.navigateTo('panelDashboard');
	},

	afterNavigationLoad: function() {
		this.fullMode ? this.navColumn.expand() : this.navColumn.collapse();
		this.navigateToDefault();
		this.removeAppMask();
		this.setTask(true);

	},

	onNavigationNodeSelected: function(model, selected) {
		var me = this;
		if(0 < selected.length) {
			if(selected[0].data.leaf) {
				var tree = me.navColumn.down('treepanel'),
					sm = tree.getSelectionModel(),
					card = selected[0].data.id,
					layout = me.MainPanel.getLayout(),
					cardCmp = Ext.getCmp(card);

				me.currCardCmp = cardCmp;
				layout.setActiveItem(card);
				cardCmp.onActive(function(success) {
					(success) ? me.lastCardNode = sm.getLastSelected() : me.goBack();
				});
			}
		}
	},

	goBack: function() {
		var tree = this.navColumn.down('treepanel'),
			sm = tree.getSelectionModel();
		sm.select(this.lastCardNode);
	},

	navCollapsed: function() {
		var me = this,
            navView = me.patientPoolArea,
			foot = me.Footer, footView;

		if(foot) {
			footView = foot.down('dataview');
			foot.setHeight(60);
			footView.show();
		}
        me.navColumn.isCollapsed = true;
		navView.hide();
	},

	navExpanded: function() {
		var me = this,
            navView = me.patientPoolArea,
			foot = me.Footer, footView;

		if(foot) {
			footView = foot.down('dataview');
			foot.setHeight(30);
			footView.hide();
		}
        me.navColumn.isCollapsed = false;
		navView.show();
	},

	/*
	 * Function to get the current active panel.
	 * NOTE: This may be used on all the application.
	 */
	getActivePanel: function() 
	{
		return this.MainPanel.getLayout().getActiveItem();
	},

	liveSearchSelect: function(combo, selection) {
		var me = this,
			post = selection[0];
		if(post) {
			Patient.currPatientSet({pid: post.get('pid')}, function() {
				me.setPatient(post.get('pid'), post.get('fullname'), function() {
					me.openPatientSummary();
				});
			});
		}
	},

	setPatient: function(pid, fullname, callback) {
		var me = this;

		me.unsetPatient(function() {
			Patient.currPatientSet({ pid: pid }, function(provider, response) {
				var data = response.result, msg1, msg2;
				if(data.readOnly) {
					msg1 = data.user + ' ' + i18n['is_currently_working_with'] + ' "' + data.patient.name + '" ' + i18n['in'] + ' "' + data.area + '" ' + i18n['area'] + '.<br>' +
						i18n['override_read_mode_will_remove_the_patient_from_previous_user'] + '.<br>' +
						i18n['do_you_would_like_to_override_read_mode'];
					msg2 = data.user + ' ' + i18n['is_currently_working_with'] + ' "' + data.patient.name + '" ' + i18n['in'] + ' "' + data.area + '" ' + i18n['area'] + '.<br>';
					Ext.Msg.show({
						title  : i18n['wait'] + '!!!',
						msg    : data.overrideReadOnly ? msg1 : msg2,
						buttons: data.overrideReadOnly ? Ext.Msg.YESNO : Ext.Msg.OK,
						icon   : Ext.MessageBox.WARNING,
						fn     : function(btn) {
							continueSettingPatient(btn != 'yes');
						}
					});
				} else {
					continueSettingPatient(false);
				}

				function continueSettingPatient(readOnly) {

	                me.patient = {
                        pid     : data.patient.pid,
                        name    : data.patient.name,
                        sex     : data.patient.sex,
                        dob     : data.patient.dob,
                        age     : data.patient.age,
                        priority: data.patient.priority,
                        readOnly: readOnly,
                        eid     : null
                    };

                    me.patientBtn.update({pid:data.patient.pid, name:data.patient.name});
					me.patientBtn.addCls(data.patient.priority);
					me.patientBtn.enable();
					if(me.patientOpenVisitsBtn) me.patientOpenVisitsBtn.enable();
					if(me.patientCreateEncounterBtn) me.patientCreateEncounterBtn.enable();
					if(me.patientCloseCurrEncounterBtn) me.patientCloseCurrEncounterBtn.enable();
					if(me.patientChargeBtn) me.patientChargeBtn.enable();
					if(me.patientCheckOutBtn) me.patientCheckOutBtn.enable();
					if(typeof callback == 'function') callback(me.patient);
				}
			});
		});
	},

    unsetPatient: function(callback) {
		var me = this;
		Patient.currPatientUnset(function() {
			me.currEncounterId = null;
            me.patient = {
                pid     : null,
                name    : null,
                sex     : null,
                dob     : null,
                age     : null,
                eid     : null,
                priority: null,
                readOnly: false
            };

			me.patientButtonRemoveCls();
			if(typeof callback == 'function'){
				callback(true);
			}else{
				if(me.patientCreateEncounterBtn) me.patientCreateEncounterBtn.disable();
				if(me.patientOpenVisitsBtn) me.patientOpenVisitsBtn.disable();
				if(me.patientCloseCurrEncounterBtn) me.patientCloseCurrEncounterBtn.disable();
				if(me.patientChargeBtn) me.patientChargeBtn.disable();
				if(me.patientCheckOutBtn) me.patientCheckOutBtn.disable();
				me.patientBtn.disable();
				me.patientBtn.update({ pid: 'record number', name: i18n['no_patient_selected']});
			}
		});
	},

	patientButtonRemoveCls:function(){
        var me = this;
        me.patientBtn.removeCls('Minimal');
        me.patientBtn.removeCls('Delayed');
        me.patientBtn.removeCls('Immediate');
        me.patientBtn.removeCls('Expectant');
        me.patientBtn.removeCls('Deceased');
	},

	showMiframe: function(btn) {
		var me = this, src = btn.action;
        me.winSupport.remove(me.miframe);
        me.winSupport.add(me.miframe = Ext.create('App.classes.ManagedIframe', {src: src}));
        me.winSupport.show();
	},

	msg: function(title, format) {
		if(!this.msgCt) {
			this.msgCt = Ext.core.DomHelper.insertFirst(document.body, {id: 'msg-div'}, true);
		}
		this.msgCt.alignTo(document, 't-t');
		var s = Ext.String.format.apply(String, Array.prototype.slice.call(arguments, 1)),
			m = Ext.core.DomHelper.append(this.msgCt, {html: '<div class="msg"><h3>' + title + '</h3><p>' + s + '</p></div>'}, true);
		m.slideIn('t').pause(3000).ghost('t', {remove: true});
	},

	checkSession: function() {
		authProcedures.ckAuth(function(provider, response) {
			if(!response.result.authorized) {
					window.location = './';
			}
		});
	},

	patientBtnTpl: function() {
		return Ext.create('Ext.XTemplate',
			'<div class="patient_btn  {priority}">',
			'<div class="patient_btn_img"><img src="resources/images/icons/user_32.png"></div>',
			'<div class="patient_btn_info">',
			'<div class="patient_btn_name">{name}</div>',
			'<div class="patient_btn_record">( {pid} )</div>',
			'</div>',
			'</div>');
	},

	patientBtnRender: function(btn) {
		this.unsetPatient();
		this.initializePatientPoolDragZone(btn)
	},

	getPatientsInPoolArea: function() {
		var me = this,
            poolArea = me.patientPoolArea,
			height = 35;
		this.patientPoolStore.load({
			callback: function(records) {
				if(records.length >= 1) {
                    for(var i=0; i < records.length; i++ ){
                        height = height + 45;
                    }
				} else {
					height = 25;
				}
                if(me.navColumn.collapsed === false && !me.navColumn.isCollapsingOrExpanding){
                    height = (height > 300) ? 300 : height;
    				poolArea.down('dataview').refresh();
    				poolArea.setHeight(height);
                }
			}
		});

		this.ppdz.reloadStores();

	},


    cancelAutoLogout:function(){
        var me = this;
        me.el.unmask();
        me.LogoutTask.stop(me.LogoutTaskTimer);
        me.logoutWarinigWindow.destroy();
        delete me.logoutWarinigWindow;
        App.classes.ActivityMonitor.start();
    },

    startAutoLogout:function(){
        var me = this;
        me.logoutWarinigWindow = Ext.create('Ext.Container',{
            floating:true,
            cls:'logout-warning-window',
            html:'Logging Out in...',
            seconds:10
        }).show();

        app.el.mask();

        if(!me.LogoutTask) me.LogoutTask = new Ext.util.TaskRunner();
        if(!me.LogoutTaskTimer){
            me.LogoutTaskTimer = me.LogoutTask.start({
                scope:me,
                run: me.logoutCounter,
                interval: 1000
            });
        }else{
            me.LogoutTask.start(me.LogoutTaskTimer);
        }


    },

    logoutCounter:function(){
        var me = this,
            sec = me.logoutWarinigWindow.seconds - 1;
        if(sec <= 0) {
            me.logoutWarinigWindow.update('Logging Out... Bye! Bye!');
            me.appLogout(true);
        }else{
            me.logoutWarinigWindow.update('Logging Out in '+sec+'sec');
            me.logoutWarinigWindow.seconds = sec;
            say('Logging Out in '+sec+'sec');
        }
    },

	appLogout: function(auto) {
        var me = this;
        if(auto === true){
            me.setTask(false);
            authProcedures.unAuth(function() {
                window.location = './'
            });
        }else{
            Ext.Msg.show({
                title  : i18n['please_confirm'] + '...',
                msg    : i18n['are_you_sure_to_quit'] + ' GaiaEHR?',
                icon   : Ext.MessageBox.QUESTION,
                buttons: Ext.Msg.YESNO,
                fn     : function(btn) {
                    if(btn == 'yes') {
                        authProcedures.unAuth(function() {
                            me.setTask(false);
                            window.location = './'
                        });
                    }
                }
            });
        }
	},

	initializePatientPoolDragZone: function(panel) {
		panel.dragZone = Ext.create('Ext.dd.DragZone', panel.getEl(), {
			ddGroup    : 'patientPoolAreas',
			getDragData: function() {
				var sourceEl = app.patientBtn.el.dom, d;
				if(app.currCardCmp != app.ppdz){
					app.MainPanel.getLayout().setActiveItem(app.ppdz);
				}
				app.navColumn.down('treepanel').getSelectionModel().deselectAll();

				if(sourceEl) {
					d = sourceEl.cloneNode(true);
					d.id = Ext.id();
					return panel.dragData = {
						copy    : true,
						sourceEl: sourceEl,
						repairXY: Ext.fly(sourceEl).getXY(),
						ddel    : d,
						records : [ panel.data ],
						patient : true
					};
				}
                return false;
			},
			getRepairXY: function() {
				app.goBack();
				return this.dragData.repairXY;
			}
		});
	},

	/**
	 *
	 * @param panel
	 */
	initializeOpenEncounterDragZone: function(panel) {
		panel.dragZone = Ext.create('Ext.dd.DragZone', panel.getEl(), {
			ddGroup:'patient',
			newGroupReset:true,
			b4MouseDown:function(e){
				if(this.newGroupReset){
					var sourceEl = e.getTarget(panel.itemSelector, 10),
						patientData = panel.getRecord(sourceEl).data;
					this.removeFromGroup(this.ddGroup);
                    say('initializeOpenEncounterDragZone | patientData:');
					say(patientData);
					if(patientData.floorPlanId != null && patientData.patientZoneId == null){
						app.navigateTo('panelAreaFloorPlan');
						this.ddGroup = 'patientPoolAreas';
					}else{
						this.ddGroup = 'patient';
						app.MainPanel.el.mask(i18n['drop_here_to_open'] + ' <strong>"' + panel.getRecord(sourceEl).data.name + '"</strong> ' + i18n['current_encounter']);
					}
					this.addToGroup(this.ddGroup);
					this.newGroupReset = false;
				}
				this.autoOffset(e.getPageX(), e.getPageY());
			},

			endDrag: function(e) {
				this.newGroupReset = true;
			},

			getDragData: function(e) {
				var sourceEl = e.getTarget(panel.itemSelector, 10), d,
					patientData = panel.getRecord(sourceEl).data;
				if(sourceEl) {
					d = sourceEl.cloneNode(true);
					d.id = Ext.id();
					return panel.dragData = {
						sourceEl   : sourceEl,
						repairXY   : Ext.fly(sourceEl).getXY(),
						ddel       : d,
						patientData: patientData
					};
				}
                return false;
			},

			getRepairXY: function() {
				app.MainPanel.el.unmask();
				this.newGroupReset = true;
				return this.dragData.repairXY;
			}
		});
	},
	
	onDocumentView: function(src) {
		var me = this;
		if(me.documentViewWindow) me.DocumentViewerWindow.remove(me.documentViewWindow);
		me.DocumentViewerWindow.add(me.documentViewWindow = Ext.create('App.classes.ManagedIframe', {src: src}));
		me.DocumentViewerWindow.show();
	},
	
	/**
	 *
	 * @param panel
	 */
	initializeOpenEncounterDropZone: function(panel) {
		var me = this;
		panel.dropZone = Ext.create('Ext.dd.DropZone', panel.getEl(), {
			ddGroup   : 'patient',
			notifyOver: function(dd, e, data) {
				return Ext.dd.DropZone.prototype.dropAllowed;
			},
			notifyDrop: function(dd, e, data) {
				app.MainPanel.el.unmask();
				me.setPatient(data.patientData.pid, data.patientData.name, function() {
					/**
					 * if encounter id is set and pool area is check out....  go to Patient Checkout panel
					 */
					if(data.patientData.eid && data.patientData.poolArea == 'Check Out') {
						me.checkOutPatient(data.patientData.eid);

					/**
					 * if encounter id is set and and user has access to encounter area... go to Encounter panel
					 * and open the encounter
					 */
					} else if(data.patientData.eid && acl['access_encounters']) {
						me.openEncounter(data.patientData.eid);

					/**
					 * else go to patient summary
					 */
					} else if(data.patientData.floorPlanId == null){
						me.openPatientSummary();
					}
				});

			}
		});
	},

	/*
	 * When the application finishes loading all the GaiaEHR core.
	 * Then it will load all the modules.
	 */
	appRender: function() {
        this.loadModules();
	},

	/*
	 * Load all the modules on the modules folder.
	 * This folder will hold modules created by third-party. 
	 */
    loadModules:function(){
        Modules.getEnabledModules(function(provider, response){
            var modules = response.result;
            for(var i=0; i < modules.length; i++){
                say('Module ' + modules[i].dir + ' loaded!');
                Ext.create('Modules.'+modules[i].dir+'.Main');
            }
        });
    },

	removeAppMask: function() {
		Ext.get('mainapp-loading').remove();
		Ext.get('mainapp-loading-mask').fadeOut({remove: true});
	},

	beforeAppRender: function() {

	},

	getCurrPatient: function() {
		return this.patient.pid;
	},

	getApp: function() {
		return this;
	},

	setTask:function(start){
        var me = this;
        if(start){

            App.classes.ActivityMonitor.init({
                interval    : me.activityMonitorInterval * 1000,
                maxInactive : (1000 * 60 * me.activityMonitorMaxInactive),
                verbose:true,
                isInactive:function(){
                    me.startAutoLogout();
                }
            });
            Ext.TaskManager.start(me.cronTask);
            App.classes.ActivityMonitor.start();
        }else{
            Ext.TaskManager.stop(me.cronTask);
            App.classes.ActivityMonitor.stop();
        }
	},

	/*
	 * Access denied massage.
	 */
	accessDenied: function() {
		Ext.Msg.show({
			title  : 'Oops!',
			msg    : i18n['access_denied'],
			buttons: Ext.Msg.OK,
			icon   : Ext.Msg.ERROR
		});
	},

	alert: function(msg,icon) {
		if(icon == 'error'){
			icon = Ext.Msg.ERROR
		}else if(icon == 'warning'){
			icon = Ext.Msg.WARNING
		}else if(icon == 'question'){
			icon = Ext.Msg.QUESTION
		}else{
			icon = Ext.Msg.INFO
		}
		Ext.Msg.show({
			title  : 'Oops!',
			msg    : msg,
			buttons: Ext.Msg.OK,
			icon   : icon
		});
	}

});