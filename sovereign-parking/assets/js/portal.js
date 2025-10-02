(function ($) {
    'use strict';

    const portal = window.spPortalData || {};
    const $container = $('#sp-portal-bookings');

    function init() {
        if (!$container.length) {
            return;
        }
        renderBookings();
    }

    function renderBookings() {
        if (!portal.bookings || !portal.bookings.length) {
            $container.html('<p>' + esc('You have no upcoming bookings at this time.') + '</p>');
            return;
        }

        const cards = portal.bookings.map(renderCard);
        $container.html(cards.join(''));

        portal.bookings.forEach(booking => {
            const $form = $('#sp-portal-form-' + booking.id);
            $form.on('submit', function (event) {
                event.preventDefault();
                submitUpdate(booking.id);
            });

            $form.find('.sp-portal-shuttle-date').on('change', function () {
                loadSlots(booking.id);
            });

            loadSlots(booking.id);
        });
    }

    function renderCard(booking) {
        const entry = booking.entry ? formatDate(booking.entry) : '';
        const exit = booking.exit ? formatDate(booking.exit) : '';
        const shuttleDate = booking.shuttle_date || '';
        const status = booking.status ? booking.status.replace(/_/g, ' ') : '';

        return `
            <div class="sp-portal-card" id="sp-portal-card-${booking.id}">
                <header>
                    <h3>${esc('Booking #')}${booking.number}</h3>
                    <span class="sp-status">${esc(status)}</span>
                </header>
                <p class="sp-cruise">${esc(booking.cruise)}</p>
                <p class="sp-dates">${esc('Entry: ')}${esc(entry)}<br>${esc('Exit: ')}${esc(exit)}</p>
                <form id="sp-portal-form-${booking.id}" class="sp-portal-form">
                    <div class="sp-field">
                        <label>${esc('Vehicle Number Plate')}</label>
                        <input type="text" name="vehicle" value="${booking.vehicle ? escAttr(booking.vehicle) : ''}" />
                    </div>
                    <div class="sp-field">
                        <label>${esc('Mobile Number')}</label>
                        <input type="text" name="phone" value="${booking.phone ? escAttr(booking.phone) : ''}" />
                    </div>
                    <div class="sp-field">
                        <label>${esc('Shuttle Date')}</label>
                        <input type="date" class="sp-portal-shuttle-date" name="shuttle_date" value="${shuttleDate}" />
                    </div>
                    <div class="sp-field">
                        <label>${esc('Shuttle Time Slot')}</label>
                        <select name="shuttle_id" class="sp-portal-shuttle"></select>
                        <p class="sp-slot-error" hidden>${esc('Selected shuttle time is full. Please choose another option.')}</p>
                    </div>
                    <div class="sp-actions">
                        <button type="submit" class="button button-primary">${esc('Save Changes')}</button>
                        <span class="sp-message" hidden></span>
                    </div>
                </form>
            </div>
        `;
    }

    function loadSlots(bookingId) {
        const booking = portal.bookings.find(b => b.id === bookingId);
        if (!booking) {
            return;
        }
        const $card = $('#sp-portal-card-' + bookingId);
        const $form = $('#sp-portal-form-' + bookingId);
        const $select = $form.find('.sp-portal-shuttle');
        const date = $form.find('.sp-portal-shuttle-date').val();
        if (!date) {
            $select.empty();
            return;
        }

        const payload = {
            action: 'sp_get_slots',
            nonce: portal.nonce,
            date: date,
            passengers: booking.passengers,
            booking_id: bookingId
        };
        $.post(portal.ajaxUrl, payload, function (response) {
            $select.empty();
            if (!response.success || !response.data.length) {
                $form.find('.sp-slot-error').prop('hidden', false);
                return;
            }
            $form.find('.sp-slot-error').prop('hidden', true);
            response.data.forEach(slot => {
                const option = $('<option></option>').attr('value', slot.id).text(slot.label + ' (' + slot.remaining + ')');
                if (booking.shuttle_id && parseInt(booking.shuttle_id, 10) === parseInt(slot.id, 10)) {
                    option.prop('selected', true);
                }
                $select.append(option);
            });
        });
    }

    function submitUpdate(bookingId) {
        const $form = $('#sp-portal-form-' + bookingId);
        const $message = $form.find('.sp-message');
        const data = {
            action: 'sp_update_booking',
            nonce: portal.nonce,
            booking_id: bookingId,
            vehicle: $form.find('input[name="vehicle"]').val(),
            phone: $form.find('input[name="phone"]').val(),
            shuttle_id: $form.find('select[name="shuttle_id"]').val(),
            shuttle_date: $form.find('input[name="shuttle_date"]').val(),
        };

        $message.prop('hidden', true).removeClass('success error');
        $.post(portal.ajaxUrl, data, function (response) {
            if (!response.success) {
                $message.text(response.data && response.data.message ? response.data.message : portal.strings.updateError)
                    .addClass('error').prop('hidden', false);
                return;
            }
            const booking = portal.bookings.find(b => b.id === bookingId);
            if (booking) {
                booking.vehicle = data.vehicle;
                booking.phone = data.phone;
                booking.shuttle_id = data.shuttle_id;
                booking.shuttle_date = data.shuttle_date;
            }
            $message.text(response.data && response.data.message ? response.data.message : portal.strings.updateSuccess)
                .addClass('success').prop('hidden', false);
        }).fail(function () {
            $message.text(portal.strings.updateError).addClass('error').prop('hidden', false);
        });
    }

    function formatDate(value) {
        const date = new Date(value);
        if (isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleString();
    }

    function esc(text) {
        return $('<div>').text(text || '').html();
    }

    function escAttr(text) {
        return $('<div>').text(text || '').html();
    }

    $(document).ready(init);
})(jQuery);
