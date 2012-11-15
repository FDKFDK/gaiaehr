Ext.define('App.view.MainTablet', {
    extend: 'Ext.Container',
    xtype: 'maintabletview',

    requires: [
        'Ext.navigation.Bar',
        'App.view.PatientList'
    ],

    config: {
        fullscreen: true,
        layout: {
            type: 'card',
            animation: {
                type: 'slide',
                direction: 'left',
                duration: 250
            }
        },
        items: [
            {
                id: 'mainPanel',
                cls : 'card',
                padding:10,
                scrollable: true,
                action:'mainPanel',
                html: '' +
                    '<div class="features">' +
                    '   <h2>Welcome to GaiaEHR Air <span class="version">Beta 0.0.1</span></h2>' +
                    '   <div class="feature main">' +
                    '       <h3>Unleash GaiaEHR</h3>' +
                    '       <p>This is the Kitchen Sink &#8212; a collection of features and examples in an easy-to-browse format. Each example also has a &#8220;view source&#8221; button which shows how it was created.</p>' +
                    '   </div>' +
                    '   <div class="feature">' +
                    '       <h3>Unleash GaiaEHR</h3>' +
                    '       <p>Faster layouts and animations, smoother scrolling, and overall more responsive.</p>' +
                    '   </div>' +
                    '   <div class="feature">' +
                    '       <h3>Mobile Architecture</h3>' +
                    '       <p>Our new class system is simpler to write and easier to extend. All new MVC and state-management support.</p>' +
                    '   </div>' +
                    '   <div class="feature">' +
                    '       <h3>Simple</h3>' +
                    '       <p>Sencha SDK Tools now allow you to build your app for App Store distribution, on Windows and Mac.</p>' +
                    '   </div>' +
                    '   <div class="feature">' +
                    '       <h3>Easy to Learn</h3>' +
                    '       <p>With over 30 new guides, 6 new full-fledged demo apps, and improved documentation, Sencha Touch 2 is easier to learn than ever.</p>' +
                    '   </div>' +
                    '   <div class="footer">Learn more at <a href="http://www.gaiaehr.org/touch" target="blank">GaiaEHR Air</a></div>' +
                    '</div>'
            },
            {
                xtype:'container',
                docked: 'left',
                layout:'vbox',
                cls:'leftNav',
                items:[
                    {
                        action: 'leftNavBar',
                        xtype : 'titlebar',
                        docked: 'top',
                        title : 'Patients'
                    },
                    {
                        xtype : 'patientlist',
                        width:350,
                        flex:1
                    }
                ]

            },
            {
                action: 'mainNavBar',
                xtype : 'titlebar',
                docked: 'top',
                title : 'Home',
                items: [
                    {
                        iconCls: 'home',
                        iconMask: true,
                        align: 'left',
                        action:'home'
                    },
                    {
                        ui:'decline',
                        text: 'Logout',
                        align: 'right',
                        action:'logout'
                    }
                ]
            }
        ]
    }
});