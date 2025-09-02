import $ from 'jquery'
import toastr from 'toastr'
import Swal from 'sweetalert2';
import bootstrap from '../externals/bootstrap/js/bootstrap.bundle.min.js'; 

document.getElementById('preview-reservation-btn').addEventListener('click', function (e) {
    e.stopPropagation();
    e.preventDefault();

    const startDate = document.getElementById('reservation_form_startDate').value;
    const endDate = document.getElementById('reservation_form_endDate').value;
    const modal = new bootstrap.Modal(document.getElementById('reservation-modal'));

    if (!startDate || !endDate) {
        toastr.error('Veuillez choisir une date de début et une date de fin.');
         document.querySelector('.alert-no-date').classList.remove('d-none');
        return;
    }


    // Inject dates into modal
    document.getElementById('recap-start').textContent = startDate;
    document.getElementById('recap-end').textContent = endDate;

    // Show modal
    document.querySelector('.alert-no-date').classList.add('d-none');
    modal.show();
    
});

