import $ from 'jquery'
import toastr from 'toastr'
import Swal from 'sweetalert2';
/**
     * 
     * @param {*} imageInputSelector 
     * @param {*} imageElementSelector 
     * @param {*} imageSizeLimit 
     * @param {*} allowedFileTypes 
     */
function initializeImageUploader(imageInputSelector, imageElementSelector, imageSizeLimit = 2, allowedFileTypes = ['image/png', 'image/jpeg']) {
    let imageUrl = $(imageInputSelector);
    let image = $(imageElementSelector);

    // Ensure allowedFileTypes is an array
    if (!Array.isArray(allowedFileTypes)) {
        allowedFileTypes = [allowedFileTypes];
    }

    $(document).on('click', '.image-toggler', function () {
        imageUrl.click();
    });

    $(document).on('click', '.icon-toggler', function () {
        imageUrl.click();
    });

    $(document).on('change', imageInputSelector, function () {

        const file = this.files[0];

        if (file.size > imageSizeLimit * 1024 * 1024) {
            toastr.error('La taille du fichier ne doit pas dépasser ' + (imageSizeLimit) + ' Mo.');
            return;
        }
        if (!allowedFileTypes.includes(file.type)) {
            toastr.error('Le fichier doit être au format PNG, JPG ou JPEG.');
            return;
        }
        if (file && allowedFileTypes.includes(file.type)) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = new Image();
                img.onload = function () {
                    image.attr('src', e.target.result);
                    imageUrl.val(e.target.result);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
        console.log(imageUrl.val());
    });
}

initializeImageUploader('#apartment_form_imagerUrl', '.rr-apartment-image', 2);
initializeImageUploader('#equipment_form_iconUrl', '.rr-equipment-icon', 1);

document.addEventListener("DOMContentLoaded", function () {
    const imageInput = document.getElementById("apartment_form_images");
    const previewContainer = document.getElementById("apartment-images-preview");

    if(!$('#apartment_form_images')[0]) { return; }

    imageInput.addEventListener("change", function (event) {
        previewContainer.innerHTML = ""; // Vider la prévisualisation actuelle
        Array.from(event.target.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.createElement("img");
                img.src = e.target.result;
                img.classList.add("img-fluid", "rounded", "shadow-sm", "col-md-3", "m-1");
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".btn-remove-apartment-image").forEach(button => {
        
        button.addEventListener("click", function () {
           
            const imageContainer = this.closest(".image-container");
            const imageId = imageContainer.getAttribute("data-image-id");

            if (!imageId) return;

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
                    fetch(`/admin/apartment/images/delete/${imageId}`, {
                        method: "DELETE",
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            imageContainer.remove();
                        } else {
                            alert("Erreur : " + data.error);
                        }
                    })
                    .catch(error => {
                        alert("Une erreur s'est produite lors de la suppression de l'image.");
                    });
                }
            });

            

            
        });
    });
    
});

