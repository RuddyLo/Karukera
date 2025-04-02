import Swal from 'sweetalert2';
import $ from 'jquery';
import select2 from 'select2';

$(document).ready(function() {
    $('.dropdown-checkbox').select2({
        placeholder: "Sélectionnez les équipements",
        allowClear: true,
        width: '100%',
        
    });
});



$(document).on('click', '#toggle-apartment-delete', function (event) {
    event.preventDefault(); // Empêche le comportement par défaut du bouton

    Swal.fire({
        title: "Êtes-vous sûr de vouloir supprimer?",
        text: "Cette action est irréversible !",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#d3d3d3",
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler"
    }).then((result) => {
        if (result.isConfirmed) {
            $('#delete-apartment-btn').trigger('click'); // Exécute la suppression si confirmé
        }
    });
});

$(document).on('click', '.event-delete-equipment', function () {
    
    let dataUuid = $(this);
    console.log(dataUuid.data('uuid'));
    Swal.fire({
        title: 'Suppression',
        text: "Voulez vous vraiment supprimer cet équipement?",
        icon: "warning",
        confirmButtonText: "Confirmer",
        showCancelButton: true,
        cancelButtonText: "Non",
        confirmButtonColor: 'green',
        cancelButtonColor: '#ddd',
    }).then((result) => {
        if(result.isConfirmed) {
            
            $("#delete-equipment-form").find("#delete-equipment-id").val(dataUuid.data('uuid'));
            $("#delete-equipment-form").submit();
        }
    })
});