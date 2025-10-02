<?php
/**
 * Booking form markup rendered via shortcode.
 */
?>
<div class="sp-booking-wrapper" data-confirmation="<?php echo esc_attr( $confirmation ); ?>">
    <?php if ( $confirmation ) : ?>
        <div class="sp-notice sp-notice-success">
            <strong><?php esc_html_e( 'Thank you! Your booking is confirmed.', 'sovereign-parking' ); ?></strong>
            <p><?php esc_html_e( 'A confirmation email has been sent to you. You can manage your booking from your customer portal.', 'sovereign-parking' ); ?></p>
        </div>
    <?php endif; ?>
    <form id="sp-booking-form" class="sp-booking-form" novalidate>
        <div class="sp-steps">
            <div class="sp-step" data-step="1">
                <h2><?php esc_html_e( 'Step 1: Select Cruise & Dates', 'sovereign-parking' ); ?></h2>
                <div class="sp-cruise-filter" id="sp-cruise-filter"></div>
                <div class="sp-field">
                    <label for="sp-cruise-select"><?php esc_html_e( 'Cruise', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                    <select id="sp-cruise-select" name="cruise_id" required></select>
                </div>
                <div class="sp-field-group">
                    <div class="sp-field">
                        <label for="sp-entry-date"><?php esc_html_e( 'Entry Date & Time', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                        <input type="datetime-local" id="sp-entry-date" name="entry" required />
                    </div>
                    <div class="sp-field">
                        <label for="sp-exit-date"><?php esc_html_e( 'Exit Date & Time', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                        <input type="datetime-local" id="sp-exit-date" name="exit" required />
                    </div>
                </div>
                <div class="sp-summary">
                    <p><?php esc_html_e( 'Total Days:', 'sovereign-parking' ); ?> <span id="sp-total-days">0</span></p>
                    <p><?php esc_html_e( 'Price:', 'sovereign-parking' ); ?> <span id="sp-total-price">$0.00</span></p>
                    <p class="sp-call-quote" id="sp-call-quote" hidden><?php esc_html_e( 'For stays of 30 days or more, please call for a personalised quote.', 'sovereign-parking' ); ?></p>
                </div>
                <div class="sp-actions">
                    <button type="button" class="sp-next button button-primary"><?php esc_html_e( 'Continue', 'sovereign-parking' ); ?></button>
                </div>
            </div>
            <div class="sp-step" data-step="2">
                <h2><?php esc_html_e( 'Step 2: Choose Shuttle', 'sovereign-parking' ); ?></h2>
                <div class="sp-note">
                    <strong><?php esc_html_e( 'Shuttle Transfers – Courtesy Note', 'sovereign-parking' ); ?></strong>
                    <p><?php esc_html_e( 'During peak times, we kindly suggest dropping passengers and luggage at the cruise terminal before parking. This courtesy step helps free up shuttle capacity, allowing more transfers to run smoothly and making additional time slots available for all guests.', 'sovereign-parking' ); ?></p>
                </div>
                <div class="sp-field">
                    <label for="sp-passengers"><?php esc_html_e( 'Passengers', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                    <input type="number" id="sp-passengers" name="passengers" min="1" max="11" value="2" required />
                </div>
                <div class="sp-field">
                    <label for="sp-shuttle-date"><?php esc_html_e( 'Shuttle Date', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                    <input type="date" id="sp-shuttle-date" name="shuttle_date" required />
                </div>
                <div class="sp-field">
                    <label for="sp-shuttle-slot"><?php esc_html_e( 'Shuttle Time Slot', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                    <select id="sp-shuttle-slot" name="shuttle_id" required></select>
                    <p class="sp-error" id="sp-slot-error" hidden><?php esc_html_e( 'No shuttle slots are available for the selected date. Please adjust passengers or time.', 'sovereign-parking' ); ?></p>
                </div>
                <div class="sp-note">
                    <strong><?php esc_html_e( 'Operational Disclaimer', 'sovereign-parking' ); ?></strong>
                    <p><?php esc_html_e( 'We do our best to get you to the cruise terminal within your selected time slot. However, we cannot control external factors such as traffic congestion or roadworks. Access to the terminal is via a single road in and out, so - while rare - delays may occur.', 'sovereign-parking' ); ?></p>
                </div>
                <div class="sp-actions">
                    <button type="button" class="sp-prev button"><?php esc_html_e( 'Back', 'sovereign-parking' ); ?></button>
                    <button type="button" class="sp-next button button-primary"><?php esc_html_e( 'Continue', 'sovereign-parking' ); ?></button>
                </div>
            </div>
            <div class="sp-step" data-step="3">
                <h2><?php esc_html_e( 'Step 3: Enter Details', 'sovereign-parking' ); ?></h2>
                <div class="sp-field">
                    <label for="sp-full-name"><?php esc_html_e( 'Full Name', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                    <input type="text" id="sp-full-name" name="full_name" required />
                </div>
                <div class="sp-field">
                    <label for="sp-email"><?php esc_html_e( 'Email', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                    <input type="email" id="sp-email" name="email" required />
                </div>
                <div class="sp-field">
                    <label for="sp-phone"><?php esc_html_e( 'Mobile', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                    <input type="tel" id="sp-phone" name="phone" required />
                </div>
                <div class="sp-field">
                    <label for="sp-vehicle"><?php esc_html_e( 'Vehicle Number Plate', 'sovereign-parking' ); ?> <span class="required">*</span></label>
                    <input type="text" id="sp-vehicle" name="vehicle" required />
                </div>
                <div class="sp-field" id="sp-credit-wrapper" hidden>
                    <label for="sp-credit-apply"><?php esc_html_e( 'Apply Credit', 'sovereign-parking' ); ?></label>
                    <input type="number" id="sp-credit-apply" name="credit" min="0" step="0.01" />
                    <p class="sp-credit-balance"></p>
                </div>
                <div class="sp-actions">
                    <button type="button" class="sp-prev button"><?php esc_html_e( 'Back', 'sovereign-parking' ); ?></button>
                    <button type="button" class="sp-next button button-primary"><?php esc_html_e( 'Continue', 'sovereign-parking' ); ?></button>
                </div>
            </div>
            <div class="sp-step" data-step="4">
                <h2><?php esc_html_e( 'Step 4: Review & Payment', 'sovereign-parking' ); ?></h2>
                <div class="sp-summary" id="sp-checkout-summary"></div>
                <fieldset class="sp-fieldset">
                    <legend><?php esc_html_e( 'Payment Method', 'sovereign-parking' ); ?></legend>
                    <label class="sp-radio"><input type="radio" name="payment_method" value="stripe" checked /> <?php esc_html_e( 'Stripe (Pay in Full)', 'sovereign-parking' ); ?></label>
                    <label class="sp-radio"><input type="radio" name="payment_method" value="paypal" /> <?php esc_html_e( 'PayPal (Pay in Full)', 'sovereign-parking' ); ?></label>
                    <label class="sp-radio"><input type="radio" name="payment_method" value="poa" /> <?php esc_html_e( 'Pay on Arrival ($10 holding deposit)', 'sovereign-parking' ); ?></label>
                    <p class="sp-poa-note" hidden><?php esc_html_e( 'A $10 holding deposit will be processed online. The remaining balance is payable on arrival.', 'sovereign-parking' ); ?></p>
                </fieldset>
                <div class="sp-actions">
                    <button type="button" class="sp-prev button"><?php esc_html_e( 'Back', 'sovereign-parking' ); ?></button>
                    <button type="submit" class="sp-submit button button-primary"><?php esc_html_e( 'Reserve & Pay', 'sovereign-parking' ); ?></button>
                </div>
                <div class="sp-processing" hidden>
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e( 'Processing your booking. Please wait…', 'sovereign-parking' ); ?></p>
                </div>
                <div class="sp-error sp-error-general" hidden></div>
            </div>
        </div>
    </form>
</div>
