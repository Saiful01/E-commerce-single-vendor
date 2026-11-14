<?php

return [
    // Customers can request a return this many days after delivery. 0 = no limit.
    'return_window_days' => 7,

    // Unpaid online-payment orders are cancelled after this many hours (php artisan orders:expire-online)
    'online_payment_hold_hours' => 3,

    // Only for testing on your own PC. bKash and Nagad refuse a local address such as http://ecommerce-app.test,
    // so set this in .env to a public address (a tunnel) or to http://127.0.0.1:8000 and leave it empty on the live site.
    'payment_callback_base' => env('PAYMENT_CALLBACK_BASE'),

    // Steadfast
    'steadfast' => [
        // Move the order to "Shipped" when the parcel is booked from the order page
        'mark_shipped_on_booking' => true,
        // When Steadfast reports a parcel as cancelled (the customer did not take it), cancel the order too.
        // That puts the items back in stock, so only turn it on if you are happy with that. Off = a note is added and you decide.
        'cancel_order_when_cancelled' => false,
    ],

    'return_reasons' => [
        'wrong_item'       => 'Received the wrong item',
        'damaged'          => 'Damaged or defective',
        'not_as_described' => 'Not as described',
        'size_issue'       => 'Size or fit problem',
        'changed_mind'     => 'Changed my mind',
        'other'            => 'Other',
    ],
];
