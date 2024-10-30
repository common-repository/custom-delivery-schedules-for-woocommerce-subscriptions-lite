=== Custom Delivery Schedules for WooCommerce Subscriptions - Lite ===
Contributors: flycart
Donate link: https://flycart.org/
Tags: woocommerce, subscriptions, memberships, order delivery schedules, prepaid subscriptions, upfront payment, one time payment for subscriptions
Requires PHP: 5.6
Requires at least: 4.4.1
Tested up to: 5.3
WC tested up to: 3.8
Stable tag: 1.0.2
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Separate your billing and shipping cycles for your WooCommerce Subscription products. Let your customers pay annually, while you can ship monthly or any custom shipping cycle.

== Description ==

Running a Subscription box business and want to separate billing and shipping cycles? Custom Delivery Schedules plugin can help you manage your billing and shipping cycles differently.
Most subscription box businesses now face a huge difficulty as their billing cycle is different than their shipping cycle.

The plugin allows you

* Create a shipping cycle that is different from the billing cycle (Example: Customer pays yearly, while you ship monthly )
* Generate an order / shipment record for each delivery (so you can easily track and manage the deliveries)
* View and manage the upcoming / past deliveries for each subscription

== Let's look at a few example use cases: ==

=== Product ships monthly, but you want to bill annually. ===

By default WooCommerce subscriptions does not allow your to bill annually, but supply the product on a monthly basis. Either you have to bill your customers monthly or yearly.
Let's say, you sell a monthly magazine subscription. Billing customers on a monthly basis would be quite inconvenient, you won't be able to plan your printing, and there will be a huge churn.
A best way is to bill the customer annually. Deliver the box every month.

Example: Product is delivered every month on a certain date (say 5th of every month). But you will charge the customers upfront - annually (every year).

In this case, you will have to:
* Create a Subscription product that bills every year
* Set your shipping cycle to every month

=== Product delivered every week, but billed monthly ===

It is the same use case as above. The only difference is that you supply the product every week, but bill the customer on a monthly basis.

Example: Vegetable Boxes. The vegetables are delivered every week, while the customer needs to be billed every month.


== Quick Start ==

1. After installing and activating the plugin, just create a new subscription product or open an existing product.
2. Check the box that says "Enable Custom Delivery Schedules"
3. Choose your Delivery Interval (Example: If you want to bill annually and ship monthly, configure the Subscription Price for Yearly, and set delivery interval as Monthly)
4. Save.

Now, when a customer purchases a subscription, he will pay annually. Every month, an order / shipping record would be generated. You can manage this at WooCommerce -> Custom Delivery Schedules.

== Plugin Features ==

* Separate your billing and delivery cycles
* Custom delivery intervals (it can be yearly / monthly / weekly)
* Manage delivery schedules in a single screen
* Email notification for customers / store admins on each shipping cycle

= Got questions? =

Just reach out to us and we will get back to you. You can either contact us via the Live Chat or via the [support request form](https://www.flycart.org/support)

== Installation ==

Just use the WordPress installer or upload to the /wp-content/plugins folder. Then Activate the Woo Discount Rules plugin.
More information could be found in the documentation

= Minimum Requirements =

* WordPress 4.4.1 or greater
* WooCommerce 2.6.1 or greater
* WooCommerce Subscriptions 2.0 or greater
* PHP version 5.6.0 or greater
* MySQL version 5.0 or greater

== Frequently asked questions ==

= Will the plugin generate an order record at every shipping cycle?  =

Yes. It will generate an order record with just the item details. Item amount or any monetary details wont be included in the record. It can be treated as a shipping record.

= Can I notify the customer via email at every cycle?  =

Yes. You can turn on the email notification that can be sent to the customer at every delivery / shipping cycle. You can configure this email at WooCommerce -> Settings -> Emails

= Will the store admins get an email notification for each shipping cycle?  =

Yes. You can configure this email notification in WooCommerce -> Settings -> Emails


== Screenshots ==

== Changelog ==

= 1.0.2 - 02/01/20 =
* Fix - Process the delivery schedules for the subscription status pending-cancel.

= 1.0.1 - 29/10/19 =
* Fix - Duplicate entry of schedules
* Improvement - Option to reschedule the canceled schedules
* Improvement - PHP 5.6 compatible

= 1.0.0 - 31/07/19 =
* Initial release

== Upgrade notice ==