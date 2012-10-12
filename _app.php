<?php
/* Main Screen Application
 *
 * Description: This is the main application file, all the global
 * vars are defined here inluding "var app" witch refers to
 * the applciation Viewport.
 *
 * version 1.0.0
 * revision: N/A
 * author: GI Technologies, 2011
 * modified: Ernesto J Rodriguez (Certun)
 *
 * @namespace App.data.REMOTING_API
 */
if(!defined('_GaiaEXEC')) die('No direct access allowed.');
$_SESSION['site']['flops'] = 0;
?>
<html>
	<head>
		<script type="text/javascript">
			var app,
				acl = {},
				user = {},
				settings = {},
				i18n = {},
				ext = 'extjs-4.1.1a',
				requires;
		</script>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>GaiaEHR :: (Electronic Health Records)</title>
		<link rel="stylesheet" type="text/css" href="resources/css/dashboard.css">
		<link rel="stylesheet" type="text/css" href="resources/css/ext-all-gray.css">
		<link rel="stylesheet" type="text/css" href="lib/extensible-1.5.1/resources/css/calendar.css"/>
		<link rel="stylesheet" type="text/css" href="lib/extensible-1.5.1/resources/css/calendar-colors.css"/>
		<link rel="stylesheet" type="text/css" href="lib/extensible-1.5.1/resources/css/recurrence.css"/>
		<link rel="stylesheet" type="text/css" href="resources/css/style_newui.css">
		<link rel="stylesheet" type="text/css" href="resources/css/custom_app.css">
		<link rel="shortcut icon" href="favicon.ico">
	</head>
	<body>
		<!-- Loading Mask -->
		<div id="mainapp-loading-mask" class="x-mask mitos-mask"></div>
		<div id="mainapp-x-mask-msg">
			<div id="mainapp-loading" class="x-mask-msg mitos-mask-msg">
				<div>Loading GaiaEHR...</div>
			</div>
		</div>
		<!-- slide down message div -->
		<span id="app-msg" style="display:none;"></span>
		<!-- Ext library -->
		<script type="text/javascript" src="lib/extjs-4.1.1a/ext-all.js"></script>
		<!-- JSrouter and Ext.deirect API files -->
		<script src="JSrouter.php"></script>
		<script src="data/api.php"></script>
		<script type="text/javascript">
			// Ext Localization file
			(function(){document.write('<script type="text/javascript" src="lib/extjs-4.1.1a/locale/'+ i18n['i18nExtFile']+'"><\/script>')})();
			// Set and enable Ext.loader for dynamic class loading
			Ext.Loader.setConfig({
				enabled       : true,
				disableCaching: false,
				paths         : {
					'Ext'       : 'lib/extjs-4.1.1a/src',
					'Ext.ux'    : 'app/classes/ux',
					'App'       : 'app',
					'Modules'   : 'modules',
					'Extensible': 'lib/extensible-1.5.1/src'
				}
			});
		</script>
		<script type="text/javascript" src="lib/webcam_control/swfobject.js"></script>
		<script type="text/javascript" src="lib/extensible-1.5.1/src/Extensible.js"></script>
		<script type="text/javascript" src="lib/jpegcam/htdocs/webcam.js"></script>
		<script type="text/javascript" src="app/classes/Overrides.js"></script>
		<script type="text/javascript" src="app/classes/VTypes.js"></script>

		<script type="text/javascript">
			function say(a) {console.log(a);}
			for(var x=0; x < App.data.length; x++){
				Ext.direct.Manager.addProvider(App.data[x]);
			}
			requires = [
				'Ext.ux.LiveSearchGridPanel',
				'Ext.ux.SlidingPager',
				'Ext.ux.PreviewPlugin',
				/*
				 * Load the models, the model are the representative of the database
				 * table structure with modifications behind the PHP counterpart.
				 * All table should be declared here, and Sencha's ExtJS models.
				 * This are spreaded in all the core application.
				 */
				'App.model.administration.ActiveProblems',
				'App.model.administration.DefaultDocuments',
				'App.model.administration.DocumentsTemplates',
				'App.model.administration.ExternalDataLoads',
				'App.model.administration.FloorPlans',
				'App.model.administration.FloorPlanZones',
				'App.model.administration.HeadersAndFooters',
				'App.model.administration.ImmunizationRelations',
				'App.model.administration.LabObservations',
				'App.model.administration.Medications',
				'App.model.administration.PreventiveCare',
				'App.model.administration.PreventiveCareActiveProblems',
				'App.model.administration.PreventiveCareMedications',
				'App.model.administration.PreventiveCareLabs',
				'App.model.administration.Services',
				'App.model.miscellaneous.OfficeNotes',
				'App.model.fees.Billing',
				'App.model.fees.Checkout',
				'App.model.fees.EncountersPayments',
				'App.model.fees.PaymentTransactions',
				'App.model.navigation.Navigation',
				'App.model.patient.Allergies',
				'App.model.patient.CheckoutAlertArea',
				'App.model.patient.CptCodes',
				'App.model.patient.Dental',
		        'App.model.patient.Disclosures',
				'App.model.patient.Encounter',
				'App.model.patient.EncounterCPTsICDs',
				'App.model.patient.Encounters',
				'App.model.patient.EventHistory',
				'App.model.patient.Immunization',
				'App.model.patient.ImmunizationCheck',
				'App.model.patient.LaboratoryTypes',
				'App.model.patient.MeaningfulUseAlert',
				'App.model.patient.MedicalIssues',
				'App.model.patient.Medications',
				'App.model.patient.Notes',
				'App.model.patient.PatientArrivalLog',
		        'App.model.patient.PatientCalendarEvents',
				'App.model.patient.PatientDocuments',
				'App.model.patient.PatientImmunization',
				'App.model.patient.PatientLabsResults',
				'App.model.patient.PatientsLabsOrders',
				'App.model.patient.PatientsPrescription',
				'App.model.patient.PreventiveCare',
				'App.model.patient.QRCptCodes',
				'App.model.patient.DismissedAlerts',
				'App.model.patient.Reminders',
				'App.model.patient.Surgery',
				'App.model.patient.VectorGraph',
				'App.model.patient.VisitPayment',
				'App.model.patient.Vitals',
				'App.model.patient.charts.BMIForAge',
				'App.model.patient.charts.HeadCircumferenceInf',
				'App.model.patient.charts.LengthForAgeInf',
				'App.model.patient.charts.StatureForAge',
				'App.model.patient.charts.WeightForAge',
				'App.model.patient.charts.WeightForAgeInf',
				'App.model.patient.charts.WeightForRecumbentInf',
				'App.model.patient.charts.WeightForStature',
				'App.model.areas.PoolArea',
				'App.model.areas.PoolDropAreas',
				/*
				 * Load all the stores used by GaiaEHR
				 * this includes ComboBoxes, and other stores used by the web application
				 * most of this stores are consumed by the dataStore directory.
				 */
				'App.store.administration.ActiveProblems',
				'App.store.administration.DefaultDocuments',
				'App.store.administration.DocumentsTemplates',
				'App.store.administration.ExternalDataLoads',
				'App.store.administration.FloorPlans',
				'App.store.administration.FloorPlanZones',
				'App.store.administration.HeadersAndFooters',
				'App.store.administration.ImmunizationRelations',
				'App.store.administration.LabObservations',
				'App.store.administration.Medications',
				'App.store.administration.PreventiveCare',
				'App.store.administration.PreventiveCareActiveProblems',
				'App.store.administration.PreventiveCareMedications',
				'App.store.administration.PreventiveCareLabs',
				'App.store.administration.Services',
				'App.store.administration.ActiveProblems',
				'App.store.miscellaneous.OfficeNotes',
				'App.store.fees.Billing',
				'App.store.fees.Checkout',
				'App.store.fees.EncountersPayments',
				'App.store.fees.PaymentTransactions',
				'App.store.navigation.Navigation',
				'App.store.patient.Allergies',
				'App.store.patient.CheckoutAlertArea',
				'App.store.patient.Dental',
		        'App.store.patient.Disclosures',
				'App.store.patient.Encounter',
				'App.store.patient.EncounterCPTsICDs',
				'App.store.patient.EncounterEventHistory',
				'App.store.patient.Encounters',
				'App.store.patient.Immunization',
				'App.store.patient.ImmunizationCheck',
				'App.store.patient.LaboratoryTypes',
				'App.store.patient.MeaningfulUseAlert',
				'App.store.patient.MedicalIssues',
				'App.store.patient.Medications',
				'App.store.patient.Notes',
				'App.store.patient.PatientArrivalLog',
		        'App.store.patient.PatientCalendarEvents',
				'App.store.patient.PatientDocuments',
				'App.store.patient.DismissedAlerts',
				'App.store.patient.PatientImmunization',
				'App.store.patient.PatientLabsResults',
				'App.store.patient.PatientsLabsOrders',
				'App.store.patient.PatientsPrescription',
				'App.store.patient.PreventiveCare',
				'App.store.patient.QRCptCodes',
				'App.store.patient.Reminders',
				'App.store.patient.Surgery',
				'App.store.patient.VectorGraph',
				'App.store.patient.VisitPayment',
				'App.store.patient.Vitals',
				'App.store.patient.charts.BMIForAge',
				'App.store.patient.charts.HeadCircumferenceInf',
				'App.store.patient.charts.LengthForAgeInf',
				'App.store.patient.charts.StatureForAge',
				'App.store.patient.charts.WeightForAge',
				'App.store.patient.charts.WeightForAgeInf',
				'App.store.patient.charts.WeightForRecumbentInf',
				'App.store.patient.charts.WeightForStature',
				'App.store.areas.PoolArea',
				/*
				 * Load the activity by the user
				 * This will detect the activity of the user, if the user are idle by a
				 * certain time, it will logout.
				 */
		        'App.classes.ActivityMonitor',
				/*
				 * Load the classes that the CORE application needs
				 */
		        'App.classes.AbstractPanel',
				'App.classes.LiveCPTSearch',
				'App.classes.LiveImmunizationSearch',
				'App.classes.LiveMedicationSearch',
				'App.classes.LiveLabsSearch',
				'App.classes.LivePatientSearch',
				'App.classes.LiveSurgeriesSearch',
				'App.classes.ManagedIframe',
				'App.classes.NodeDisabled',
				'App.classes.PhotoIdWindow',
				/*
				 * Load the RenderPanel
				 * This is the main panel when all the forms are rendered.
				 */
				'App.classes.RenderPanel',
				/*
				 * Load the charts related controls
				 */
				'Ext.fx.target.Sprite',
				/*
				 * Load the DropDown related components
				 */
				'Ext.dd.DropZone',
				'Ext.dd.DragZone',
				/*
				 * Load the Extensible related controls and panels
				 * This is the Calendar Component that GaiaEHR uses.
				 */

				/*
				 * Load the form specific related fields
				 * Not all the fields are the same.
				 */
		        'App.classes.form.fields.Help',
		        'App.classes.form.fields.Checkbox',
		        'App.classes.form.fields.Currency',
		        'App.classes.form.fields.DateTime',
		        'App.classes.form.Panel',
		        'App.classes.grid.EventHistory',
		        'App.classes.grid.RowFormEditing',
		        'App.classes.grid.RowFormEditor',
				/*
				 * Load the combo boxes spreaded on all the web application
				 * remember this are all reusable combo boxes.
				 */
				'App.classes.combo.ActiveFacilities',
		        'App.classes.combo.ActiveInsurances',
				'App.classes.combo.Allergies',
				'App.classes.combo.AllergiesAbdominal',
				'App.classes.combo.AllergiesLocal',
				'App.classes.combo.AllergiesLocation',
				'App.classes.combo.AllergiesSeverity',
				'App.classes.combo.AllergiesSkin',
				'App.classes.combo.AllergiesSystemic',
				'App.classes.combo.AllergiesTypes',
				'App.classes.combo.Authorizations',
				'App.classes.combo.BillingFacilities',
				'App.classes.combo.CalendarCategories',
				'App.classes.combo.CalendarStatus',
				'App.classes.combo.CodesTypes',
				'App.classes.combo.EncounterPriority',
				'App.classes.combo.Facilities',
				'App.classes.combo.FloorPlanAreas',
				'App.classes.combo.FollowUp',
				'App.classes.combo.InsurancePayerType',
				'App.classes.combo.LabObservations',
				'App.classes.combo.LabsTypes',
				'App.classes.combo.Languages',
				'App.classes.combo.Lists',
				'App.classes.combo.MedicalIssues',
				'App.classes.combo.Medications',
				'App.classes.combo.MsgNoteType',
				'App.classes.combo.MsgStatus',
				'App.classes.combo.Occurrence',
				'App.classes.combo.Outcome',
				'App.classes.combo.Outcome2',
				'App.classes.combo.PayingEntity',
				'App.classes.combo.PaymentCategory',
				'App.classes.combo.PaymentMethod',
				'App.classes.combo.Pharmacies',
				'App.classes.combo.posCodes',
				'App.classes.combo.PrescriptionHowTo',
				'App.classes.combo.PrescriptionOften',
				'App.classes.combo.PrescriptionTypes',
				'App.classes.combo.PrescriptionWhen',
				'App.classes.combo.PreventiveCareTypes',
				'App.classes.combo.ProceduresBodySites',
				'App.classes.combo.Providers',
				'App.classes.combo.Roles',
				'App.classes.combo.Sex',
				'App.classes.combo.SmokingStatus',
				'App.classes.combo.Surgery',
				'App.classes.combo.TaxId',
				'App.classes.combo.Templates',
		        'App.classes.combo.Themes',
				'App.classes.combo.Time',
				'App.classes.combo.Titles',
				'App.classes.combo.TransmitMethod',
				'App.classes.combo.Types',
				'App.classes.combo.Units',
				'App.classes.combo.Users',
				'App.classes.combo.YesNoNa',
				'App.classes.combo.YesNo',
				'App.classes.window.Window',
				'App.classes.NodeDisabled',
				'App.view.search.PatientSearch',
				/*
				 * Load the patient window related panels
				 */
				'App.view.patient.windows.Medical',
				'App.view.patient.windows.Charts',
				'App.view.patient.windows.PreventiveCare',
				'App.view.patient.windows.NewDocuments',
				'App.view.patient.windows.DocumentViewer',
				'App.view.patient.windows.ArrivalLog',
				/*
				 * Load the patient related panels
				 */
		        'App.view.dashboard.panel.PortalColumn',
		        'App.view.dashboard.panel.PortalDropZone',
		        'App.view.dashboard.panel.PortalPanel',
				'App.view.dashboard.panel.OnotesPortlet',
		        'App.view.dashboard.panel.VisitsPortlet',
		        'App.view.dashboard.Dashboard',
				/*
				 * Load the root related panels
				 */
				'App.view.calendar.ExtensibleAll',
				'App.view.calendar.Calendar',
				'App.view.messages.Messages',
				/*
				 * Load the areas related panels
				 */
				'App.view.areas.FloorPlan',
				'App.view.areas.PatientPoolDropZone',
	            /**
	             * Load vector charts panel
	             */
				'App.view.patient.charts.BPPulseTemp',
				'App.view.patient.charts.HeadCircumference',
				'App.view.patient.charts.HeightForStature',
				/*
				 * Load the patient related panels
				 */
				'App.view.patient.encounter.CurrentProceduralTerminology',
				'App.view.patient.encounter.HealthCareFinancingAdministrationOptions',
				'App.view.patient.encounter.ICDs',
				'App.view.patient.ItemsToReview',
				'App.view.patient.EncounterDocumentsGrid',
				'App.view.patient.encounter.ICDs',
				'App.view.patient.CheckoutAlertsView',
				'App.view.patient.Vitals',
				'App.view.patient.NewPatient',
				'App.view.patient.Summary',
				'App.view.patient.ProgressNote',
				'App.view.patient.Visits',
				'App.view.patient.Encounter',
				'App.view.patient.windows.Medical',
				'App.view.patient.VisitCheckout',
				/*
				 * Load the fees related panels
				 */
				'App.view.fees.Billing',
				'App.view.fees.PaymentEntryWindow',
				'App.view.fees.Payments',
				/*
				 * Load the administration related panels
				 */
				'App.view.administration.DataManager',
				'App.view.administration.Documents',
				'App.view.administration.Facilities',
				'App.view.administration.Globals',
				'App.view.administration.Layout',
				'App.view.administration.Lists',
				'App.view.administration.Log',
				'App.view.administration.Medications',
				'App.view.administration.FloorPlans',
				'App.view.administration.Practice',
				'App.view.administration.PreventiveCare',
				'App.view.administration.Roles',
				'App.view.administration.ExternalDataLoads',
				'App.view.administration.Users',
				/*
				 * Load the miscellaneous related panels
				 */
				'App.view.miscellaneous.Addressbook',
				'App.view.miscellaneous.MyAccount',
				'App.view.miscellaneous.MySettings',
				'App.view.miscellaneous.OfficeNotes',
				'App.view.miscellaneous.Websearch',
				'App.view.signature.SignatureWindow',
				/*
				 * Dynamically load the modules
				 */
		        'Modules.Module'
			];
			(function(){
			    var scripts = document.getElementsByTagName('script'),
			        localhostTests = [
			            /^localhost$/,
			            /\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(:\d{1,5})?\b/ // IP v4
			        ],
			        host = window.location.hostname,
			        isDevelopment = null,
			        queryString = window.location.search,
			        test, path, i, ln, scriptSrc, match;

			    for (i = 0, ln = scripts.length; i < ln; i++) {
			        scriptSrc = scripts[i].src;

			        match = scriptSrc.match(/bootstrap\.js$/);

			        if (match) {
			            path = scriptSrc.substring(0, scriptSrc.length - match[0].length);
			            break;
			        }
			    }

			    if (queryString.match('(\\?|&)debug') !== null) {
			        isDevelopment = true;
			    }
			    else if (queryString.match('(\\?|&)nodebug') !== null) {
			        isDevelopment = false;
			    }

			    if (isDevelopment === null) {
			        for (i = 0, ln = localhostTests.length; i < ln; i++) {
			            test = localhostTests[i];

			            if (host.search(test) !== -1) {
			                isDevelopment = true;
			                break;
			            }
			        }
			    }

			    if (isDevelopment === null && window.location.protocol === 'file:') {
			        isDevelopment = true;
			    }

				if(isDevelopment){
				say('Loading GaiaEHR Classes (Development)');
//				var jsb3Buffer = '"files": [';
					for(var r=0; r < requires.length; r++){
				        document.write('<script type="text/javascript" charset="UTF-8" src="' + Ext.Loader.getPath(requires[r]) + '"><\/script>');
//						var arrayBuffer = Ext.Loader.getPath(requires[r]).split('/'),
//								fileName = arrayBuffer.pop();
//								filePath = arrayBuffer.join('/');
//				        jsb3Buffer = jsb3Buffer + '{' +
//					        '"path": "'+filePath+'/",' +
//						    '"name": "'+fileName+'"' +
//				            '},';
				   }
//			   jsb3Buffer = jsb3Buffer+' ]';
				}else{
					say('Loading GaiaEHR Classes (Production)');
					document.write('<script type="text/javascript" charset="UTF-8" src="app/app-all.js"><\/script>');
				}
			})();
			function copyToClipBoard(token) {
				app.msg('Sweet!', token + ' copied to clipboard, Ctrl-V or Paste where need it.');
				if(window.clipboardData) {
					window.clipboardData.setData('text', token);
					return null;
				} else {
					return (token);
				}
			}

			function onWebCamComplete(msg) {
				app.onWebCamComplete(msg);
			}
			function printQRCode(pid) {
				var src = settings.site_url + '/patients/' + app.patient.pid + '/patientDataQrCode.png?';
				app.QRCodePrintWin = window.open(src, 'QRCodePrintWin', 'left=20,top=20,width=800,height=600,toolbar=0,resizable=0,location=1,scrollbars=0,menubar=0,directories=0');
				Ext.defer(function() {
					app.QRCodePrintWin.print();
				}, 1000);
			}
			Ext.onReady(function() {
				/**
				 * lets create the Application Viewport (render the application),
				 * and store the application viewport instance in "app".
				 * @type {*}
				 */
				CronJob.run(function(){
					say('Loading GaiaEHR');
					app = Ext.create('App.view.Viewport');
				});
			});
		</script>
	</body>
</html>