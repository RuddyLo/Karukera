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
        
    });
}

initializeImageUploader('#apartment_form_imagerUrl', '.rr-apartment-image', 2);
initializeImageUploader('#equipment_form_iconUrl', '.rr-equipment-icon', 1);

document.addEventListener("DOMContentLoaded", function () {
    const imageInput      = document.getElementById("apartment-images-input");
    if (!imageInput) return;

    const previewContainer = document.getElementById("apartment-images-preview");
    const uploadStatus     = document.getElementById("upload-status");
    const allowedTypes     = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    let pendingCount = 0;

    imageInput.addEventListener("change", function (event) {
        const files = Array.from(event.target.files);
        imageInput.value = '';

        files.forEach(file => {
            if (!allowedTypes.includes(file.type)) {
                toastr.error(`${file.name} : format non supporté (PNG, JPG, WEBP)`);
                return;
            }

            // Preview immédiat côté client
            const card = createPreviewCard();
            previewContainer.appendChild(card);

            const reader = new FileReader();
            reader.onload = e => card.querySelector('img').src = e.target.result;
            reader.readAsDataURL(file);

            // Upload en arrière-plan
            uploadFile(file, card);
        });
    });

    async function uploadFile(file, card) {
        pendingCount++;
        updateStatus();

        const overlay = card.querySelector('.upload-overlay');
        overlay.style.display = 'flex';
        overlay.innerHTML = spinnerHTML();

        let errorMsg = null;

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch('/admin/apartment/images/upload-temp', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });

            if (!response.ok) {
                errorMsg = `Erreur HTTP ${response.status}`;
            } else {
                let data;
                try {
                    data = await response.json();
                } catch {
                    errorMsg = 'Réponse serveur invalide';
                }

                if (!errorMsg) {
                    if (data.success) {
                        overlay.style.display = 'none';
                        const input = document.createElement('input');
                        input.type  = 'hidden';
                        input.name  = 'temp_image_keys[]';
                        input.value = data.tempKey;
                        card.appendChild(input);
                    } else {
                        errorMsg = data.error || 'Upload échoué';
                    }
                }
            }
        } catch {
            errorMsg = 'Serveur inaccessible';
        }

        if (errorMsg) {
            showCardError(card, file, errorMsg);
        }

        pendingCount--;
        updateStatus();
    }

    function showCardError(card, file, message) {
        const overlay = card.querySelector('.upload-overlay');
        overlay.style.display = 'flex';
        overlay.innerHTML = `
            <div class="text-center px-2">
                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:1.3rem;"></i>
                <small class="d-block text-white mt-1" style="font-size:0.72rem;">${message}</small>
                <button type="button" class="btn btn-sm btn-light mt-1 py-0 btn-retry" style="font-size:0.75rem;">Réessayer</button>
            </div>
        `;
        overlay.querySelector('.btn-retry').addEventListener('click', () => uploadFile(file, card));
    }

    function spinnerHTML() {
        return `<div class="spinner-border text-light" role="status" style="width:1.4rem;height:1.4rem;"></div>`;
    }

    function updateStatus() {
        if (pendingCount > 0) {
            uploadStatus.style.display = 'block';
            uploadStatus.textContent = `Upload en cours… (${pendingCount} restant${pendingCount > 1 ? 's' : ''})`;
        } else {
            uploadStatus.style.display = 'none';
        }
    }

    function createPreviewCard() {
        const div = document.createElement('div');
        div.className = 'col-md-3 image-container position-relative m-1';
        div.innerHTML = `
            <div class="position-relative" style="height:130px;overflow:hidden;border-radius:6px;">
                <img src="" style="width:100%;height:130px;object-fit:cover;" class="rounded" alt="">
                <div class="upload-overlay position-absolute top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="background:rgba(0,0,0,0.45);border-radius:6px;display:none;"></div>
            </div>
            <button type="button" class="btn btn-danger btn-sm mt-1 btn-remove-new-image">
                <i class="bi bi-trash-fill"></i>
            </button>
        `;
        div.querySelector('.btn-remove-new-image').addEventListener('click', () => div.remove());
        return div;
    }
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
                    .catch(() => {
                        alert("Une erreur s'est produite lors de la suppression de l'image.");
                    });
                }
            });

            

            
        });
    });
    
});

