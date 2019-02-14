=== WP-OTP ===
Contributors: noplanman
Tags: login, 2fa, otp, totp, one time password, security, recovery, freeotp, google authenticator
Requires at least: 3.1.4
Tested up to: 5.0
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Donate link: https://noplanman.ch/donate

Make your WordPress login extra secure with One Time Passwords.

== Description ==

With WP-OTP you can easily set up 2 Factor Authentication with One Time Passwords for your WordPress login.
This extra layer makes your WordPress site a lot more secure.

= Getting started =
After installing and activating the plugin, every user can enable WP-OTP on their profile page.

It's as easy as scanning the provided QR Code or entering the OTP secret to any OTP generator app.
Then just activate it by entering the generated OTP and voilà, all set up.
Now, the login requires an OTP code to succeed.

Each user gets their own secret key to authenticate with, giving them control over their login security.

= Development =
This plugin is completely open source and a work of passion.
If you would like to be part of it and join in, make your way over to the [project page](https://git.feneas.org/noplanman/wp-otp) now.
Also, if you have an idea you would like to see in this plugin or if you've found a bug, please [let me know](https://git.feneas.org/noplanman/wp-otp/issues/new).

= Filters =
There are a multitude of filters to be adjusted.

* `wp_otp_login_form_text`: Text for input field on the login screen.
* `wp_otp_login_form_text_sub`: Subtext for the input field on the login screen.
* `wp_otp_login_form_invalid_code_text`: Error text for an invalid code input on the login screen.
* `wp_otp_code_expiration_window`: Set the window of code verification expiration.
* `wp_otp_recovery_codes_count`: Number of recovery codes to generate.
* `wp_otp_recovery_codes_length`: Length of the recovery codes.
* `wp_otp_secret_length`: Length of the secret key.

= Minimum requirements =
WordPress 3.1.4, PHP 5.5.

== Installation ==

You can either use the built in WordPress installer or install the plugin manually.

For an automated installation:

1. Go to 'Plugins -> Add New' on your WordPress Admin page.
2. Search for the 'WP OTP' plugin.
3. Install by clicking the 'Install Now' button.
4. Activate the plugin on the 'Plugins' page in your WordPress Admin.

For a manual installation:

1. Upload the 'wp-otp' folder to the plugins directory of your WordPress installation.
2. Activate the plugin on the 'Plugins' page in your WordPress Admin.

== Frequently Asked Questions ==

= What if I lose my OTP authenticator? =
No problem! When activating WP-OTP, you will also get a list of recovery codes that you can use instead of entering the OTP from your authenticator app.
Be sure to regenerate them when you run out though, or better yet, reconfigure your WP-OTP to get a new secret and a new set of recovery codes.

= Can I reset my OTP secret key? =
Yes, just click the `Reconfigure` button on the profile page.

== Changelog ==

= [unreleased] =
* Update list of OTP mobile apps.

= 0.2.1 =
* Add GitLab CI for PHP Code Sniffer.
* Fix changed Base32 namespace.

= 0.2.0 =
* Tested for WP 5.0.
* Update OTPHP to 8.3.3.
* Moved project to Feneas GitLab (git.feneas.org)

= 0.1.4 =
* Tested for WP 4.8.
* Update OTPHP to 8.3.0.

= 0.1.3 =
* Make OTP code input a normal text field, to allow input verification.

= 0.1.2 =
* Add proper localisation.

= 0.1.1 =
* Longer secret by default.
* Replace/override packages not compatible with WordPress.

= 0.1.0 =
* First version!
