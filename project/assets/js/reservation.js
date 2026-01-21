import toastr from 'toastr';
import bootstrap from '../externals/bootstrap/js/bootstrap.bundle.min.js';

let stripe;
let elementsRent;
let clientSecretRent = null;


function initStripe() {
    if (!window.stripePublicKey) {
        console.error('Stripe public key not found');
        return false;
    }
    stripe = Stripe(window.stripePublicKey);
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('preview-reservation-btn')) return;
    initStripe();
});

document.getElementById('preview-reservation-btn')?.addEventListener('click', async function (e) {
    e.preventDefault();

    if (!stripe && !initStripe()) {
        toastr.error('Erreur de configuration Stripe');
        return;
    }

    const startDate = document.getElementById('reservation_form_startDate').value;
    const endDate = document.getElementById('reservation_form_endDate').value;
    const priceElement = document.getElementById('apartment_price');
    const price = parseFloat(priceElement.dataset.price);

    if (!startDate || !endDate) {
        toastr.error("Veuillez choisir une date d'arrivée et une date de départ.");
        return;
    }

    document.getElementById('recap-start').textContent = startDate;
    document.getElementById('recap-end').textContent = endDate;

    const response = await fetch('/stripe/create-payment-intent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            apartment_id: window.apartmentId,
            start_date: startDate,
            end_date: endDate,
            price: price
        })
    });

    const data = await response.json();
    
    if (data.error) {
        toastr.error(data.error);
        return;
    }

    clientSecretRent = data.clientSecretRent;

    document.getElementById('recap-days').textContent = data.days;
    document.getElementById('recap-rent').textContent = data.rentAmount.toFixed(2);
    document.getElementById('recap-caution').textContent = data.cautionAmount.toFixed(2);
    document.getElementById('recap-caution-total').textContent = data.cautionWithFees.toFixed(2);
    document.getElementById('recap-fees').textContent = data.stripeFees.toFixed(2);

    const paymentElementContainer = document.getElementById('payment-element-rent');
    if (paymentElementContainer && !paymentElementContainer.hasChildNodes()) {
        elementsRent = stripe.elements({ clientSecret: clientSecretRent });
        const paymentElement = elementsRent.create('payment');
        paymentElement.mount('#payment-element-rent');
    }

    const modal = new bootstrap.Modal(document.getElementById('reservation-modal'));
    modal.show();
});

document.getElementById('checkout-button')?.addEventListener('click', async function() {
    if (!elementsRent) {
        toastr.error('Le formulaire de paiement n\'est pas prêt.');
        return;
    }

    this.disabled = true;
    this.textContent = 'Paiement en cours...';

    const { error } = await stripe.confirmPayment({
        elements: elementsRent,
        confirmParams: {
            return_url: window.location.origin + '/payment/success',
        }
    });

    if (error) {
        document.getElementById('payment-error').textContent = error.message;
        this.disabled = false;
        this.textContent = 'Payer la location';
    }
});