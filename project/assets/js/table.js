const { ajax } = require("jquery");
import $ from 'jquery';
import Swal from 'sweetalert2';

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
                                <button title="Suppression" class='btn btn-danger event-delete-apartment' data-uuid='${row[0]}'>
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                              </span>
                      </div>


                  `
                }
            },
        ],

    });

    $(document).on('click', '.event-delete-apartment', function () {
        const id = $(this).data('uuid');

        Swal.fire({
            title: 'Supprimer cet appartement ?',
            text: 'L\'appartement sera masqué du site (les réservations existantes sont conservées).',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then(result => {
            if (!result.isConfirmed) return;

            const url = ajaxLink.apartment.delete.replace('123456789', id);
            const formData = new FormData();
            formData.append('_token', ajaxLink.apartment.deleteToken);

            fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    apartmentDataTable.ajax.reload(null, false);
                    Swal.fire({ title: 'Supprimé !', icon: 'success', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Impossible', data.error || 'Échec de la suppression', 'warning');
                }
            })
            .catch(() => Swal.fire('Erreur', 'Erreur réseau', 'error'));
        });
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
                                <button title="Suppression" class='btn btn-danger event-delete-user' data-uuid='${row[0]}'>
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                              </span>
                      </div>


                  `
                }
            },
        ],

    });

    $(document).on('click', '.event-delete-user', function () {
        const id = $(this).data('uuid');

        Swal.fire({
            title: 'Supprimer cet utilisateur ?',
            text: 'Cette action est irréversible.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then(result => {
            if (!result.isConfirmed) return;

            const url = ajaxLink.user.delete.replace('123456789', id);
            const formData = new FormData();
            formData.append('_token', ajaxLink.user.deleteToken);

            fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    userDataTable.ajax.reload(null, false);
                    Swal.fire({ title: 'Supprimé !', icon: 'success', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Impossible', data.error || 'Échec de la suppression', 'warning');
                }
            })
            .catch(() => Swal.fire('Erreur', 'Erreur réseau', 'error'));
        });
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