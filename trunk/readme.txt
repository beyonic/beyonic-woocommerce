=== Beyonic Woocommerce Payment Gateway ===
Contributors: beyonic
Donate link: https://beyonic.com/
Tags: payment gateway, beyonic technologies, mobile money, m-pesa, mpesa, mobile payments
Requires at least: 3.0.1
Tested up to: 5.4.1
Stable tag: 2.0.13
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This pluin integrates Beyonic mobile payments with your woocommerce website.

== Description ==

Accept mobile payments from many mobile money networks with the Beyonic payment gateway for WooCommerce. This official plugin is developed and supported by Beyonic.

### Supported Mobile Money Countries and Networks.

The following countries and networks are supported. See [https://beyonic.com](https://beyonic.com) for an updated list of supported countries and networks.

#### Ghana:

- Airtel
- Expresso
- Globacom
- MTN
- Tigo
- Vodafone

#### Kenya:

- Airtel
- Equitel
- MPESA (Safaricom M-PESA)

#### Rwanda:

- Airtel
- MTN
- Tigo

#### Somalia:

- Somtel

#### Tanzania:

- Airtel
- Halopesa (Halotel)
- MPESA (Vodacom M-PESA)
- Tigo

#### Uganda:

- Africell
- Airtel
- MTN
- UTL

And many more. See [https://beyonic.com](https://beyonic.com) for an updated list of supported countries and networks.

### Why choose Beyonic?

Beyonic is a simple way to accept mobile money payments online. With Beyonic, you can accept payments from customers' mobile phones right from your WooCommerce website. This plugin integrates your WooCommerce shopping cart with Beyonic so that customers can choose to use mobile payments at checkout. Once customers select the mobile payment option, a payment request will be sent to their mobile phone, and once they have completed the payment, the order will be updated in your WooCommerce site so that you can process the order and delivery the goods or services.

You will need a Beyonic user account to get started. Please visit [https://beyonic.com](https://beyonic.com) to sign up for an account. See the "Installation" tab for more information.

### Suggestions / feature requests

If you have suggestions or a new feature request, please get in touch with Beyonic at info-at-beyonic.com. 

You can also follow Beyonic on twitter at [@beyonictech](https://twitter.com/beyonictech).

Finally, be sure to visit [https://beyonic.com/developers](https://beyonic.com/developers) for more ways that you can connect with Beyonic and get assistance as you set up your shopping cart.

== Installation ==

You will need a Beyonic user account to get started. Please visit [https://beyonic.com](https://beyonic.com) to sign up for an account. Once you have signed up, follow the instructions below to install the plugin.

### Automatic installation

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To
do an automatic install of, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Beyonic Woocommerce Payment Gateway” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

After instalation, remember to activate the plugin and then configure it. See below for configuration instructions.

### Manual installation via wordpress admin

1. Download the plugin zip file
2. Login to your WordPress Admin. Click on “Plugins > Add New” from the left hand menu.
3. Click on the “Upload” option, then click “Choose File” to select the zip file from your computer. Once selected, press “OK” and press the “Install Now” button.

After instalation, remember to activate the plugin and then configure it. See below for configuration instructions.

### Manual installation via FTP

This method involves downloading the plugin zip file and uploading it to your web server via your favorite FTP application. The WordPress documentation contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation): 

After instalation, remember to activate the plugin and then configure it. See below for configuration instructions.

### Configuring the plugin

To configure the plugin, login to the wordpress admin, go to "WooCommerce > Settings" from the left hand menu, then click "Checkout" from the top tab. You will see "Beyonic" as part of the available Checkout Options. Select "Beyonic" to configure the payment gateway.

You will need to enter your Beyonic API Key. To get your API Key:
1. Login to the Beyonic portal via [https://beyonic.com](https://beyonic.com)
2. Go to your user profile account and you will see the API Key at the bottom of the page.
3. Make sure that the user account has "Initiate Payment" permissions - these permissions are needed for the plugin to work.


== Frequently Asked Questions ==

= What version of PHP is required for this plugin to work? =

Use PHP v5.4 or later.

= Do we need CURL Enabled =

Yes, Curl is required.

== Screenshots ==

1. This shows the Beyonic configuration page in WooCommerce admin settings.

== Changelog ==

= 1.0 =
* Included Beyonic.php file directly.
* Moved files into vendor directory.

= 2.0 =
* Updated the plugin to work with the latest version of wordpress (Version 5.4.1).

== Upgrade Notice ==

= 1.0 =
This is the first initial release. No upgrade is required.

= 2.0 =
Simply upgrade the plugin using the wordpress upgrade functionality.
