import toastr from 'toastr';
import bootstrap from '../externals/bootstrap/js/bootstrap.bundle.min.js';

let stripe;
let elements;
let clientSecret = null;
let reservationData = null;

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

    if (!startDate || !endDate) {
        toastr.error('Veuillez choisir une date de début et une date de fin.');
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
            end_date: endDate
        })
    });

    const data = await response.json();
    
    if (data.error) {
        toastr.error(data.error);
        return;
    }

    clientSecret = data.clientSecret;
    reservationData = data;

    document.getElementById('recap-days').textContent = data.days;
    document.getElementById('recap-rent').textContent = data.rentAmount.toFixed(2);
    document.getElementById('recap-caution').textContent = data.cautionAmount.toFixed(2);
    document.getElementById('recap-total').textContent = data.totalAmount.toFixed(2);

    elements = stripe.elements({ clientSecret });
    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    const modal = new bootstrap.Modal(document.getElementById('reservation-modal'));
    modal.show();
});

document.getElementById('checkout-button')?.addEventListener('click', async function() {
    if (!elements) {
        toastr.error('Le formulaire de paiement n\'est pas prêt.');
        return;
    }

    this.disabled = true;
    this.textContent = 'Traitement...';

    const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: window.location.origin + '/payment/success',
        }
    });

    if (error) {
        document.getElementById('payment-error').textContent = error.message;
        this.disabled = false;
        this.textContent = 'Payer maintenant';
    }
});