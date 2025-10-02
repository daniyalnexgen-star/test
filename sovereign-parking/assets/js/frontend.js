(function ($) {
    'use strict';

    const data = window.spBookingData || {};
    const state = {
        cruise: null,
        entry: null,
        exit: null,
        days: 0,
        price: 0,
        passengers: 2,
        shuttleDate: null,
        shuttleId: null,
        creditBalance: 0,
        creditApplied: 0,
    };

    const $form = $('#sp-booking-form');
    const $steps = $form.find('.sp-step');
    let currentStep = 0;

    function init() {
        if (!$form.length) {
            return;
        }

        populateCruises();
        bindEvents();
        updateStep();
    }

    function bindEvents() {
        $form.on('click', '.sp-next', function () {
            if (validateStep(currentStep)) {
                currentStep++;
                updateStep();
            }
        });

        $form.on('click', '.sp-prev', function () {
            currentStep = Math.max(0, currentStep - 1);
            updateStep();
        });

        $('#sp-cruise-select').on('change', handleCruiseChange);
        $('#sp-entry-date, #sp-exit-date').on('change', handleDateChange);
        $('#sp-passengers, #sp-shuttle-date').on('change', refreshSlots);
        $('#sp-shuttle-slot').on('change', function () {
            state.shuttleId = $(this).val();
        });
        $('#sp-email').on('blur', fetchCreditBalance);
        $('input[name="payment_method"]').on('change', togglePaymentNote);

        $form.on('submit', handleSubmit);

        togglePaymentNote();
    }

    function updateStep() {
        $steps.removeClass('active').eq(currentStep).addClass('active');
        $('html, body').animate({ scrollTop: $form.offset().top - 80 }, 200);
        if (currentStep === 1) {
            // Prefill shuttle date with entry date if available.
            const entry = $('#sp-entry-date').val();
            if (entry && !$('#sp-shuttle-date').val()) {
                $('#sp-shuttle-date').val(entry.split('T')[0]);
                state.shuttleDate = $('#sp-shuttle-date').val();
                refreshSlots();
            }
        }
        if (currentStep === 3) {
            renderSummary();
        }
    }

    function populateCruises() {
        const $select = $('#sp-cruise-select');
        const $filter = $('#sp-cruise-filter');
        $select.empty().append('<option value="">' + sp_i18n('Select a cruise') + '</option>');

        const lines = new Set();
        data.cruises.forEach(cruise => {
            lines.add(cruise.line || '');
            $select.append('<option value="' + cruise.id + '" data-departure="' + cruise.departure + '" data-return="' + cruise.return + '">' + cruise.title + '</option>');
        });

        $filter.empty();
        const $all = $('<button type="button" class="sp-chip active">' + sp_i18n('All Lines') + '</button>');
        $all.on('click', function () {
            $filter.find('.sp-chip').removeClass('active');
            $(this).addClass('active');
            $select.find('option').show();
        });
        $filter.append($all);

        lines.forEach(line => {
            if (!line) {
                return;
            }
            const $chip = $('<button type="button" class="sp-chip">' + line + '</button>');
            $chip.on('click', function () {
                $filter.find('.sp-chip').removeClass('active');
                $(this).addClass('active');
                $select.find('option').each(function () {
                    const optionLine = data.cruises.find(c => String(c.id) === $(this).val());
                    if (!optionLine) {
                        return;
                    }
                    if (optionLine.line === line) {
                        $(this).show();
                    } else if ($(this).val()) {
                        $(this).hide();
                    }
                });
                $select.val('');
            });
            $filter.append($chip);
        });
    }

    function handleCruiseChange() {
        const cruiseId = $(this).val();
        state.cruise = cruiseId ? data.cruises.find(c => String(c.id) === cruiseId) : null;

        if (state.cruise) {
            if (state.cruise.departure) {
                $('#sp-entry-date').val(toLocalInput(state.cruise.departure));
            }
            if (state.cruise.return) {
                $('#sp-exit-date').val(toLocalInput(state.cruise.return));
            }
        }
        handleDateChange();
    }

    function handleDateChange() {
        const entry = $('#sp-entry-date').val();
        const exit = $('#sp-exit-date').val();
        state.entry = entry ? new Date(entry) : null;
        state.exit = exit ? new Date(exit) : null;

        if (state.entry && state.exit && state.exit >= state.entry) {
            const diff = state.exit.getTime() - state.entry.getTime();
            state.days = Math.ceil(diff / (1000 * 60 * 60 * 24));
            if (state.days < 1) {
                state.days = 1;
            }
        } else {
            state.days = 0;
        }
        $('#sp-total-days').text(state.days);
        updatePrice();
    }

    function updatePrice() {
        const map = {};
        if (Array.isArray(data.pricing)) {
            data.pricing.forEach(row => {
                map[row.days] = row.price;
            });
        }
        let price = map[state.days] || 0;
        const callQuote = state.days >= 30 || price === 0;
        $('#sp-call-quote').prop('hidden', !callQuote);
        if (callQuote) {
            $('#sp-total-price').text('$0.00');
            state.price = 0;
            return;
        }
        state.price = price;
        $('#sp-total-price').text(formatCurrency(price));
    }

    function refreshSlots() {
        state.passengers = parseInt($('#sp-passengers').val(), 10) || 1;
        state.shuttleDate = $('#sp-shuttle-date').val();
        if (!state.shuttleDate || !state.passengers) {
            return;
        }

        const payload = {
            action: 'sp_get_slots',
            nonce: data.nonce,
            passengers: state.passengers,
            date: state.shuttleDate,
            cruise: state.cruise ? state.cruise.id : '',
        };

        const $select = $('#sp-shuttle-slot');
        $select.prop('disabled', true);
        $.post(data.ajaxUrl, payload, function (response) {
            $select.empty();
            if (!response.success || !response.data.length) {
                $('#sp-slot-error').prop('hidden', false);
                $select.prop('disabled', false);
                return;
            }
            $('#sp-slot-error').prop('hidden', true);
            response.data.forEach(slot => {
                $select.append('<option value="' + slot.id + '">' + slot.label + ' (' + slot.remaining + ')</option>');
            });
            $select.prop('disabled', false);
            state.shuttleId = $select.val();
        });
    }

    function fetchCreditBalance() {
        const email = $('#sp-email').val();
        if (!email) {
            return;
        }
        $.post(data.ajaxUrl, {
            action: 'sp_get_credit',
            nonce: data.nonce,
            email: email
        }, function (response) {
            if (!response.success) {
                $('#sp-credit-wrapper').prop('hidden', true);
                return;
            }
            state.creditBalance = parseFloat(response.data.balance) || 0;
            if (state.creditBalance > 0) {
                $('#sp-credit-wrapper').prop('hidden', false);
                $('#sp-credit-apply').attr('max', state.creditBalance.toFixed(2));
                $('.sp-credit-balance').text(sp_i18n('Available credit: ') + formatCurrency(state.creditBalance));
            } else {
                $('#sp-credit-wrapper').prop('hidden', true);
            }
        });
    }

    function togglePaymentNote() {
        const method = $('input[name="payment_method"]:checked').val();
        $('.sp-poa-note').prop('hidden', method !== 'poa');
    }

    function renderSummary() {
        const summary = $('#sp-checkout-summary');
        const entry = $('#sp-entry-date').val();
        const exit = $('#sp-exit-date').val();
        const shuttleText = $('#sp-shuttle-slot option:selected').text();
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        const passengers = state.passengers;
        const credit = parseFloat($('#sp-credit-apply').val()) || 0;
        state.creditApplied = Math.min(credit, state.creditBalance, state.price);

        let totalDue = state.price - state.creditApplied;
        let description = '';
        if (paymentMethod === 'poa') {
            totalDue = data.depositAmount || 10;
            description = sp_i18n('Deposit payable now: ') + formatCurrency(totalDue);
        } else {
            description = sp_i18n('Amount payable now: ') + formatCurrency(totalDue);
        }

        const html = `
            <h3>${sp_i18n('Booking Summary')}</h3>
            <ul>
                <li><strong>${sp_i18n('Cruise')}:</strong> ${$('#sp-cruise-select option:selected').text()}</li>
                <li><strong>${sp_i18n('Entry')}:</strong> ${formatDisplayDate(entry)}</li>
                <li><strong>${sp_i18n('Exit')}:</strong> ${formatDisplayDate(exit)}</li>
                <li><strong>${sp_i18n('Passengers')}:</strong> ${passengers}</li>
                <li><strong>${sp_i18n('Shuttle Slot')}:</strong> ${shuttleText}</li>
                <li><strong>${sp_i18n('Parking Price')}:</strong> ${formatCurrency(state.price)}</li>
                <li><strong>${sp_i18n('Credit Applied')}:</strong> ${formatCurrency(state.creditApplied)}</li>
            </ul>
            <p class="sp-total-due">${description}</p>
        `;
        summary.html(html);
    }

    function validateStep(step) {
        let valid = true;
        const $current = $steps.eq(step);
        $current.find('[required]').each(function () {
            if (!$(this).val()) {
                $(this).addClass('sp-invalid');
                valid = false;
            } else {
                $(this).removeClass('sp-invalid');
            }
        });

        if (step === 0 && (state.days === 0 || state.price === 0)) {
            valid = false;
            $('#sp-total-price').addClass('sp-invalid');
        }

        if (step === 1 && !state.shuttleId) {
            $('#sp-slot-error').prop('hidden', false);
            valid = false;
        }

        return valid;
    }

    function handleSubmit(event) {
        event.preventDefault();
        if (!validateStep(currentStep)) {
            return;
        }

        $('.sp-error-general').prop('hidden', true);
        $('.sp-processing').prop('hidden', false);
        $('.sp-submit').prop('disabled', true);

        const payload = {
            action: 'sp_create_booking',
            nonce: data.nonce,
            cruise_id: $('#sp-cruise-select').val(),
            entry: $('#sp-entry-date').val(),
            exit: $('#sp-exit-date').val(),
            passengers: state.passengers,
            shuttle_date: state.shuttleDate,
            shuttle_id: state.shuttleId,
            full_name: $('#sp-full-name').val(),
            email: $('#sp-email').val(),
            phone: $('#sp-phone').val(),
            vehicle: $('#sp-vehicle').val(),
            payment_method: $('input[name="payment_method"]:checked').val(),
            credit: state.creditApplied,
            source_url: window.location.href,
        };

        $.post(data.ajaxUrl, payload).done(function (response) {
            if (!response || !response.success) {
                const message = response && response.data && response.data.message ? response.data.message : sp_i18n('Unable to process booking. Please check your details and try again.');
                $('.sp-error-general').text(message).prop('hidden', false);
                return;
            }

            if (response.data.redirect_url) {
                window.location.href = response.data.redirect_url;
                return;
            }

            if (response.data.message) {
                $('.sp-booking-wrapper').html('<div class="sp-notice sp-notice-success"><h3>' + sp_i18n('Booking Reserved') + '</h3><p>' + response.data.message + '</p></div>');
            } else {
                $('.sp-booking-wrapper').html('<div class="sp-notice sp-notice-success"><h3>' + sp_i18n('Booking Completed') + '</h3><p>' + sp_i18n('Thank you for booking with Sovereign Parking.') + '</p></div>');
            }
        }).fail(function () {
            $('.sp-error-general').text(sp_i18n('An unexpected error occurred. Please try again.')).prop('hidden', false);
        }).always(function () {
            $('.sp-processing').prop('hidden', true);
            $('.sp-submit').prop('disabled', false);
        });
    }

    function toLocalInput(dateString) {
        const date = new Date(dateString + 'Z');
        if (isNaN(date.getTime())) {
            return '';
        }
        const offsetDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
        return offsetDate.toISOString().slice(0, 16);
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD' }).format(amount || 0);
    }

    function formatDisplayDate(value) {
        if (!value) {
            return '';
        }
        const date = new Date(value);
        if (isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString();
    }

    function sp_i18n(text) {
        return text;
    }

    $(document).ready(init);
})(jQuery);
