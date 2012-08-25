Ext.define('App.view.sitesetup.SiteSetup', {
    extend       : 'Ext.window.Window',
    title        : 'GaiaEHR Site Setup',
    bodyPadding  : 5,
    y            : 90,
    width        : 900,
    height       : 500,
    plain        : true,
    modal        : false,
    resizable    : false,
    draggable    : false,
    closable     : false,
    bodyStyle    : 'background-color: #ffffff; padding: 5px;',
    layout       : {
        type : 'vbox',
        align: 'stretch'
    },
    requires:[
        'App.classes.form.fields.Help',
        'App.classes.form.fields.plugin.HelpIcon',
        'App.classes.window.CopyRights'
    ],
    initComponent: function() {
        var me = this;
        /**
         * array to store each step success and data
         *
         * me.step[0] = Welcome!
         * me.step[1] = System Compatibility
         * me.step[2] = Database Configuration
         * me.step[3] = Site Configuration
         * me.step[4] = Installation Complete!
         *
         * @type {Array}
         */
        me.step = [];

        /**
         * Store used to load system requirements
         */
        Ext.define('Requirements', {extend: 'Ext.data.Model',
            fields                        : [
                {name: 'msg', type: 'string'},
                {name: 'status', type: 'string'}
            ]
        });
        me.requirementsStore = Ext.create('Ext.data.Store', {
            model   : 'Requirements',
            proxy: {
           		type       : 'direct',
           		api        : {
           			read  : SiteSetup.checkRequirements
           		}
           	},
            autoLoad: false
        });
        /**
         * Copy Rights window
         * @type {*}
         */
        me.winCopyright = Ext.create('App.classes.window.CopyRights');
        /**
         * Site Setup window
         * @type {Array}
         */
        me.items = [
            me.headerPanel = Ext.create('Ext.Container', {
                cls   : 'siteSetupHeader',
                height: 45,
                items : [
                    me.welcomeBtn = Ext.create('Ext.Button', {
                        scale        : 'large',
                        iconCls      : 'icoGrayFace',
                        componentCls : 'setupBts',
                        margin       : '0 38 0 0',
                        iconAlign    : 'right',
                        enableToggle : true,
                        toggleGroup  : 'siteSetup',
                        text         : '1.Welcome!',
                        scope        : me,
                        action:0,
                        pressed:true,
                        handler: me.onHeaderBtnPress
                    }), me.compatibiltyBtn = Ext.create('Ext.Button', {
                        scale        : 'large',
                        iconCls      : 'icoGrayFace',
                        componentCls : 'setupBts',
                        margin       : '0 38 0 0',
                        iconAlign    : 'right',
                        enableToggle : true,
                        toggleGroup  : 'siteSetup',
                        disabled:true,
                        text         : '2.System Compatibility',
                        scope        : me,
                        action:1,
                        handler: me.onHeaderBtnPress
                    }), me.databaseBtn = Ext.create('Ext.Button', {
                        scale        : 'large',
                        iconCls      : 'icoGrayFace',
                        componentCls : 'setupBts',
                        margin       : '0 38 0 0',
                        iconAlign    : 'right',
                        enableToggle : true,
                        toggleGroup  : 'siteSetup',
                        disabled:true,
                        text         : '3.Database Configuration',
                        scope        : me,
                        action:2,
                        handler: me.onHeaderBtnPress
                    }), me.siteConfigurationBtn = Ext.create('Ext.Button', {
                        scale        : 'large',
                        iconCls      : 'icoGrayFace',
                        componentCls : 'setupBts',
                        margin       : '0 38 0 0',
                        iconAlign    : 'right',
                        enableToggle : true,
                        toggleGroup  : 'siteSetup',
                        disabled:true,
                        text         : 'Site Configuration',
                        scope        : me,
                        action:3,
                        handler: me.onHeaderBtnPress
                    }), me.completeBtn = Ext.create('Ext.Button', {
                        scale        : 'large',
                        iconCls      : 'icoGrayFace',
                        componentCls : 'setupBts',
                        iconAlign    : 'right',
                        enableToggle : true,
                        toggleGroup  : 'siteSetup',
                        disabled:true,
                        text         : '4.Installation Complete!',
                        scope        : me,
                        action:4,
                        handler: me.onHeaderBtnPress
                    })
                ]
            }), me.mainPanel = Ext.create('Ext.Container', {
                flex  : 1,
                layout: 'card',
                items : [
                    me.welcome = Ext.create('Ext.Container', {
                        action    : 0,
                        items: [
                            {
                                xtype           : 'panel',
                                title:'Welcome to GaiaEHR Site Setup',
                                styleHtmlContent: true,
                                cls:'welcome',
                                layout:'auto',
                                items:[
                                    {
                                        xtype:'container',
                                        height:120,
                                        padding:'5 10 0 10',
                                        html:' <p>Please allow 10-15 minutes to complete the installation process.</p>' +
                                            '<p>The GaiaEHR Site Setup will do most of the work for you in just a few clicks.</p>' +
                                            '<p>However, you must know how to do the following:</p>' +
                                            '<ul>' +
                                            '<li>Set permissions on folders & subfolders using an FTP client</li>' +
                                            '<li>Create a MySQL database using phpMyAdmin (or by asking your hosting provider)</li>' +
                                            '</ul>'
                                    },
                                    {
                                        xtype      : 'fieldset',
                                        title      : 'License Agreement',
                                        defaultType: 'textfield',
                                        layout     : 'anchor',
                                        margin:'0 5 5 5',
                                        items      : [
                                            me.licence = Ext.create('Ext.Container', {
                                                height: 170,
                                                styleHtmlContent: true,
                                                autoScroll:true,
                                                autoLoad        : 'gpl-licence-en.html'
                                            }),
                                            me.licAgreement = Ext.create('Ext.form.field.Checkbox', {
                                                boxLabel  : 'I agree to the GaiaEHR terms and conditions',
                                                name      : 'topping',
                                                margin:'5 0 0 0',
                                                inputValue: '1',
                                                scope:me,
                                                handler:me.licenceChecked
                                            })
                                        ]
                                    }
                                ]
                            }
                        ]
                    }), me.requirementsGrid = Ext.create('Ext.grid.Panel', {
                        store     : me.requirementsStore,
                        frame     : false,
                        title:'Requirements',
                        action    : 1,
                        viewConfig: {stripeRows: true},
                        columns   : [
                            {
                                text     : 'Requirements',
                                flex     : 1,
                                sortable : false,
                                dataIndex: 'msg'
                            },
                            {
                                text     : 'Status',
                                width    : 150,
                                sortable : true,
                                renderer : me.statusRenderer,
                                dataIndex: 'status'
                            }
                        ],
                        tools:[
                            {
                                type:'refresh',
                                tooltip: 'ReCheck Requirements',
                                handler: function(){
                                    me.requirementsStore.load({
                                        scope:me,
                                        callback:me.onRequirementsStoreLoad
                                    });
                                }
                            }
                        ],
                        bbar:['->', '-',
                            {
                                text:'Re-Check Requirements',
                                handler: function(){
                                    me.requirementsStore.load({
                                        scope:me,
                                        callback:me.onRequirementsStoreLoad
                                    });
                                }
                            }, '-'
                        ]
                    }), me.databaseConfiguration = Ext.create('Ext.form.Panel', {
                        title:'Database Configuration',
                        defaultType: 'textfield',
                        bodyPadding:'0 10',
                        action    : 2,
                        items      : [
                            {
                                xtype  : 'displayfield',
                                padding: '10px',
                                value  : 'Choose if you want to <a href="javascript:void(0);" onClick="Ext.getCmp(\'rootFieldset\').enable();">create a new database</a> or use an <a href="javascript:void(0);" onClick="Ext.getCmp(\'dbuserFieldset\').enable();">existing database</a><br>'
                            },
                            {
                                xtype         : 'fieldset',
                                id            : 'rootFieldset',
                                title         : 'Create a New Database (Root Access Needed)',
                                defaultType   : 'textfield',
                                collapsed     : true,
                                disabled      : true,
                                layout        : 'anchor',
                                defaults      : {anchor: '100%'},
                                items         : [
                                    {
                                        fieldLabel: 'Root User',
                                        name      : 'rootUser',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'Root Password',
                                        name      : 'rootPass',
                                        id        : 'rootPass',
                                        inputType : 'password',
                                        allowBlank: true
                                    },
                                    {
                                        fieldLabel: 'SQL Server Host',
                                        name      : 'dbHost',
                                        value     : 'localhost',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'SQL Server Port',
                                        name      : 'dbPort',
                                        value     : '3306',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'Database Name',
                                        name      : 'dbName',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'New Database User',
                                        name      : 'dbUser',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'New Database Pass',
                                        name      : 'dbPass',
                                        inputType : 'password',
                                        allowBlank: false
                                    }
                                ],
                                listeners     : {
                                    enable: function() {
                                        conn = 'root';
                                        Ext.getCmp('dbuserFieldset').collapse();
                                        Ext.getCmp('dbuserFieldset').disable();
                                        Ext.getCmp('rootFieldset').expand();

                                    }
                                }
                            },
                            {
                                xtype         : 'fieldset',
                                id            : 'dbuserFieldset',
                                title         : 'Install on a existing database',
                                defaultType   : 'textfield',
                                collapsed     : true,
                                disabled      : true,
                                layout        : 'anchor',
                                defaults      : {anchor: '100%'},
                                items         : [
                                    {
                                        fieldLabel: 'Database Name',
                                        name      : 'dbName',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'Database User',
                                        name      : 'dbUser',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'Database Pass',
                                        name      : 'dbPass',
                                        id        : 'dbPass',
                                        inputType : 'password',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'Database Host',
                                        name      : 'dbHost',
                                        value     : 'localhost',
                                        allowBlank: false
                                    },
                                    {
                                        fieldLabel: 'Database Port',
                                        name      : 'dbPort',
                                        value     : '3306',
                                        allowBlank: false
                                    }
                                ],
                                listeners     : {
                                    enable: function() {
                                        conn = 'user';
                                        Ext.getCmp('rootFieldset').collapse();
                                        Ext.getCmp('rootFieldset').disable();
                                        Ext.getCmp('dbuserFieldset').expand();

                                    }
                                }
                            }
                        ],
                        bbar    : [
                            '**Database Connection Test is Required to Continue -->>','->','-',
                            {
                                text   : 'Database Connection Test',
                                action     : 'dataTester',
                                scope:me,
                                handler: me.onDbTestCredentials
                            },
                            '-'
                        ]
                    }),
                    me.siteConfiguration = Ext.create('Ext.form.Panel', {
                        title:'Site configuration',
                        defaultType: 'textfield',
                        bodyPadding:'10',
                        action    : 3,
                        items:[
                            {
                                xtype         : 'fieldset',
                                title         : 'Site / Admin Info',
                                layout        : 'anchor',
                                defaults      : { margin: '4 0'},
                                items         : [
                                    {
                                        xtype:'textfield',
                                        fieldLabel: 'Site ID',
                                        name      : 'siteId',
                                        value:'default',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'Most GaiaEHR installations will support only one site.<br>' +
                                                    'If that is the case for you, leave Site ID on <span style="font-weight: bold;">"default"</span>.<br>' +
                                                    'Otherwise, use a Site ID short identifier with no spaces<br>' +
                                                    'or special characters other dashes. It is case-sensitive,<br>' +
                                                    'we suggest sticking to lower case letters for ease of use'
                                            }
                                        ]
                                    },
                                    {
                                        xtype:'textfield',
                                        fieldLabel: 'Admin username',
                                        name      : 'adminUsername',
                                        value:'admin',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'**Username must be between <span style="font-weight: bold;">4 to 10</span> characters long<br>' +
                                                        '**Do not use special characters. ei. <span style="font-weight: bold;">"!@#$%^&*()</span>'
                                            }
                                        ]
                                    },
                                    {

                                        xtype:'textfield',
                                        fieldLabel: 'Admin password',
                                        inputType: 'password',
                                        name      : 'adminPassword',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'**Password must be between <span style="font-weight: bold;">6 to 8</span> characters long<br>' +
                                                    '**Do not use special characters. ei. <span style="font-weight: bold;">"!@#$%^&*()</span>'
                                            }
                                        ]
                                    }
                                ]
                            },
                            {
                                xtype         : 'fieldset',
                                title         : 'Site Options',
                                layout        : 'anchor',
                                defaults      : { margin: '4 0'},
                                items         : [
                                    {
                                        xtype:'combobox',
                                        fieldLabel: 'Site Theme',
                                        name      : 'lang',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'**The themes will change the visual aspect.<br>' +
                                                    '**This can be change later in the Administrator -> Global Setting'
                                            }
                                        ]
                                    },
                                    {
                                        xtype:'combobox',
                                        fieldLabel: 'Default Language',
                                        name      : 'lang',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'**This default language will be the default language during the Logon window.<br>' +
                                                    '**This can be change later in the Administrator -> Global Setting'
                                            }
                                        ]
                                    },
                                    {
                                        xtype:'checkboxfield',
                                        fieldLabel: 'Load ICD9',
                                        name      : 'ICD9',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'Load ICD9 Codes will add a <span style="font-weight: bold;">few minutes</span> to the installation process.'
                                            }
                                        ]
                                    },
                                    {
                                        xtype:'checkboxfield',
                                        fieldLabel: 'Load ICD10',
                                        name      : 'ICD10',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'Load ICD10 Codes will add a <span style="font-weight: bold;">few minutes</span> to the installation process.'
                                            }
                                        ]
                                    },
                                    {
                                        xtype:'checkboxfield',
                                        fieldLabel: 'Load SNOMED',
                                        name      : 'SNOMED',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'Load SNOMED Codes will add a <span style="font-weight: bold;">5 to 10 minutes</span> to the installation process.'
                                            }
                                        ]
                                    },
                                    {
                                        xtype:'checkboxfield',
                                        fieldLabel: 'Load RxNorm',
                                        name      : 'RxNorm',
                                        plugins:[
                                            {
                                                ptype:'helpicon',
                                                helpMsg:'Load RxNorm Codes will add <span style="font-weight: bold;">30 to 60 minutes</span> to the installation process.'
                                            }
                                        ]
                                    }
                                ]
                            }
                        ]
                    }),
                    me.installationComplete = Ext.create('Ext.panel.Panel', {
                        title:'Installation Complete',
                        bodyPadding:'10',
                        action    : 4,
                        html:'Installation placeholder'
                    })
                ]
            })
        ];

        me.buttons = [
            {
                text   : 'Back',
                scope  : me,
                hidden:true,
                id     : 'move-prev',
                handler: me.onStepBack
            },
            '->',
            {
                text   : 'Next',
                scope  : me,
                disabled:true,
                id     : 'move-next',
                handler: me.onNexStep
            }
        ];

        me.callParent();
    },

    onDbTestCredentials:function(){
        var me = this,
            form = me.databaseConfiguration.getForm(),
            success,
            dbInfo;
        if(typeof form.getValues().dbName !== 'undefined'){
            if(form.isValid()) {
                me.databaseConfiguration.el.mask('Validating Database Info');
                SiteSetup.checkDatabaseCredentials(form.getValues(), function(provider, response){
                    success = response.result.success;
                    dbInfo = response.result.dbInfo;
                    me.step[2] = { success:success , dbInfo:dbInfo };
                    me.okToGoNext(success);
                    me.databaseConfiguration.el.unmask();
                    if(!success) Ext.Msg.show({
                         title:'Oops!',
                         msg: 'Unable to validate database credentials.',
                         buttons: Ext.Msg.Ok,
                         icon: Ext.Msg.ERROR
                    });
                });
            }
        }else{
            Ext.Msg.show({
                title:'Oops!',
                msg: 'Please select one of the two options.',
                buttons: Ext.Msg.Ok,
                icon: Ext.Msg.ERROR
            });
        }
    },

    onNexStep: function() {
        this.navigate(this.mainPanel, 'next');
    },

    onStepBack: function() {
        this.navigate(this.mainPanel, 'prev');
    },

    navigate: function(panel, to) {
        var me = this,
            layout = panel.getLayout(),
            currCard, next;
        if(typeof to == 'string') {
            layout[to]();
        } else {
            layout.setActiveItem(to);
        }
        currCard = layout.getActiveItem();
        me.headerPanel.getComponent(currCard.action).toggle(true);
        if(currCard.action == 1){
            me.requirementsStore.load({
                scope:me,
                callback:me.onRequirementsStoreLoad
            });
        }
        Ext.getCmp('move-prev').setVisible(layout.getPrev());
        next = layout.getNext();
        if(next) {
            Ext.getCmp('move-next').setDisabled(true);
        }else{
            Ext.getCmp('move-next').setVisible(next);
        }
    },

    licenceChecked:function(checkbox, checked){
        var me = this;
        me.step[0] = { success:checked };
        me.okToGoNext(checked);
    },

    onRequirementsStoreLoad:function(records){
        var me = this,
            errorCount = 0;
        for(var i=0; i < records.length; i++){
            if(records[i].data.status != 'Ok') errorCount++;
        }
        me.step[1] = { success:errorCount === 0 };
        me.okToGoNext(me.step[1].success);
    },

    onHeaderBtnPress: function(btn, pressed) {
        if(pressed){
            this.navigate(this.mainPanel, btn.action);
        }
    },

    okToGoNext:function(ok){
        var me = this,
            layout = me.mainPanel.getLayout();
        me.headerPanel.getComponent(layout.getActiveItem().action).setIconCls(ok ? 'icoGreenFace' : 'icoRedFace');
        if(layout.getNext()) me.headerPanel.getComponent(layout.getNext().action).setDisabled(!ok);
        Ext.getCmp('move-next').setDisabled(!ok);
    },

    statusRenderer: function(val) {
        if(val == 'Ok') {
            return '<span style="color:green;">' + val + '</span>';
        } else {
            return '<span style="color:red;">' + val + '</span>';
        }
    }

});

