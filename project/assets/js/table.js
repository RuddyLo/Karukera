const { ajax } = require("jquery");
import $ from 'jquery';

$(document).ready(() => {
    let apartmentDataTable = $("#apartment-table").DataTable({
        responsive: true,
        "aaSorting": [],
        "bProcessing": true,
        "bFilter": true,
        "bServerSide": true,
        "iDisplayLength": 10,
        order: [[0, 'desc']],

        "ajax": {
            url: ajaxLink.apartment.list,
            data: function (data) {
                if (data.order && data?.order[0]) {
                    data.order_by = data.columns[data.order[0].column].name + ' ' + data.order[0].dir;
                }
            },
        },
        "columnDefs": [
            {
                targets: 0,
                name: 'apartment.id',
                orderable: true,
            },
            {
                targets: 1,
                name: 'apartment.name',
                orderable: true,
            },
            {
                targets: 2,
                name: 'apartment.description',
                orderable: true,
            },
            {
                targets: 3,
                name: 'apartment.is_active',
                orderable: true,
            },
            {
                targets: 4,
                name: 'apartment.on_top',
                orderable: true,
            },
            {
                targets: 5,
                name: 'apartment.action',
                orderable: false,
                render: function (data, type, row) {
                    return `
                      <div class="d-flex justify-content-center list-action-group">
                          <span>
                                <a title="Visualisation" href='${ajaxLink.apartment.show.replace('123456789', row[0])}' id='${data}' class='btn btn-primary'>
                                   <i class="bi bi-eye-fill"></i>
                                </a>
                                <a title="Modifier FR" href='${ajaxLink.apartment.edit.replace('123456789', row[0])}?locale=fr' class='btn btn-secondary'>
    🇫🇷
</a>
<a title="Modifier EN" href='${ajaxLink.apartment.edit.replace('123456789', row[0])}?locale=en' class='btn btn-secondary'>
    🇺🇸
</a>
                                <button title="Suppression" id='delete-apartment' class='btn btn-danger event-delete-apartment' data-uuid=${data}>
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                              </span>
                      </div>
                      
                      
                  `
                }
            },
        ],

    });

    let userDataTable = $("#user-table").DataTable({
        responsive: true,
        "aaSorting": [],
        "bProcessing": true,
        "bFilter": true,
        "bServerSide": true,
        "iDisplayLength": 10,
        order: [[0, 'desc']],

        "ajax": {
            url: ajaxLink.user.list,
            data: function (data) {
                if (data.order && data?.order[0]) {
                    data.order_by = data.columns[data.order[0].column].name + ' ' + data.order[0].dir;
                }
            },
        },
        "columnDefs": [
            {
                targets: 0,
                name: 'user.id',
                orderable: true,
            },
            {
                targets: 1,
                name: 'useremail',
                orderable: true,
            },

            {
                targets: 2,
                name: 'user.action',
                orderable: false,
                render: function (data, type, row) {
                    return `
                      <div class="d-flex justify-content-center list-action-group">
                          <span>
                                <a title="Visualisation" href='${ajaxLink.apartment.show.replace('123456789', row[0])}' id='${data}' class='btn btn-primary'>
                                   <i class="bi bi-eye-fill"></i>
                                </a>
        
                                <button title="Suppression" id='delete-apartment' class='btn btn-danger event-delete-apartment' data-uuid=${data}>
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                              </span>
                      </div>
                      
                      
                  `
                }
            },
        ],

    });

    let equipmentDataTable = $("#equipment-table").DataTable({
        responsive: true,
        "aaSorting": [],
        "bProcessing": true,
        "bFilter": true,
        "bServerSide": true,
        "iDisplayLength": 10,
        order: [[0, 'desc']],

        "ajax": {
            url: ajaxLink.equipment.list,
            data: function (data) {
                if (data.order && data?.order[0]) {
                    data.order_by = data.columns[data.order[0].column].name + ' ' + data.order[0].dir;
                }
            },
        },
        "columnDefs": [
            {
                targets: 0,
                name: 'e.id',
                orderable: true,
            },
            {
                targets: 1,
                name: 'e.name',
                orderable: true,
            },

            {
                targets: 2,
                name: 'e.action',
                orderable: false,
                render: function (data, type, row) {
                    return `
                      <div class="d-flex justify-content-center list-action-group">
                          <span>
                                
                                <a title="Edition" href='${ajaxLink.equipment.edit.replace('123456789', row[0])}' id='${data}' class='btn btn-secondary'>
                                   <i class="bi bi-pencil-fill"></i>
                                </a>
        
                                <button title="Suppression" id='delete-apartment' class='btn btn-danger event-delete-equipment' data-uuid=${row[0]}>
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                              </span>
                      </div>
                      
                      
                  `
                }
            },
        ],

    });

});