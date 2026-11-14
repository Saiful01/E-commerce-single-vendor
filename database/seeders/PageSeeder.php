<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * The pages every online shop needs. The text is a sensible starting point, written with your own
 * shop name, delivery charges and return days. Read each page, change what does not match how you
 * work, and have the legal pages checked by someone who knows the law before you go live.
 *
 *   php artisan db:seed --class=PageSeeder
 *
 * Safe to run again: a page that already exists (same address) is never overwritten.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $s       = SiteSetting::current();
        $shop    = $s->site_name ?: config('app.name');
        $days    = (int) config('shop.return_window_days', 7);
        $inside  = $s->formatMoney($s->delivery_charge_inside_dhaka ?? 0);
        $outside = $s->formatMoney($s->delivery_charge_outside_dhaka ?? 0);
        $free    = (float) $s->free_shipping_min > 0 ? "Orders over {$s->formatMoney($s->free_shipping_min)} get free delivery." : '';
        $phone   = $s->phone_number ?: 'our phone number';
        $email   = $s->primary_email ?: 'our email address';
        $returnDays = $days > 0 ? "{$days} days" : 'a reasonable time';

        $pages = [
            // ----------------------------------------------------------------
            [
                'slug' => 'about-us', 'title' => 'About Us', 'sort_order' => 1, 'is_system' => false,
                'meta_description' => "Learn who we are and why customers shop with {$shop}.",
                'content' => <<<HTML
<p>Welcome to <strong>{$shop}</strong>. We are an online shop that brings good products to your door at fair prices, with honest descriptions and real people to talk to when you need help.</p>
<h2>What we believe</h2>
<ul>
<li><strong>Genuine products.</strong> We sell what we describe, and we say so clearly when something is not exactly what you might expect.</li>
<li><strong>Fair prices.</strong> The price you see is the price you pay. Delivery is shown before you confirm your order.</li>
<li><strong>Fast, careful delivery.</strong> Every parcel is checked and packed before it leaves us.</li>
<li><strong>Help that answers.</strong> Call or message us any time during opening hours.</li>
</ul>
<h2>Why shop with us</h2>
<p>You can pay by cash on delivery, mobile wallet or card. If something is wrong with your order, our <a href="/pages/return-and-refund-policy">return and refund policy</a> makes it simple to put right.</p>
<h2>Talk to us</h2>
<p>Questions, ideas or feedback? Visit our <a href="/pages/contact-us">contact page</a>. We read every message.</p>
HTML,
            ],
            // ----------------------------------------------------------------
            [
                'slug' => 'terms-and-conditions', 'title' => 'Terms and Conditions', 'sort_order' => 2, 'is_system' => true,
                'meta_description' => "The terms for shopping at {$shop}.",
                'content' => <<<HTML
<p>By using this website and placing an order with <strong>{$shop}</strong> you agree to the terms below. Please read them. If you do not agree, please do not use the site.</p>
<h2>1. Using the site</h2>
<p>You must give correct information when you order or create an account, and keep your password private. You are responsible for what happens under your account.</p>
<h2>2. Orders</h2>
<p>When you place an order we send you an order number. An order is only final once we confirm it, usually by phone or message. We may refuse or cancel an order, for example when an item is out of stock, a price was shown wrongly, or we cannot reach you to confirm. If we cancel an order you have already paid for, we refund the full amount.</p>
<h2>3. Prices and payment</h2>
<p>All prices are in {$s->currency_code} and include the taxes that apply. We can change prices at any time, but never for an order you already placed. You can pay with the methods shown at checkout. Some methods carry a small fee, which is shown before you confirm.</p>
<h2>4. Delivery</h2>
<p>We deliver as described in our <a href="/pages/shipping-and-delivery-policy">shipping and delivery policy</a>. Delivery times are estimates, not promises. Please check your parcel when it arrives and tell us at once if it is damaged or wrong.</p>
<h2>5. Returns and refunds</h2>
<p>Returns are covered by our <a href="/pages/return-and-refund-policy">return and refund policy</a>, which is part of these terms.</p>
<h2>6. Product information</h2>
<p>We try to describe and photograph every product accurately. Colours can look a little different on different screens. If a product is not as described, you can return it under our return policy.</p>
<h2>7. Our content</h2>
<p>The text, pictures and logos on this site belong to {$shop} or the brands we sell. Please do not copy or reuse them without asking us first.</p>
<h2>8. Our responsibility</h2>
<p>We are responsible for the products we sell and the service we give. We are not responsible for delays or problems caused by things outside our control, such as natural events, strikes or failures of networks and payment services.</p>
<h2>9. Changes to these terms</h2>
<p>We may update these terms. The date at the top of the page shows when they last changed. Orders you placed earlier follow the terms that applied on that day.</p>
<h2>10. Contact</h2>
<p>For any question about these terms, call {$phone} or write to {$email}.</p>
HTML,
            ],
            // ----------------------------------------------------------------
            [
                'slug' => 'privacy-policy', 'title' => 'Privacy Policy', 'sort_order' => 3, 'is_system' => true,
                'meta_description' => "How {$shop} collects, uses and protects your personal information.",
                'content' => <<<HTML
<p>Your privacy matters to us. This page explains what information <strong>{$shop}</strong> collects, why, and what you can do about it.</p>
<h2>Information we collect</h2>
<ul>
<li><strong>Details you give us:</strong> your name, mobile number, email, delivery address and the items you order. If you create an account, your login details.</li>
<li><strong>Messages:</strong> what you write to us through the contact form or by phone.</li>
<li><strong>Technical data:</strong> things like your device type and the pages you visit, collected by cookies and analytics tools to keep the site working and make it better.</li>
</ul>
<p>We do not store your card number. Card and online payments are handled by our payment partner on their own secure page.</p>
<h2>How we use it</h2>
<ul>
<li>To process, deliver and support your orders.</li>
<li>To contact you about your order, for example to confirm it or tell you about a delivery.</li>
<li>To prevent fraud and keep the shop safe.</li>
<li>To understand how the site is used and improve it.</li>
</ul>
<h2>Who we share it with</h2>
<p>Only with people who need it to serve you: courier companies (name, phone and address), payment providers, and tools that help us run the shop. We never sell your personal information.</p>
<h2>Cookies</h2>
<p>We use cookies to keep your cart and sign-in working and to measure visits. You can block cookies in your browser, but parts of the site, such as the cart, may stop working.</p>
<h2>How long we keep it</h2>
<p>We keep order records for as long as the law and our accounting need. Other information is kept while you have an account or until you ask us to remove it.</p>
<h2>Your choices</h2>
<p>You can see and change your details in your account. You can ask us to correct or delete your information by writing to {$email}.</p>
<h2>Security</h2>
<p>We protect your information with passwords, encryption in transit and limited access. No system is perfectly safe, so please keep your password private.</p>
HTML,
            ],
            // ----------------------------------------------------------------
            [
                'slug' => 'return-and-refund-policy', 'title' => 'Return and Refund Policy', 'sort_order' => 4, 'is_system' => true,
                'meta_description' => "How to return an item to {$shop} and get your money back.",
                'content' => <<<HTML
<p>We want you to be happy with what you bought. If you are not, here is exactly how returns and refunds work at <strong>{$shop}</strong>.</p>
<h2>How long you have</h2>
<p>You can ask to return an item within <strong>{$returnDays} of delivery</strong>.</p>
<h2>What can be returned</h2>
<ul>
<li>Items that arrived damaged or defective.</li>
<li>Items that are different from what you ordered or from the description.</li>
<li>Items that do not fit or that you changed your mind about, as long as they are unused, in the original packaging and with all tags and accessories.</li>
</ul>
<h2>What cannot be returned</h2>
<ul>
<li>Items that were used, washed, altered or damaged after delivery.</li>
<li>Underwear, perfume, skin care and other personal-care items once opened.</li>
<li>Gift cards, and items marked "non-returnable" on the product page.</li>
</ul>
<h2>How to return something</h2>
<ol>
<li>Sign in and open <strong>My orders</strong>, choose the order, and press <strong>Return items</strong>. Pick the items and tell us why. If you ordered without an account, call {$phone}.</li>
<li>We review your request and tell you if it is approved.</li>
<li>Pack the item safely and hand it to us or our courier as we explain.</li>
<li>We check the item when it arrives back.</li>
</ol>
<h2>Refunds</h2>
<p>Once we receive and check the item, we refund its price, with any coupon discount you used shared fairly across your items. The refund goes back through the way you paid, or to your bKash or Nagad number if you paid on delivery. It normally reaches you within a few working days after we approve it.</p>
<h2>Delivery charges</h2>
<p>If the item was wrong, damaged or defective, we also refund the delivery charge and cover the return courier. For returns because you changed your mind, the original delivery charge is not refunded and the return courier cost is yours.</p>
<h2>Exchanges</h2>
<p>If you would like a different size or colour, return the item and place a new order. This is the fastest way to be sure the new one is kept for you.</p>
<h2>Need help?</h2>
<p>Call {$phone} or <a href="/pages/contact-us">write to us</a>. We will sort it out.</p>
HTML,
            ],
            // ----------------------------------------------------------------
            [
                'slug' => 'shipping-and-delivery-policy', 'title' => 'Shipping and Delivery Policy', 'sort_order' => 5, 'is_system' => false,
                'meta_description' => "Delivery areas, charges and times at {$shop}.",
                'content' => <<<HTML
<p>We deliver all over Bangladesh. Here is what to expect.</p>
<h2>Delivery charges</h2>
<ul>
<li><strong>Inside Dhaka:</strong> {$inside}</li>
<li><strong>Outside Dhaka:</strong> {$outside}</li>
</ul>
<p>{$free} The exact charge for your order is shown at checkout before you confirm.</p>
<h2>Delivery time</h2>
<ul>
<li><strong>Inside Dhaka:</strong> usually 1 to 3 days.</li>
<li><strong>Outside Dhaka:</strong> usually 3 to 5 days.</li>
</ul>
<p>Times start after we confirm your order. Public holidays, bad weather and remote areas can add a day or two.</p>
<h2>Order confirmation</h2>
<p>We call or message you on the number you gave to confirm every order before it is sent. Please keep your phone on.</p>
<h2>When your parcel arrives</h2>
<p>Please check the parcel in front of the delivery person. If it is damaged or wrong, tell us the same day so we can replace it quickly.</p>
<h2>Track your order</h2>
<p>Use <a href="/track-order">order tracking</a> with your order number and phone number. If you have an account, you can also follow it under <strong>My orders</strong>.</p>
HTML,
            ],
            // ----------------------------------------------------------------
            [
                'slug' => 'faq', 'title' => 'Frequently Asked Questions', 'template' => 'faq', 'sort_order' => 6, 'is_system' => false,
                'meta_description' => "Answers to the most common questions about ordering, payment, delivery and returns at {$shop}.",
                'content' => '<p>Quick answers to what customers ask us most. Cannot find yours? Call us or send a message from the contact page.</p>',
                'faq_items' => [
                    ['q' => 'How do I place an order?', 'a' => "Add the products you like to your cart, press Checkout, fill in your name, phone and delivery address, pick a payment method and press Place order. You do not need an account."],
                    ['q' => 'Which payment methods do you accept?', 'a' => "Cash on delivery, bKash and Nagad send money, bank transfer and online payment by card or mobile banking. The methods available for your order are shown at checkout."],
                    ['q' => 'How long does delivery take?', 'a' => "Usually 1 to 3 days inside Dhaka and 3 to 5 days outside Dhaka, counted from the time we confirm your order."],
                    ['q' => 'How much is delivery?', 'a' => "Inside Dhaka {$inside}, outside Dhaka {$outside}. {$free}"],
                    ['q' => 'How can I track my order?', 'a' => "Open Track order from the footer and enter your order number and phone number. If you have an account, go to My orders."],
                    ['q' => 'Can I cancel my order?', 'a' => "Yes, as long as we have not started processing it. Open the order under My orders and press Cancel order, or call us."],
                    ['q' => 'Can I pay for an order in parts?', 'a' => "Yes. Pay what you can now, and pay the rest later from the order page. We show the amount that is still due each time."],
                    ['q' => 'How do I return a product?', 'a' => "Go to My orders, open the order and press Return items. We explain every step on the Return and Refund Policy page."],
                    ['q' => 'When will I get my refund?', 'a' => "After we receive and check the returned item. It usually reaches you within a few working days of approval."],
                    ['q' => 'How do I contact you?', 'a' => "Call {$phone}, write to {$email}, or use the form on the contact page."],
                ],
            ],
            // ----------------------------------------------------------------
            [
                'slug' => 'contact-us', 'title' => 'Contact Us', 'template' => 'contact', 'sort_order' => 7, 'is_system' => false,
                'meta_description' => "Call, message or visit {$shop}. We are happy to help.",
                'content' => '<p>Questions about an order, a product or a return? Reach us any way you like below, or send a message and we will get back to you.</p>',
            ],
        ];

        $created = 0;
        foreach ($pages as $p) {
            $page = Page::firstOrCreate(['slug' => $p['slug']], $p + [
                'template' => 'standard', 'is_active' => true, 'show_in_footer' => true, 'show_in_header' => false,
            ]);
            $created += (int) $page->wasRecentlyCreated;
        }

        $this->command?->info("Pages added: {$created} (existing pages were left alone).");
        $this->command?->warn('Read every page and edit it to match how your shop really works. Have the Terms, Privacy and Return policies checked before launch.');
    }
}
