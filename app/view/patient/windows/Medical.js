/**
 * Created by JetBrains PhpStorm.
 * User: Ernesto J. Rodriguez (Certun)
 * File:
 * Date: 2/15/12
 * Time: 4:30 PM
 *
 * @namespace Immunization.getImmunizationsList
 * @namespace Immunization.getPatientImmunizations
 * @namespace Immunization.addPatientImmunization
 */
Ext.define('App.view.patient.windows.Medical', {
	extend       : 'App.classes.window.Window',
	title        : i18n['medical_window'],
	id           : 'MedicalWindow',
	layout       : 'card',
	closeAction  : 'hide',
	height       : 750,
	width        : 1200,
	bodyStyle    : 'background-color:#fff',
	modal        : true,
	defaults     : {
		margin: 5
	},
	requires     : [ 'App.view.patient.LaboratoryResults' ],
	pid          : null,
	initComponent: function() {

		var me = this;


		me.patientImmuListStore = Ext.create('App.store.patient.PatientImmunization', {
			groupField: 'immunization_name',
			sorters   : ['immunization_name', 'administered_date'],
			listeners : {
				scope     : me,
				beforesync: me.setDefaults
			},
			autoSync  : true
		});
		me.patientAllergiesListStore = Ext.create('App.store.patient.Allergies', {

			listeners: {
				scope     : me,
				beforesync: me.setDefaults
			},
			autoSync : true
		});
		me.patientMedicalIssuesStore = Ext.create('App.store.patient.MedicalIssues', {

			listeners: {
				scope     : me,
				beforesync: me.setDefaults
			},
			autoSync : true
		});
		me.patientSurgeryStore = Ext.create('App.store.patient.Surgery', {

			listeners: {
				scope     : me,
				beforesync: me.setDefaults
			},
			autoSync : true
		});
		me.patientDentalStore = Ext.create('App.store.patient.Dental', {

			listeners: {
				scope     : me,
				beforesync: me.setDefaults
			},
			autoSync : true
		});
		me.patientMedicationsStore = Ext.create('App.store.patient.Medications', {

			listeners: {
				scope     : me,
				beforesync: me.setDefaults
			},
			autoSync : true
		});
		me.labPanelsStore = Ext.create('App.store.patient.LaboratoryTypes', {
			autoSync: true
		});

		me.items = [
			{
				xtype   : 'grid',
				action  : 'patientImmuListGrid',
				itemId  : 'patientImmuListGrid',
				store   : me.patientImmuListStore,
				features: Ext.create('Ext.grid.feature.Grouping', {
					groupHeaderTpl   : i18n['immunization'] + ': {name} ({rows.length} Item{[values.rows.length > 1 ? "s" : ""]})',
					hideGroupedHeader: true
				}),
				columns : [
					{
						header   : i18n['immunization_name'],
						width    : 100,
						dataIndex: 'immunization_name'
					},
					{
						xtype    : 'datecolumn',
						header   : 'Date',
						format   : 'Y-m-d',
						width    : 100,
						dataIndex: 'administered_date'
					},
					{
						header   : i18n['lot_number'],
						width    : 100,
						dataIndex: 'lot_number'
					},
					{
						header   : 'Notes',
						flex     : 1,
						dataIndex: 'note'
					}
				],

				plugins: Ext.create('App.classes.grid.RowFormEditing', {
					autoCancel  : false,
					errorSummary: false,
					clicksToEdit: 1,
					formItems   : [

						{

							title : 'general',
							xtype : 'container',
							layout: 'vbox',
							items : [
								{
									/**
									 * Line one
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 3 0', xtype: 'textfield'},
									items   : [

										{
											xtype          : 'immunizationlivesearch',
											fieldLabel     : i18n['name'],
											hideLabel      : false,
											allowBlank     : false,
											itemId         : 'immunization_name',
											name           : 'immunization_name',
											enableKeyEvents: true,
											action         : 'immunizations',
											width          : 570,
											listeners      : {
												scope : me,
												select: me.onLiveSearchSelect
											}
										},
										{
											xtype : 'textfield',
											hidden: true,
											name  : 'immunization_id',
											action: 'idField'
										},
										{
											fieldLabel: i18n['administrator'],
											name      : 'administered_by',
											width     : 295,
											labelWidth: 160

										}

									]

								},
								{
									/**
									 * Line two
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 3 0', xtype: 'textfield' },
									items   : [
										{
											fieldLabel: i18n['lot_number'],
											xtype     : 'textfield',
											width     : 300,
											name      : 'lot_number'

										},
										{

											xtype     : 'numberfield',
											fieldLabel: i18n['dosis_number'],
											width     : 260,
											name      : 'dosis'
										},

										{
											fieldLabel: i18n['info_statement_given'],
											width     : 295,
											labelWidth: 160,
											xtype     : 'datefield',
											format    : 'Y-m-d',
											name      : 'education_date'
										}

									]

								},
								{
									/**
									 * Line three
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 3 0', xtype: 'textfield' },
									items   : [

										{
											fieldLabel: i18n['notes'],
											xtype     : 'textfield',
											width     : 300,
											name      : 'note'

										},
										{
											fieldLabel: i18n['manufacturer'],
											xtype     : 'textfield',
											width     : 260,

											name: 'manufacturer'

										},

										{
											fieldLabel: i18n['date_administered'],
											xtype     : 'datefield',
											width     : 295,
											labelWidth: 160,
											format    : 'Y-m-d',
											name      : 'administered_date'
										}

									]

								}

							]

						}

					]
				}),
				bbar   : [
					'->', {
						text   : i18n['reviewed'],
						action : 'review',
						itemId : 'review_immunizations',
						scope  : me,
						handler: me.onReviewed
					}
				]
			},

			{
				/**
				 * Allergies Card panel
				 */
				xtype  : 'grid',
				action : 'patientAllergiesListGrid',
				store  : me.patientAllergiesListStore,
				columns: [
					{
						header   : i18n['type'],
						width    : 100,
						dataIndex: 'allergy_type'
					},
					{
						header   : i18n['name'],
						width    : 100,
						dataIndex: 'allergy'
					},
					{
						header   : i18n['location'],
						width    : 100,
						dataIndex: 'location'
					},
					{
						header   : i18n['severity'],
						flex     : 1,
						dataIndex: 'severity'
					},
					{
						text     : i18n['active'],
						width    : 55,
						dataIndex: 'alert',
						renderer : me.boolRenderer
					}
				],
				plugins: me.rowEditingAllergies = Ext.create('App.classes.grid.RowFormEditing', {
					autoCancel  : false,
					errorSummary: false,
					clicksToEdit: 1,
					formItems   : [

						{
							title  : i18n['general'],
							xtype  : 'container',
							padding: 10,
							layout : 'vbox',
							items  : [
								{
									/**
									 * Line one
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											xtype          : 'mitos.allergiestypescombo',
											fieldLabel     : i18n['type'],
											name           : 'allergy_type',
											action         : 'allergy_type',
											allowBlank     : false,
											width          : 225,
											labelWidth     : 70,
											enableKeyEvents: true,
											listeners      : {
												scope   : me,
												'select': me.onAllergyTypeSelect
											}
										},
										{
											xtype     : 'mitos.allergieslocationcombo',
											fieldLabel: i18n['location'],
											name      : 'location',
											action    : 'location',
											width     : 225,
											labelWidth: 70,
											listeners : {
												scope   : me,
												'select': me.onLocationSelect
											}

										},
										{
											fieldLabel: i18n['begin_date'],
											xtype     : 'datefield',
											format    : 'Y-m-d',
											name      : 'begin_date'

										}

									]

								},
								{
									/**
									 * Line two
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											xtype          : 'mitos.allergiescombo',
											fieldLabel     : i18n['allergy'],
											action         : 'allergie_name',
											name           : 'allergy',
											enableKeyEvents: true,
											disabled       : true,
											width          : 225,
											labelWidth     : 70,
											listeners      : {
												scope   : me,
												'select': me.onLiveSearchSelect,
												change  : me.disableFieldLogic
											}
										},
										{
											xtype          : 'medicationlivetsearch',
											fieldLabel     : i18n['allergy'],
											hideLabel      : false,
											action         : 'drug_name',
											name           : 'allergy',
											hidden         : true,
											disabled       : true,
											enableKeyEvents: true,
											width          : 225,
											labelWidth     : 70,
											listeners      : {
												scope   : me,
												'select': me.onLiveSearchSelect,
												change  : me.disableFieldLogic
											}
										},
										{
											xtype : 'textfield',
											hidden: true,
											name  : 'allergy_id',
											action: 'idField'
										},
										{
											xtype     : 'mitos.allergiesabdominalcombo',
											fieldLabel: i18n['reaction'],
											name      : 'reaction',
											disabled  : true,
											width     : 225,
											labelWidth: 70,
											listeners : {
												scope : me,
												change: me.disableFieldLogic
											}

										},
										{
											xtype     : 'mitos.allergieslocalcombo',
											fieldLabel: i18n['reaction'],
											name      : 'reaction',
											hidden    : true,
											disabled  : true,
											width     : 225,
											labelWidth: 70,
											listeners : {
												scope : me,
												change: me.disableFieldLogic
											}

										},
										{
											xtype     : 'mitos.allergiesskincombo',
											fieldLabel: i18n['reaction'],
											name      : 'reaction',
											hidden    : true,
											disabled  : true,
											width     : 225,
											labelWidth: 70,
											listeners : {
												scope : me,
												change: me.disableFieldLogic
											}

										},
										{
											xtype     : 'mitos.allergiessystemiccombo',
											fieldLabel: i18n['reaction'],
											name      : 'reaction',
											hidden    : true,
											disabled  : true,
											width     : 225,
											labelWidth: 70,
											listeners : {
												scope : me,
												change: me.disableFieldLogic
											}

										},
										{
											fieldLabel: i18n['end_date'],
											xtype     : 'datefield',
											format    : 'Y-m-d',
											name      : 'end_date'
										}

									]

								},
								{
									/**
									 * Line three
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											xtype     : 'mitos.allergiesseveritycombo',
											fieldLabel: i18n['severity'],
											name      : 'severity',
											width     : 225,
											labelWidth: 70

										}


									]
								}
							]
						}
					]
				}),
				bbar   : [
					'->', {
						text   : i18n['reviewed'],
						action : 'review',
						itemId : 'review_allergies',
						scope  : me,
						handler: me.onReviewed
					}
				]
			},
			{
				/**
				 * Active Problem Card panel
				 */

				xtype  : 'grid',
				action : 'patientMedicalListGrid',
				store  : me.patientMedicalIssuesStore,
				columns: [

					{
						header   : i18n['problem'],
						flex     : 1,
						dataIndex: 'code_text'
					},
					{
						xtype    : 'datecolumn',
						header   : i18n['begin_date'],
						width    : 100,
						format   : 'Y-m-d',
						dataIndex: 'begin_date'
					},
					{
						xtype    : 'datecolumn',
						header   : i18n['end_date'],
						width    : 100,
						format   : 'Y-m-d',
						dataIndex: 'end_date'
					}

				],
				plugins: Ext.create('App.classes.grid.RowFormEditing', {
					autoCancel  : false,
					errorSummary: false,
					clicksToEdit: 1,

					formItems: [
						{
							title  : i18n['general'],
							xtype  : 'container',
							padding: 10,
							layout : 'vbox',
							items  : [
								{
									/**
									 * Line one
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											xtype          : 'liveicdxsearch',
											fieldLabel     : i18n['problem'],
											name           : 'code_text',
											allowBlank     : false,
											hideLabel      : false,
											itemId         : 'medicalissues',
											action         : 'medicalissues',
											enableKeyEvents: true,
											width          : 510,
											labelWidth     : 70,
											listeners      : {
												scope   : me,
												'select': me.onLiveSearchSelect
											}
										},
										{
											xtype : 'textfield',
											hidden: true,
											name  : 'code',
											action: 'idField'
										},


										{
											fieldLabel: i18n['begin_date'],
											xtype     : 'datefield',
											width     : 200,
											labelWidth: 80,
											format    : 'Y-m-d',
											name      : 'begin_date'

										}

									]

								},
								{
									/**
									 * Line two
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [

										{
											fieldLabel: i18n['ocurrence'],
											width     : 250,
											labelWidth: 70,
											xtype     : 'mitos.occurrencecombo',
											name      : 'ocurrence'

										},

										{
											fieldLabel: i18n['outcome'],
											xtype     : 'mitos.outcome2combo',
											width     : 250,
											labelWidth: 70,
											name      : 'outcome'

										},
										{
											fieldLabel: i18n['end_date'],
											xtype     : 'datefield',
											width     : 200,
											labelWidth: 80,
											format    : 'Y-m-d',
											name      : 'end_date'

										}

									]

								},
								{
									/**
									 * Line three
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [

										{
											xtype     : 'textfield',
											width     : 250,
											labelWidth: 70,
											fieldLabel: i18n['referred_by'],
											name      : 'referred_by'
										}

									]
								}
							]
						}

					]
				}),
				bbar   : [
					'->', {
						text   : i18n['reviewed'],
						action : 'review',
						itemId : 'review_active_problems',
						scope  : me,
						handler: me.onReviewed
					}
				]
			},
			{
				/**
				 * Surgery Card panel
				 */

				xtype  : 'grid',
				action : 'patientSurgeryListGrid',
				store  : me.patientSurgeryStore,
				columns: [
					{
						header   : i18n['surgery'],
						width    : 100,
						flex     : 1,
						dataIndex: 'surgery'
					},
					{
						xtype    : 'datecolumn',
						header   : i18n['date'],
						width    : 100,
						format   : 'Y-m-d',
						dataIndex: 'date'
					}

				],
				plugins: Ext.create('App.classes.grid.RowFormEditing', {
					autoCancel  : false,
					errorSummary: false,
					clicksToEdit: 1,
					formItems   : [
						{
							title  : i18n['general'],
							xtype  : 'container',
							padding: 10,
							layout : 'vbox',
							items  : [
								{
									/**
									 * Line one
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											fieldLabel     : i18n['surgery'],
											name           : 'surgery',
											hideLabel      : false,
											allowBlank     : false,
											width          : 510,
											labelWidth     : 70,
											xtype          : 'surgerieslivetsearch',
											itemId         : 'surgery',
											action         : 'surgery',
											enableKeyEvents: true,
											listeners      : {
												scope   : me,
												'select': me.onLiveSearchSelect
											}
										},
										{
											xtype : 'textfield',
											hidden: true,
											name  : 'surgery_id',
											action: 'idField'
										},
										{
											fieldLabel: i18n['date'],
											xtype     : 'datefield',
											width     : 200,
											labelWidth: 80,
											format    : 'Y-m-d',
											name      : 'date'

										}

									]

								},
								{
									/**
									 * Line two
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											fieldLabel: i18n['notes'],
											xtype     : 'textfield',
											width     : 510,
											labelWidth: 70,
											name      : 'notes'

										},
										{
											fieldLabel: i18n['outcome'],
											xtype     : 'mitos.outcome2combo',
											width     : 200,
											labelWidth: 80,
											name      : 'outcome'

										}


									]

								},
								{
									/**
									 * Line three
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											xtype     : 'textfield',
											width     : 260,
											labelWidth: 70,

											fieldLabel: i18n['referred_by'],
											name      : 'referred_by'
										}

									]
								}
							]
						}

					]
				}),
				bbar   : [
					'->', {
						text   : i18n['reviewed'],
						action : 'review',
						itemId : 'review_surgery',
						scope  : me,
						handler: me.onReviewed
					}
				]
			},
			{
				/**
				 * Dental Card panel
				 */

				xtype  : 'grid',
				action : 'patientDentalListGrid',
				store  : me.patientDentalStore,
				columns: [
					{
						header   : i18n['title'],
						width    : 100,
						dataIndex: 'title'
					},
					{
						xtype    : 'datecolumn',
						header   : i18n['begin_date'],
						width    : 100,
						format   : 'Y-m-d',
						dataIndex: 'begin_date'
					},
					{
						xtype    : 'datecolumn',
						header   : i18n['end_date'],
						flex     : 1,
						format   : 'Y-m-d',
						dataIndex: 'end_date'
					}
				],
				plugins: Ext.create('App.classes.grid.RowFormEditing', {
					autoCancel  : false,
					errorSummary: false,
					clicksToEdit: 1,
					formItems   : [
						{
							title  : i18n['general'],
							xtype  : 'container',
							padding: 10,
							layout : 'vbox',
							items  : [
								{
									/**
									 * Line one
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [

										{   xtype     : 'textfield',
											width     : 225,
											labelWidth: 70,
											fieldLabel: i18n['title'],
											action    : 'dental',
											name      : 'title'
										},
//                                        {
//   		                                    xtype:'textfield',
//   		                                    hidden:true,
//   		                                    name:'immunization_id',
//   		                                    action:'idField'
//   	                                    },
										{
											fieldLabel: i18n['begin_date'],
											xtype     : 'datefield',
											width     : 200,
											labelWidth: 80,
											format    : 'Y-m-d',
											name      : 'begin_date'

										},
										{
											fieldLabel: i18n['outcome'],
											xtype     : 'mitos.outcome2combo',
											width     : 250,
											labelWidth: 70,
											name      : 'outcome'

										}

									]

								},
								{
									/**
									 * Line two
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [

										{
											xtype     : 'textfield',
											width     : 225,
											labelWidth: 70,
											fieldLabel: i18n['referred_by'],
											name      : 'referred_by'
										},

										{
											fieldLabel: i18n['end_date'],
											xtype     : 'datefield',
											width     : 200,
											labelWidth: 80,
											format    : 'Y-m-d',
											name      : 'end_date'

										},
										{
											fieldLabel: i18n['ocurrence'],
											xtype     : 'mitos.occurrencecombo',
											width     : 250,
											labelWidth: 70,
											name      : 'ocurrence'

										}

									]

								}
							]
						}

					]
				}),
				bbar   : [
					'->', {
						text   : i18n['reviewed'],
						action : 'review',
						itemId : 'review_dental',
						scope  : me,
						handler: me.onReviewed
					}
				]
			},
			{
				/**
				 * Medications panel
				 */

				xtype  : 'grid',
				action : 'patientMedicationsListGrid',
				store  : me.patientMedicationsStore,
				columns: [
					{
						header   : i18n['medication'],
						flex     : 1,
						dataIndex: 'medication'
					},
					{
						xtype    : 'datecolumn',
						header   : i18n['begin_date'],
						width    : 100,
						format   : 'Y-m-d',
						dataIndex: 'begin_date'
					},
					{
						xtype    : 'datecolumn',
						header   : i18n['end_date'],
						width    : 100,
						format   : 'Y-m-d',
						dataIndex: 'end_date'
					}
				],
				plugins: Ext.create('App.classes.grid.RowFormEditing', {
					autoCancel  : false,
					errorSummary: false,
					clicksToEdit: 1,

					formItems: [
						{
							title  : i18n['general'],
							xtype  : 'container',
							padding: 10,
							layout : 'vbox',
							items  : [
								{
									/**
									 * Line one
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											xtype          : 'medicationlivetsearch',
											fieldLabel     : i18n['medication'],
											hideLabel      : false,
											itemId         : 'medication',
											name           : 'medication',
											action         : 'medication',
											enableKeyEvents: true,
											width          : 520,
											labelWidth     : 70,
											listeners      : {
												scope   : me,
												'select': me.onLiveSearchSelect
											}
										},
										{
											xtype : 'textfield',
											hidden: true,
											name  : 'medication_id',
											action: 'idField'
										},

										{
											fieldLabel: i18n['begin_date'],
											xtype     : 'datefield',
											width     : 200,
											labelWidth: 80,
											format    : 'Y-m-d',
											name      : 'begin_date'

										}

									]

								},
								{
									/**
									 * Line two
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [
										{
											fieldLabel: i18n['outcome'],
											xtype     : 'mitos.outcome2combo',
											width     : 250,
											labelWidth: 70,
											name      : 'outcome'
										},
										{
											xtype     : 'textfield',
											width     : 260,
											fieldLabel: i18n['referred_by'],
											name      : 'referred_by'
										},
										{
											fieldLabel: i18n['end_date'],
											xtype     : 'datefield',
											width     : 200,
											labelWidth: 80,
											format    : 'Y-m-d',
											name      : 'end_date'
										}

									]

								},
								{
									/**
									 * Line three
									 */
									xtype   : 'fieldcontainer',
									layout  : 'hbox',
									defaults: { margin: '0 10 5 0' },
									items   : [

										{
											fieldLabel: i18n['ocurrence'],
											width     : 250,
											labelWidth: 70,
											xtype     : 'mitos.occurrencecombo',
											name      : 'ocurrence'

										}

									]
								}
							]
						}
					]
				}),
				bbar   : [
					'->', {
						text   : i18n['reviewed'],
						action : 'review',
						itemId : 'review_medications',
						scope  : me,
						handler: me.onReviewed
					}
				]
			},
			{
				/**
				 * Lab panel
				 */
				xtype : 'container',
				action: 'patientLabs',
				layout: 'border',
				items : [
					{
						xtype     : 'panel',
						region    : 'north',
						layout    : 'border',
						bodyBorder: false,
						border    : false,
						height    : 350,
						split     : true,
						items     : [
							{
								xtype    : 'grid',
								region   : 'west',
								width    : 290,
								split    : true,
								store    : me.labPanelsStore,
								columns  : [
									{
										header   : i18n['laboratories'],
										dataIndex: 'label',
										flex     : 1
									}
								],
								listeners: {
									scope          : me,
									itemclick      : me.onLabPanelSelected,
									selectionchange: me.onLabPanelSelectionChange
								}
							},
							{
								xtype : 'panel',
								action: 'labPreviewPanel',
								title : i18n['laboratory_preview'],
								region: 'center',
								items : [
									me.uploadWin = Ext.create('Ext.window.Window', {
										draggable  : false,
										closable   : false,
										closeAction: 'hide',
										items      : [
											{
												xtype      : 'form',
												bodyPadding: 10,
												width      : 400,
												items      : [
													{
														xtype     : 'filefield',
														name      : 'filePath',
														buttonText: i18n['select_a_file'] + '...',
														anchor    : '100%'
													}
												],
												//   url: 'dataProvider/DocumentHandler.php'
												api        : {
													submit: DocumentHandler.uploadDocument
												}
											}
										],
										buttons    : [
											{
												text   : i18n['cancel'],
												handler: function() {
													me.uploadWin.close();
												}
											},
											{
												text   : i18n['upload'],
												scope  : me,
												handler: me.onLabUpload
											}
										]
									})
								]
							}
						],
						tbar      : [
							'->',
							{
								text: i18n['scan']
							},
							'-',
							{
								text    : i18n['upload'],
								disabled: true,
								action  : 'uploadBtn',
								scope   : me,
								handler : me.onLabUploadWind
							}
						]
					},
					{
						xtype : 'container',
						region: 'center',
						layout: 'border',
						split : true,
						items : [
							{
								xtype      : 'form',
								title      : i18n['laboratory_entry_form'],
								region     : 'west',
								width      : 290,
								split      : true,
								bodyPadding: 5,
								autoScroll : true,
								bbar       : [
									'->',
									{
										text   : i18n['reset'],
										scope  : me,
										handler: me.onLabResultsReset
									},
									'-',
									{
										text   : i18n['sign'],
										scope  : me,
										handler: me.onLabResultsSign
									},
									'-',
									{
										text   : i18n['save'],
										scope  : me,
										handler: me.onLabResultsSave
									}
								]
							},
							{
								xtype : 'panel',
								region: 'center',
								height: 300,
								split : true,
								items : [
									{
										xtype    : 'lalboratoryresultsdataview',
										action   : 'lalboratoryresultsdataview',
										store    : Ext.create('App.store.patient.PatientLabsResults'),
										listeners: {
											scope    : me,
											itemclick: me.onLabResultClick
										}
									}
								]
							}
						]
					}
				]
			}
		];

		me.dockedItems = [
			{
				xtype: 'toolbar',
				items: [
					{

						text        : i18n['immunization'],
						enableToggle: true,
						toggleGroup : 'medicalWin',
						pressed     : true,
						itemId      : 'immunization',
						action      : 'immunization',
						scope       : me,
						handler     : me.cardSwitch
					},
					'-',
					{
						text        : i18n['allergies'],
						enableToggle: true,
						toggleGroup : 'medicalWin',
						itemId      : 'allergies',
						action      : 'allergies',
						scope       : me,
						handler     : me.cardSwitch
					},
					'-',
					{
						text        : i18n['active_problems'],
						enableToggle: true,
						toggleGroup : 'medicalWin',
						itemId      : 'issues',
						action      : 'issues',
						scope       : me,
						handler     : me.cardSwitch
					},
					'-',
					{
						text        : i18n['surgeries'],
						enableToggle: true,
						toggleGroup : 'medicalWin',
						itemId      : 'surgery',
						action      : 'surgery',
						scope       : me,
						handler     : me.cardSwitch
					},
					'-',
					{
						text        : i18n['dental'],
						enableToggle: true,
						toggleGroup : 'medicalWin',
						itemId      : 'dental',
						action      : 'dental',
						scope       : me,
						handler     : me.cardSwitch
					},
					'-',
					{
						text        : i18n['medications'],
						enableToggle: true,
						toggleGroup : 'medicalWin',
						itemId      : 'medications',
						action      : 'medications',
						scope       : me,
						handler     : me.cardSwitch
					},
					'-',
					{
						text        : i18n['laboratories'],
						enableToggle: true,
						toggleGroup : 'medicalWin',
						itemId      : 'laboratories',
						action      : 'laboratories',
						scope       : me,
						handler     : me.cardSwitch
					},
					'->',
					{
						text   : i18n['add_new'],
						action : 'AddRecord',
						scope  : me,
						handler: me.onAddItem
					}
				]
			}
		];
		me.listeners = {
			scope: me,
			show : me.onMedicalWinShow,
			close : me.onMedicalWinClose
		};
		me.callParent(arguments);
	},

	//*******************************************************

	onLabPanelSelected: function(grid, model) {
		var me = this,
			formPanel = me.query('[action="patientLabs"]')[0].down('form'),
			dataView = me.query('[action="lalboratoryresultsdataview"]')[0],
			store = dataView.store,
			fields = model.data.fields;

		me.currLabPanelId = model.data.id;
		me.removeLabDocument();
		formPanel.removeAll();

		formPanel.add({
			xtype : 'textfield',
			name  : 'id',
			hidden: true
		});
		for(var i = 0; i < fields.length; i++) {
			formPanel.add({
				xtype     : 'fieldcontainer',
				layout    : 'hbox',
				margin    : 0,
				anchor    : '100%',
				fieldLabel: fields[i].code_text_short || fields[i].loinc_name,
				labelWidth: 130,
				items     : [
					{
						xtype     : 'textfield',
						name      : fields[i].loinc_number,
						flex      : 1,
						allowBlank: fields[i].required_in_panel != 'R'
					},
					{
						xtype: 'mitos.unitscombo',
						value: fields[i].default_unit,
						name : fields[i].loinc_number + '_unit',
						width: 90
					}
				]
			});
		}

		store.load({params: {parent_id: model.data.id}});
	},

	onLabPanelSelectionChange: function(model, record) {
		this.query('[action="uploadBtn"]')[0].setDisabled(record.length == 0);
	},

	onLabUploadWind: function() {
		var me = this,
			previewPanel = me.query('[action="labPreviewPanel"]')[0];
		me.uploadWin.show();
		me.uploadWin.alignTo(previewPanel.el.dom, 'tr-tr', [-5, 30])
	},

	onLabUpload: function(btn) {
		var me = this,
			form = me.uploadWin.down('form').getForm(),
			win = btn.up('window');

		if(form.isValid()) {
			form.submit({
				waitMsg: i18n['uploading_laboratory'] + '...',
				params : {
					pid    : app.patient.pid,
					docType: 'laboratory',
					eid : app.currEncounterId
				},
				success: function(fp, o) {
					win.close();
					me.getLabDocument(o.result.doc.url);
					me.addNewLabResults(o.result.doc.id);
				},
				failure: function(fp, o) {
					win.close();

				}
			});
		}
	},

	onLabResultClick: function(view, model) {
		var me = this,
			form = me.query('[action="patientLabs"]')[0].down('form').getForm();

		if(me.currDocUrl != model.data.document_url) {
			form.reset();
			model.data.data.id = model.data.id;
			form.setValues(model.data.data);
			me.getLabDocument(model.data.document_url);
			me.currDocUrl = model.data.document_url;
		}

	},

	onLabResultsSign: function() {
		var me = this,
			form = me.query('[action="patientLabs"]')[0].down('form').getForm(),
			dataView = me.query('[action="lalboratoryresultsdataview"]')[0],
			store = dataView.store,
			values = form.getValues(),
			record = dataView.getSelectionModel().getLastSelected();

		if(form.isValid()) {
			if(values.id) {
				me.passwordVerificationWin(function(btn, password) {
					if(btn == 'ok') {
						User.verifyUserPass(password, function(provider, response) {
							if(response.result) {
								say(record);
								Medical.signPatientLabsResultById(record.data.id, function(provider, response) {
									store.load({params: {parent_id: me.currLabPanelId}});
								});
							} else {
								Ext.Msg.show({
									title  : 'Oops!',
									msg    : i18n['incorrect_password'],
									//buttons:Ext.Msg.OKCANCEL,
									buttons: Ext.Msg.OK,
									icon   : Ext.Msg.ERROR,
									fn     : function(btn) {
										if(btn == 'ok') {
											//me.onLabResultsSign();
										}
									}
								});
							}
						});
					}
				});
			} else {
				Ext.Msg.show({
					title  : 'Oops!',
					msg    : i18n['nothing_to_sign'],
					//buttons:Ext.Msg.OKCANCEL,
					buttons: Ext.Msg.OK,
					icon   : Ext.Msg.ERROR,
					fn     : function(btn) {
						if(btn == 'ok') {
							//me.onLabResultsSign();
						}
					}
				});
			}

		}
	},

	onLabResultsSave: function(btn) {
		var me = this,
			form = btn.up('form').getForm(),
			dataView = me.query('[action="lalboratoryresultsdataview"]')[0],
			store = dataView.store,
			values = form.getValues(),
			record = dataView.getSelectionModel().getLastSelected();

		if(form.isValid()) {
			Medical.updatePatientLabsResult(values, function() {
				store.load({params: {parent_id: record.data.parent_id}});
				form.reset();
			});
		}
	},


	addNewLabResults: function(docId) {
		var me = this,
			dataView = me.query('[action="lalboratoryresultsdataview"]')[0],
			store = dataView.store,
			params = {
				parent_id  : me.currLabPanelId,
				document_id: docId
			};
		Medical.addPatientLabsResult(params, function(provider, response) {
			store.load({params: {parent_id: me.currLabPanelId}});

		});
	},

	onReviewed: function(btn) {
		var me = this,
			BtnId = btn.itemId,
			params = {
				eid : app.currEncounterId,
				area: BtnId
			};

		Medical.reviewMedicalWindowEncounter(params, function(provider, response) {
			me.msg('Sweet!', i18n['succefully_reviewed']);
		});
	},

	onLabResultsReset: function(btn) {
		var form = btn.up('form').getForm();
		form.reset();
	},

	getLabDocument: function(src) {
		var panel = this.query('[action="labPreviewPanel"]')[0];
		panel.remove(this.doc);
		panel.add(this.doc = Ext.create('App.classes.ManagedIframe', {src: src}));
	},

	removeLabDocument: function(src) {
		var panel = this.query('[action="labPreviewPanel"]')[0];
		panel.remove(this.doc);
	},

	//*********************************************************

	onLiveSearchSelect: function(combo, model) {

		var me = this,
			field, field2, id;
		if(combo.action == 'immunizations') {
			id = model[0].data.id;
			field = combo.up('container').query('[action="idField"]')[0];
			field.setValue(id);
		}
		else if(combo.id == 'allergie_name' || combo.id == 'drug_name') {
			id = model[0].data.id;
			field = combo.up('fieldcontainer').query('[action="idField"]')[0];
			field.setValue(id);

		}
		else if(combo.action == 'medicalissues') {
			id = model[0].data.code;
			field = combo.up('fieldcontainer').query('[action="idField"]')[0];
			field2 = combo.up('fieldcontainer').query('[action="medicalissues"]')[0];
			field.setValue(id);
			field2.setValue(model[0].data.code_text);
		}
		else if(combo.action == 'surgery') {
			id = model[0].data.id;
			field = combo.up('fieldcontainer').query('[action="idField"]')[0];
			field.setValue(id);

		}
		else if(combo.action == 'medication') {
			id = model[0].data.id;
			field = combo.up('fieldcontainer').query('[action="idField"]')[0];
			field.setValue(id);
		}

	},

	onAddItem       : function() {

		var me = this, grid = this.getLayout().getActiveItem(), store = grid.store,
			params;

		grid.editingPlugin.cancelEdit();
		store.insert(0, {
			created_uid: app.user.id,
			pid        : app.patient.pid,
			create_date: new Date(),
			eid        : app.currEncounterId,
			begin_date : new Date()

		});
		grid.editingPlugin.startEdit(0, 0);
		if(app.currEncounterId != null) {
			if(grid.action == 'patientImmuListGrid') {
				params = {
					eid : app.currEncounterId,
					area: 'review_immunizations'
				};
			} else if(grid.action == 'patientAllergiesListGrid') {
				params = {
					eid : app.currEncounterId,
					area: 'review_allergies'
				};
			} else if(grid.action == 'patientMedicalListGrid') {
				params = {
					eid : app.currEncounterId,
					area: 'review_active_problems'
				};
			} else if(grid.action == 'patientSurgeryListGrid') {
				params = {
					eid : app.currEncounterId,
					area: 'review_surgery'
				};
			} else if(grid.action == 'patientDentalListGrid') {
				params = {
					eid : app.currEncounterId,
					area: 'review_dental'
				};
			} else if(grid.action == 'patientMedicationsListGrid') {
				params = {
					eid : app.currEncounterId,
					area: 'review_medications'
				};
			}
			Medical.reviewMedicalWindowEncounter(params);
		}


	},
	hideall         : function(combo, skinCombo, localCombo, abdominalCombo, systemicCombo) {

		skinCombo.hide(true);
		skinCombo.setDisabled(true);
		skinCombo.reset();
		localCombo.hide(true);
		localCombo.setDisabled(true);
		localCombo.reset();
		abdominalCombo.hide(true);
		abdominalCombo.setDisabled(true);
		abdominalCombo.reset();
		systemicCombo.hide(true);
		systemicCombo.setDisabled(true);
		systemicCombo.reset();

	},
	onLocationSelect: function(combo, record) {
		var me = this,
			skinCombo = combo.up('form').getForm().findField('skinreaction'),
			localCombo = combo.up('form').getForm().findField('localreaction'),
			abdominalCombo = combo.up('form').getForm().findField('abdominalreaction'),
			systemicCombo = combo.up('form').getForm().findField('systemicreaction'),
			value = combo.getValue();

		me.hideall(combo, skinCombo, localCombo, abdominalCombo, systemicCombo);
		if(value == 'Skin') {
			skinCombo.show(true);
			skinCombo.setDisabled(false);
		} else if(value == 'Local') {
			localCombo.show(true);
			localCombo.setDisabled(false);
		} else if(value == 'Abdominal') {
			abdominalCombo.show(true);
			abdominalCombo.setDisabled(false);
		} else if(value == 'Systemic / Anaphylactic') {
			systemicCombo.show(true);
			systemicCombo.setDisabled(false);

		}
	},


	disableFieldLogic: function(field, newValue) {
		field.setDisabled((newValue == '' || newValue == null));
	},

	onAllergyTypeSelect: function(combo, record) {
		var me = this,
			allergyCombo = combo.up('form').getForm().findField('allergie_name'),
			drugLiveSearch = combo.up('form').getForm().findField('drug_name');

		if(record[0].data.allergy_type == 'Drug'){
			allergyCombo.hide(true);
			allergyCombo.setDisabled(true);
			allergyCombo.reset();
			drugLiveSearch.show(true);
			drugLiveSearch.setDisabled(false);
		}
		else if(record[0].data.allergy_type == '' || record[0].data.allergy_type == null) {
			allergyCombo.setDisabled(true);
			drugLiveSearch.hide(true);
			drugLiveSearch.setDisabled(true);
			allergyCombo.show(true);
		}
		else {
			drugLiveSearch.hide(true);
			drugLiveSearch.setDisabled(true);
			allergyCombo.show(true);
			allergyCombo.setDisabled(false);
			allergyCombo.reset();
			allergyCombo.store.load({params: {allergy_type: record[0].data.allergy_type}})
		}


	},
	setDefaults: function(options) {
		var data;

		if(options.update) {
			data = options.update[0].data;
			data.updated_uid = app.user.id;
		} else if(options.create) {

		}
	},

	cardSwitch: function(btn) {
		var me = this,
			layout = me.getLayout(),
			addBtn = me.down('toolbar').query('[action="AddRecord"]')[0],
			p = app.patient,
			title;

		me.pid = p.pid;
		addBtn.show();

		if(btn.action == 'immunization') {
			layout.setActiveItem(0);
			title = 'Immunizations';

		} else if(btn.action == 'allergies') {
			layout.setActiveItem(1);
			title = 'Allergies';

		} else if(btn.action == 'issues') {
			layout.setActiveItem(2);
			title = 'Medical Issues';

		} else if(btn.action == 'surgery') {
			layout.setActiveItem(3);
			title = 'Surgeries';

		} else if(btn.action == 'dental') {
			layout.setActiveItem(4);
			title = 'Dentals';

		} else if(btn.action == 'medications') {
			layout.setActiveItem(5);
			title = 'Medications';

		} else if(btn.action == 'laboratories') {
			layout.setActiveItem(6);
			title = 'Laboratories';
			addBtn.hide();
		}

		me.setTitle(p.name + ' (' + title + ') ' + (p.readOnly ? '-  <span style="color:red">[Read Mode]</span>' : ''));

	},

	onMedicalWinShow: function() {
		var me = this,
			reviewBts = me.query('button[action="review"]'),
			p = app.patient;

		me.pid = p.pid;
		me.setTitle(p.name + (p.readOnly ? ' <span style="color:red">[' + i18n['read_mode'] + ']</span>' : ''));
		me.setReadOnly(app.patient.readOnly);
		for(var i = 0; i < reviewBts.length; i++) {
			reviewBts[i].setVisible((app.currEncounterId != null));
		}
		me.labPanelsStore.load();
		me.patientImmuListStore.load({params: {pid: app.patient.pid}});
		me.patientAllergiesListStore.load({params: {pid: app.patient.pid}});
		me.patientMedicalIssuesStore.load({params: {pid: app.patient.pid}});
		me.patientSurgeryStore.load({params: {pid: app.patient.pid}});
		me.patientDentalStore.load({params: {pid: app.patient.pid}});
		me.patientMedicationsStore.load({params: {pid: app.patient.pid}});

    },

    onMedicalWinClose:function(){
        if(app.currCardCmp.id == 'panelSummary'){

            app.currCardCmp.loadStores();

        }

    }


});