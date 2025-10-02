# Sovereign Parking Booking System

A custom WordPress plugin that powers the Sovereign Parking cruise-terminal booking experience. It provides a multi-step customer checkout, real-time shuttle capacity enforcement, payment integrations, customer self-service portal, and a streamlined operations dashboard.

## Key Features

- **Multi-step booking form** with cruise selection, automatic date prefills, pricing matrix, shuttle slot enforcement, and payment options (Stripe, PayPal, Pay on Arrival deposit).
- **Payment integrations** using Stripe Checkout and PayPal Orders. Pay-on-arrival bookings collect a configurable holding deposit.
- **Automated email confirmations** matching the Sovereign Parking copy once payments (or deposits) succeed.
- **Customer portal** that auto-creates accounts, lists bookings, and allows guests to update contact details, vehicle plates, or shuttle times.
- **Operations dashboard** including booking management filters, CSV export, shuttle slot maintenance, cruise management, and a calendar overview.
- **Customer credit ledger** to issue and redeem credits in lieu of refunds.

## Installation

1. Copy the `sovereign-parking` directory into your WordPress installation under `wp-content/plugins/`.
2. Activate **Sovereign Parking Booking System** from the WordPress admin Plugins page.
3. Configure payment credentials and email settings under **Sovereign Parking → Settings**.
4. Create a page and add the `[sovereign_parking_booking]` shortcode for the booking form.
5. Optionally add `[sovereign_parking_portal]` to a members-only page for the customer portal.

## Requirements

- WordPress 6.0 or newer.
- PHP 7.4 or newer.
- cURL support for outbound Stripe/PayPal API requests.

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[sovereign_parking_booking]` | Renders the customer booking flow. |
| `[sovereign_parking_portal]` | Shows the self-service customer portal (requires login). |

## Notes

- Shuttle capacity defaults to 11 passengers per slot but can be edited via the Shuttle Slots post type.
- Cruise schedules can be added individually or imported in bulk using the WordPress editor (CSV/XLSX import tooling can be added as needed).
- Confirmation emails respect the provided copy and are only sent after successful payments or POA deposit collection.

For further customisation or support, extend the service classes located in the `includes/` directory.
