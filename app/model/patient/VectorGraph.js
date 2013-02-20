 /**
 * Created by JetBrains PhpStorm.
 * User: Ernesto J. Rodriguez (Certun)
 * File:
 * Date: 2/18/12
 * Time: 11:09 PM
 */
Ext.define('App.model.patient.VectorGraph', {
	extend   : 'Ext.data.Model',
	table: {
		name:'vectorgraph',
		engine:'InnoDB',
		autoIncrement:1,
		charset:'utf8',
		collate:'utf8_bin',
		comment:'Vector Graphics'
	},
	fields   : [
		{name: 'age_mos', type: 'float'},
		{name: 'height', type: 'float'},
		{name: 'PP', type: 'float'},
		{name: 'P3', type: 'float'},
		{name: 'P5', type: 'float'},
		{name: 'P10', type: 'float'},
		{name: 'P25', type: 'float'},
		{name: 'P50', type: 'float'},
		{name: 'P75', type: 'float'},
		{name: 'P85', type: 'float'},
		{name: 'P90', type: 'float'},
		{name: 'P95', type: 'float'},
		{name: 'P97', type: 'float'}
	],
	proxy    : {
		type       : 'direct',
		api        : {
			read: VectorGraph.getGraphData
		},
		reader     : {
			type: 'json'
		}
	}

});