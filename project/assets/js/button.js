import Swal from 'sweetalert2';
import $ from 'jquery';

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