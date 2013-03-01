/*
 GaiaEHR (Electronic Health Records)
 Layout.js
 Copyright (C) 2013 Certun

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

Ext.define('App.store.administration.LayoutTreeModel', {
    model: 'App.model.administration.LayoutTreeModel',
    extend: 'Ext.data.Store',
    proxy: {
        type: 'direct',
        api: {
            read: FormLayoutBuilder.getFormFieldsTree,
            create: FormLayoutBuilder.createFormField,
            update: FormLayoutBuilder.updateFormField,
            destroy: FormLayoutBuilder.removeFormField
        }
    },
    autoSync: true,
    remoteSort: true,
    autoLoad: false
});