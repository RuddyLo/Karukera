const { ajax } = require("jquery");

$(document).ready(() =>{
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
                name: 'apartment.is_favorite',
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
                           
                             
                            </span>
                      </div>
                      
                      
                  `
                }
            },
        ],

    });

});