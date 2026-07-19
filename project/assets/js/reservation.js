import toastr from 'toastr';
import bootstrap from '../externals/bootstrap/js/bootstrap.bundle.min.js';

let stripe;
let elementsRent;
let clientSecretRent = null;
let appliedCouponCode = null;

function getCurrencySymbol() {
    return (window.selectedCurrency || 'eur') === 'brl' ? 'R$' : '€';
}

function updateCurrencySymbols() {
    const symbol = getCurrencySymbol();
    document.querySelectorAll('.currency-symbol').forEach(el => { el.textContent = symbol; });
}

function initStripe() {
    if (!window.stripePublicKey) {
        console.error('Stripe public key not found');
        return false;
    }
    stripe = Stripe(window.stripePublicKey);
    return true;
}

function showCurrencySpinner(loading) {
    const spinner = document.getElementById('currency-spinner');
    const rateEl = document.getElementById('exchange-rate-display');
    const btns = document.querySelectorAll('#currency-selector button');
    if (spinner) spinner.style.display = loading ? 'block' : 'none';
    if (rateEl && loading) rateEl.style.display = 'none';
    btns.forEach(b => { b.disabled = loading; });
}

function showExchangeRate(currency, rate) {
    const rateEl = document.getElementById('exchange-rate-display');
    if (!rateEl) return;
    if (currency === 'brl' && rate && rate !== 1) {
        rateEl.textContent = `Taux appliqué : 1 EUR = ${rate.toFixed(4)} R$ (inclus un frais de conversion de 2%)`;
        rateEl.style.display = 'block';
    } else {
        rateEl.style.display = 'none';
    }
}

function renderPriceBreakdown(groupedBreakdown) {
    const recapPriceEl = document.getElementById('apartment_price');
    if (!recapPriceEl || !groupedBreakdown || !groupedBreakdown.length) return;

    const symbol = getCurrencySymbol();
    const average = groupedBreakdown.reduce((sum, g) => {
        const nights = (new Date(g.endDate) - new Date(g.startDate)) / 86400000 + 1;
        return sum + g.price * nights;
    }, 0);
    const totalNights = groupedBreakdown.reduce((sum, g) => sum + ((new Date(g.endDate) - new Date(g.startDate)) / 86400000 + 1), 0);
    recapPriceEl.dataset.price = (average / totalNights).toFixed(2);

    if (groupedBreakdown.length === 1) {
        recapPriceEl.innerHTML = `<span id="apartment_price_value">${groupedBreakdown[0].price.toFixed(2)}</span> <span class="currency-symbol">${symbol}</span> <small class="text-muted">(TVA incluses)</small>`;
        return;
    }

    const formatLabel = d => new Date(d + 'T00:00:00').toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
    const detail = groupedBreakdown.map(g => {
        const range = g.startDate === g.endDate ? formatLabel(g.startDate) : `${formatLabel(g.startDate)} → ${formatLabel(g.endDate)}`;
        return `<span class="d-block">${range} : ${g.price.toFixed(2)} ${symbol}</span>`;
    }).join('');
    recapPriceEl.innerHTML = `<small>${detail}</small>`;
}

async function fetchAndMountPaymentIntent(startDate, endDate, price) {
    clientSecretRent = null;
    const container = document.getElementById('payment-element-rent');
    if (container) container.innerHTML = '';
    elementsRent = null;

    showCurrencySpinner(true);

    let data;
    try {
        const response = await fetch('/stripe/create-payment-intent', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                apartment_id: window.apartmentId,
                start_date: startDate,
                end_date: endDate,
                price: price,
                currency: window.selectedCurrency || 'eur',
                coupon_code: appliedCouponCode
            })
        });
        data = await response.json();
    } catch (e) {
        showCurrencySpinner(false);
        toastr.error('Erreur réseau. Veuillez réessayer.');
        return false;
    }

    showCurrencySpinner(false);

    if (data.error) {
        toastr.error(data.error);
        return false;
    }

    clientSecretRent = data.clientSecretRent;
    if (data.currency) window.selectedCurrency = data.currency;
    updateCurrencySymbols();
    showExchangeRate(data.currency, data.exchangeRate);

    renderPriceBreakdown(data.groupedBreakdown);

    const discountRow = document.getElementById('coupon-discount-row');
    const couponMessageEl = document.getElementById('coupon-message');
    const appliedBanner = document.getElementById('coupon-applied-banner');
    if (data.discountAmount && data.discountAmount > 0) {
        document.getElementById('recap-discount').textContent = data.discountAmount.toFixed(2);
        document.getElementById('coupon-applied-code').textContent = data.couponCode || '';
        if (discountRow) discountRow.style.display = '';
        if (couponMessageEl) { couponMessageEl.textContent = ''; }

        if (appliedBanner) {
            document.getElementById('coupon-applied-code-2').textContent = data.couponCode || '';
            const rateBadge = document.getElementById('coupon-rate-badge');
            if (rateBadge) {
                rateBadge.textContent = data.couponType === 'percentage'
                    ? `-${data.couponValue}%`
                    : `-${data.discountAmount.toFixed(2)} ${getCurrencySymbol()}`;
            }
            appliedBanner.style.display = 'flex';
        }
    } else {
        if (discountRow) discountRow.style.display = 'none';
        if (appliedBanner) appliedBanner.style.display = 'none';
    }

    document.getElementById('recap-days').textContent = data.days;
    document.getElementById('recap-rent').textContent = data.rentAmount.toFixed(2);
    document.getElementById('recap-caution').textContent = data.cautionAmount.toFixed(2);
    document.getElementById('recap-caution-total').textContent = data.cautionWithFees.toFixed(2);
    document.getElementById('recap-fees').textContent = data.stripeFees.toFixed(2);
    const refundEl = document.getElementById('recap-caution-refund');
    if (refundEl) refundEl.textContent = data.cautionAmount.toFixed(2);

    const grandTotal = data.rentAmount + data.cautionWithFees;
    const totalSejourEl = document.getElementById('recap-total-sejour');
    if (totalSejourEl) totalSejourEl.textContent = data.rentAmount.toFixed(2);
    const totalCautionEl = document.getElementById('recap-total-caution');
    if (totalCautionEl) totalCautionEl.textContent = data.cautionWithFees.toFixed(2);
    const grandTotalEl = document.getElementById('recap-grand-total');
    if (grandTotalEl) grandTotalEl.textContent = grandTotal.toFixed(2);

    if (container) {
        elementsRent = stripe.elements({ clientSecret: clientSecretRent, locale: window.appLocale || 'fr' });
        const paymentElement = elementsRent.create('payment');
        paymentElement.mount('#payment-element-rent');
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('preview-reservation-btn')) return;
    initStripe();

    document.querySelectorAll('#currency-selector [data-currency]').forEach(btn => {
        btn.addEventListener('click', async function() {
            document.querySelectorAll('#currency-selector [data-currency]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const newCurrency = this.dataset.currency;
            if (window.selectedCurrency === newCurrency) return;

            window.selectedCurrency = newCurrency;

            const startDate = document.getElementById('reservation_form_startDate').value;
            const endDate = document.getElementById('reservation_form_endDate').value;
            const price = parseFloat(document.getElementById('apartment_price').dataset.price);

            const checkoutBtn = document.getElementById('checkout-button');
            if (checkoutBtn) checkoutBtn.disabled = true;

            await fetchAndMountPaymentIntent(startDate, endDate, price);

            if (checkoutBtn) { checkoutBtn.disabled = false; checkoutBtn.textContent = 'Payer le séjour →'; }
        });
    });
});

document.getElementById('preview-reservation-btn')?.addEventListener('click', async function(e) {
    e.preventDefault();

    if (!stripe && !initStripe()) {
        toastr.error('Erreur de configuration Stripe');
        return;
    }

    const cgvEl = document.getElementById('accept-cgv');
    const privacyEl = document.getElementById('accept-privacy');
    if ((cgvEl && !cgvEl.checked) || (privacyEl && !privacyEl.checked)) {
        toastr.warning('Veuillez accepter les CGV et la Politique de Confidentialité pour continuer.');
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

    if (window.isUserAdmin) {
        document.getElementById('recap-days').textContent = '∞';
        document.getElementById('recap-rent').textContent = '—';
        document.getElementById('recap-caution').textContent = '—';
        document.getElementById('recap-caution-total').textContent = '—';
        document.getElementById('recap-fees').textContent = '—';
    } else {
        const ok = await fetchAndMountPaymentIntent(startDate, endDate, price);
        if (!ok) return;
    }

    const modal = new bootstrap.Modal(document.getElementById('reservation-modal'));
    modal.show();
});

document.getElementById('coupon-apply-btn')?.addEventListener('click', async function() {
    const input = document.getElementById('coupon-code-input');
    const code = (input.value || '').trim().toUpperCase();
    const messageEl = document.getElementById('coupon-message');
    if (!code) return;

    const startDate = document.getElementById('reservation_form_startDate').value;
    const endDate = document.getElementById('reservation_form_endDate').value;
    const price = parseFloat(document.getElementById('apartment_price').dataset.price);

    const previousCoupon = appliedCouponCode;
    appliedCouponCode = code;

    this.disabled = true;
    const ok = await fetchAndMountPaymentIntent(startDate, endDate, price);

    if (!ok) {
        appliedCouponCode = previousCoupon;
        if (messageEl) { messageEl.textContent = ''; }
        await fetchAndMountPaymentIntent(startDate, endDate, price);
    }

    this.disabled = false;
});

document.getElementById('checkout-button')?.addEventListener('click', async function() {
    const startDate = document.getElementById('reservation_form_startDate').value;
    const endDate = document.getElementById('reservation_form_endDate').value;

    if (window.isUserAdmin) {
        this.disabled = true;
        this.textContent = 'Création en cours...';

        try {
            const response = await fetch('/stripe/create-admin-reservation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    apartment_id: window.apartmentId,
                    start_date: startDate,
                    end_date: endDate
                })
            });

            const data = await response.json();

            if (data.success) {
                toastr.success(data.message);
                document.getElementById('reservation-modal').addEventListener('hidden.bs.modal', function() {
                    window.location.reload();
                }, { once: true });
                bootstrap.Modal.getInstance(document.getElementById('reservation-modal')).hide();
            } else {
                toastr.error(data.error || 'Erreur lors de la création');
                this.disabled = false;
                this.textContent = 'Créer la réservation →';
            }
        } catch (error) {
            toastr.error('Erreur serveur: ' + error.message);
            this.disabled = false;
            this.textContent = 'Créer la réservation →';
        }
    } else {
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
    }
});
