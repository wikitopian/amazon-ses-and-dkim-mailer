=== Amazon SES DKIM Mailer ===
Contributors: Anatta, coffee2code, Titon Barua (titanix88@gmail.com)
Donate link: http://wt.is/4g
Tags: Amazon, SES, DKIM, email, smtp, gmail, google, apps, sendmail, wp_mail, phpmailer, outgoing mail, tls, ssl, security, privacy, wp-phpmailer, coffee2code, configure-smtp, anatta
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 3.3
Version: 1.0

Configure mailing via Amazon SES or SMTP in WordPress, including support for sending e-mail via SSL/TLS (such as GMail), and DKIM signatures


== Description ==

This plugin is the renamed, rewritten, and updated version of the configure-smtp plugin.

Use this plugin to customise the mailing system used by default by WordPress to handle *outgoing* e-mails. It offers you the ability to specify the following:

* --- Amazon SES ---
* Amazon AWS access key and secret key
* Validated "From" address
* --- DKIM Settings ---
* DKIM private key (and password if encrypted)
* DKIM domain
* DKIM selector
* --- SMTP (if not using SES) ---
* SMTP host name
* SMTP port number
* If SMTPAuth (authentication) should be used.
* SMTP username
* SMTP password
* If the SMTP connection needs to occur over ssl or tls

You can indicate that you wish to use GMail or Google Apps to handle outgoing e-mail, in which case the settings are automatically configured to values appropriate for GMail or Google Apps, though you'll need to specify your GMail or Google Apps e-mail (including the "@gmail.com/@your-apps-domain.com") and password.

Regardless of whether Amazon SES or SMTP is enabled, the plugin provides you the ability to define the name and e-mail of the 'From:' field for all outgoing e-mails and to enable DKIM signing if your web host or server mail service does not provide DKIM.

A simple test button is also available that allows you to send a test e-mail to yourself to check if sending e-mail has been properly configured for your blog.

Additional Links: [Plugin Homepage](http://www.anatta.com/tools/amazon-ses-with-dkim-support-wordpress-plugin/)

To do: Incorporate Amazon SES stats checking and display and implement failover to SMTP once quota is reached.

== Installation ==

1. Unzip the plugin zip file inside the `/wp-content/plugins/` directory (or install via the built-in WordPress plugin installer)
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. Click the plugin's `Settings` link next to its `Deactivate` link (still on the Plugins page), or click on the `Settings` -> `Mail Settings` link, to go to the plugin's admin settings page.  Optionally customise the settings (to configure it if the defaults aren't valid for your situation).
1. For DKIM generate a public and private key then, upload your private key to your server (we recommend naming it .htkeyprivate and placing it in the website root), you will also need to add a DKIM dns record with your selector and DKIM public key.  There are many good tutorials and key generators to help - Google is your friend. On a linux server or Mac you can generate your own DKIM keys with the following commands: openssl genrsa -out private.key 1024 | openssl rsa -in private.key -pubout -out public.key.
1. For DKIM, set a DNS TXT record something like: <selector>._domainkey.<example.com>.  v=DKIM1; k=rsa; g=*; s=email; h=sha1; t=s; p=<your public key>;
1. (optional) Use the built-in test to see if your blog can properly send out e-mails.

== Frequently Asked Questions ==

= I am already able to receive e-mail sent by my blog, so would I have any use or need for this plugin? =

Most likely, no.  Not unless you have a preference for having your mail sent out via a different SMTP server, such as GMail, or would like the reliability and credibility of using Amazon's architecture.

= How do I get an Amazon AWS account? = Sign up at http://aws.amazon.com.

= How do I get my Amazon AWS keys? = You can access these from your AWS Management Console from the Security Credentials link under your account name in the top right corner

= Amazon SES is only letting me <em>send</em> to registered addresses = You need to apply for production access from your Amazon SES Management Console (there's a big button - you can't miss it).  Until production access is granted, you will only be able to send to registered addresses.

= Amazon SES is not letting me send from the from address = It is a requirement of Amazon SES that all sender addresses are verified before they can be used as a From address.  Validate your address through the Amazon SES Management Console. 
= How do I find out my SMTP host, and/or if I need to use SMTPAuth and what my username and password for that are? =

Check out the settings for your local e-mail program.  More than likely that is configured to use an outgoing SMTP server.  Otherwise, contact your host or someone more intimately knowledgeable about your situation.

= I've sent out a few test e-mails using the test button after having tried different values for some of the settings; how do I know which one worked? =

If your settings worked, you should receive the test e-mail at the e-mail address associated with your WordPress blog user account.  That e-mail contains a time-stamp which was reported to you by the plugin when the e-mail was sent.  If you are trying out various setting values, be sure to record what your settings were and what the time-stamp was when sending with those settings.

= Why am I getting this error when attempting to send a test message: `SMTP Error: Could not connect to SMTP host.`? =

There are a number of reasons you could be getting this error:
# Your server (or a router to which it is connected) may be blocking all outgoing SMTP traffic.
# Your mail server may be configured to allow SMTP connections only from certain servers.
# You have supplied incorrect server settings (hostname, port number, secure protocol type).

= What am I getting this error: `SMTP Error: Could not authenticate.`? =

The connection to the SMTP server was successful, but the credentials you provided (username and/or password) are not correct.


== Screenshots ==

1. A screenshot of the plugin's admin settings page.

== Changelog ==

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 1.0 =
* If migrating from configure-smtp (or other mailer plugins), please remember to deactivate it first to avoid conflicts.