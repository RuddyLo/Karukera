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

    if ($("#reservation-table").length) {
        let reservationStatusFilter = '';

        let reservationDataTable = $("#reservation-table").DataTable({
            responsive: true,
            "aaSorting": [],
            "bProcessing": true,
            "bFilter": true,
            "bServerSide": true,
            "iDisplayLength": 10,
            order: [[3, 'desc']],

            "ajax": {
                url: ajaxLink.reservation.list,
                data: function (data) {
                    if (data.order && data?.order[0]) {
                        data.order_by = data.columns[data.order[0].column].name + ' ' + data.order[0].dir;
                    }
                    data.status_filter = reservationStatusFilter;
                },
            },
            "columnDefs": [
                { targets: 0, name: 'r.id', orderable: true },
                { targets: 1, name: 'u.email', orderable: true },
                { targets: 2, name: 'a.name', orderable: true },
                { targets: 3, name: 'r.startDate', orderable: true },
                { targets: 4, name: 'reservation.rent', orderable: false },
                {
                    targets: 5,
                    name: 'reservation.caution',
                    orderable: false,
                    render: function (data) {
                        if (!data || !data.amount) return '<span class="text-muted">-</span>';
                        const labels = {
                            refunded: `<span class="badge bg-info">${data.amount} remboursés</span>`,
                            conserved: `<span class="badge bg-warning text-dark">${data.amount} conservée</span>`,
                            pending: '<span class="badge bg-secondary text-white">Caution non traitée</span>',
                        };
                        return labels[data.status] || data.amount;
                    }
                },
                {
                    targets: 6,
                    name: 'reservation.status',
                    orderable: false,
                    render: function (data) {
                        const labels = {
                            upcoming: '<span class="badge bg-primary">À venir</span>',
                            ongoing: '<span class="badge bg-success">En cours</span>',
                            finished: '<span class="badge bg-secondary">Terminée</span>',
                            canceled: '<span class="badge bg-dark">Annulée</span>',
                        };
                        return labels[data] || data;
                    }
                },
                {
                    targets: 7,
                    name: 'reservation.action',
                    orderable: false,
                    render: function (data, type, row) {
                        const id = row[0];
                        const status = row[6];
                        const isFinished = row[7];
                        const reviewToken = row[8];

                        let buttons = `<a title="Détails" href='${ajaxLink.reservation.show.replace('123456789', id)}' class='btn btn-primary'><i class="bi bi-eye-fill"></i></a>`;

                        if (isFinished && status !== 'canceled') {
                            buttons += `
                                <form class="d-inline m-0 send-review-form" method="post" action="${ajaxLink.reservation.sendReview.replace('123456789', id)}">
                                    <input type="hidden" name="_token" value="${reviewToken}">
                                    <button type="submit" title="Envoyer email avis" class="btn btn-outline-success"><i class="bi bi-envelope"></i></button>
                                </form>`;
                        }

                        if (status !== 'canceled') {
                            buttons += ` <button title="Annuler" class='btn btn-danger event-cancel-reservation' data-uuid='${id}'><i class="bi bi-x-circle-fill"></i></button>`;
                        }

                        return `<div class="d-flex justify-content-center gap-1 list-action-group">${buttons}</div>`;
                    }
                },
            ],
        });

        $("#reservation-status-filter").on('change', function () {
            reservationStatusFilter = $(this).val();
            reservationDataTable.ajax.reload();
        });

        $(document).on('click', '.event-cancel-reservation', function () {
            const id = $(this).data('uuid');

            Swal.fire({
                title: 'Annuler cette réservation ?',
                text: 'Cette action est irréversible. Les dates redeviendront disponibles sur le calendrier.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, annuler',
                cancelButtonText: 'Retour',
            }).then(result => {
                if (!result.isConfirmed) return;

                const url = ajaxLink.reservation.cancel.replace('123456789', id);
                const formData = new FormData();
                formData.append('_token', ajaxLink.reservation.cancelToken);

                fetch(url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        reservationDataTable.ajax.reload(null, false);
                        Swal.fire({ title: 'Réservation annulée !', icon: 'success', timer: 1500, showConfirmButton: false });
                    } else {
                        Swal.fire('Impossible', data.error || "Échec de l'annulation", 'warning');
                    }
                })
                .catch(() => Swal.fire('Erreur', 'Erreur réseau', 'error'));
            });
        });
    }

});