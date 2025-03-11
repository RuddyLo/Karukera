import $ from 'jquery'
/**
     * 
     * @param {*} imageInputSelector 
     * @param {*} imageElementSelector 
     * @param {*} imageSizeLimit 
     * @param {*} allowedFileType 
     */
function initializeImageUploader(imageInputSelector, imageElementSelector, imageSizeLimit = 2 , allowedFileType = '.png') {
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
    
initializeImageUploader('#apartment_form_imagerUrl', '.rr-apartment-image', 2 , '.png');