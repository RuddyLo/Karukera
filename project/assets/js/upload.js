import $ from 'jquery'
import toastr from 'toastr'
/**
     * 
     * @param {*} imageInputSelector 
     * @param {*} imageElementSelector 
     * @param {*} imageSizeLimit 
     * @param {*} allowedFileType 
     */
function initializeImageUploader(imageInputSelector, imageElementSelector, imageSizeLimit = 2, allowedFileType = '.png') {
    let imageUrl = $(imageInputSelector);
    let image = $(imageElementSelector);

    $(document).on('click', '.image-toggler', function () {
        imageUrl.click();
    });

    $(document).on('change', imageInputSelector, function () {

        const file = this.files[0];

        if (file.size > imageSizeLimit * 1024 * 1024) {
            toastr.error('La taille du fichier ne doit pas dépasser ' + (imageSizeLimit) + ' Mo.');
            return;
        }
        if (!file.type.match(allowedFileType + '*')) {
            toastr.error('Le fichier doit être au format ' + allowedFileType.toUpperCase() + '.');
            return;
        }
        if (file && file.type.match(allowedFileType + '*')) {
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

initializeImageUploader('#apartment_form_imagerUrl', '.rr-apartment-image', 2, '.png');

document.addEventListener("DOMContentLoaded", function () {
    const imageInput = document.getElementById("apartment_form_images");
    const previewContainer = document.getElementById("apartment-images-preview");
    console.log(previewContainer);

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
