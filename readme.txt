=== Amazon SES DKIM Mailer ===
Contributors: Anatta
Donate link: http://wt.is/4g
Tags: Amazon, SES, DKIM, email, smtp, gmail, google, apps, sendmail, wp_mail, phpmailer, outgoing mail, tls, ssl, security, privacy, wp-phpmailer, coffee2code, configure-smtp, anatta
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 1.0.2
Version: 1.0.2

Configure Wordpress mailer with DKIM signing and delivery via Amazon SES, GMail or SMTP over SSL/TLS. 

== Description ==

This plugin from [Anatta®](http://www.anatta.com) adds Amazon SES, DKIM and third party SMTP capability to the Wordpress mailing system for outgoing e-mails. It is based on the configure-smtp plugin v3.1 by coffee2code and the Amazon SES class for PHPMailer by Titon Barua.

The plugin provides the ability to configure the following;

**Amazon SES**:

* Amazon AWS access key and secret key
* Amazon SES Validated 'From:' address
* 'From:' Sender Name

**DKIM**:

* DKIM private key
* DKIM private key password
* DKIM domain
* DKIM selector

**SMTP (if not using SES)**:

* SMTP host name
* SMTP port number
* If SMTPAuth (authentication) should be used.
* SMTP username
* SMTP password
* SMTP connection (ssl or tls)

Regardless of whether Amazon SES or SMTP is enabled, the plugin provides you the ability to use the name and e-mail of the 'From:' field for all outgoing e-mails and to enable DKIM signing if your web host or server mail service does not provide DKIM.

Two test buttons are also available that allows you to send a test e-mail to yourself and to a DKIM checking service to validate everything has been properly configured.

Additional Links: [Plugin Homepage](http://www.anatta.com/tools/amazon-ses-with-dkim-support-wordpress-plugin/)

**To do**: 

* Incorporate Amazon SES stats reporting
* Implement failover to SMTP once SES quota is reached, or SES error code received
* Add simple DKIM key and DNS record generator to plugin homepage

== Installation ==

1. Deactivate any existing mailer plugins to avoid conflicts.

1. Unzip the plugin zip file inside the `/wp-content/plugins/` directory (or install via the built-in WordPress plugin installer).

1. Activate the plugin through the 'Plugins' admin menu in WordPress.

1. Click the plugin's `Settings` link next to its `Deactivate` link (still on the Plugins page), or click on the `Settings` -> `Mail Settings` link, to go to the plugin's admin settings page.  Customise the settings for your situation.

1. For Amazon SES, apply for *Production Access*. Without this, you will not be able to send e-mail to addresses that you do not control.

1. For DKIM generate a public and private key and upload your private key to your server *(we recommend naming it .htkeyprivate and placing it in the website root, and setting permissions to 400 or 440)*.  There are many good tutorials and online key generators to help; If you are a Windows user, Google is your friend.  On a linux server or Mac you can easily generate your own DKIM keys with a password of 'change-me' using the following terminal command: `openssl genrsa -des3 -passout pass:change-me -out .htkeyprivate 1024 && openssl rsa -in .htkeyprivate -passin pass:change-me -pubout -out .htkeypublic`

1. For DKIM, set a DNS TXT record something like: HOST: `your-selector._domainkey.example.com.`  TXT VALUE: `v=DKIM1; k=rsa; g=*; s=email; h=sha1; t=s; p=your-public-key;`

1. Use the built-in tests to check that your blog can properly send out (DKIM signed) e-mails.

== Frequently Asked Questions ==

= I am already able to receive e-mail sent by my blog, so would I have any use or need for this plugin? =

Most likely, no.  Not unless you have a preference for having your mail sent out via a different SMTP server, such as GMail or would like the reliability and scalability Amazon's SES architecture.  If your SMTP server or web host does not support DKIM signing, you may wish to use this plugin to DKIM sign outgoing mail.

= I just want to DKIM sign my emails, I do not need to use Amazon SES or a third party SMTP server, can I still use this plugin? =

No problem, just install the plugin and only set the DKIM settings.  If you want to use DKIM with an SMTP server then set the DKIM and SMTP server details.  Note that many SMTP servers including Gmail already DKIM sign all mails so be sure to check that you are not double signing.

= How can I check if DKIM is configured correctly? = 

Brandon Checketts has an excellent [online tool](http://www.brandonchecketts.com/emailtest.php) for checking your DKIM signatures. The button at the bottom of the plugin settings page will send a message to this service and will display a link where you can check your DKIM signature.  Note that if using Amazon SES, you need to have *Production Access* in order for this check to be able to send an email to an unregistered address.

= How do I get an Amazon AWS account? = Sign up at http://aws.amazon.com, it's free and new users get access to the free tier for 1 year (correct as of November 2011).

= How do I get my Amazon AWS keys? = You can access these from your AWS Management Console from the Security Credentials link under your account name in the top right corner.

= Amazon SES is not letting me *send from* the 'From:' address = It is a requirement of Amazon SES that all sender addresses are verified before they can be used as a 'From:' address.  Validate your address through the Amazon SES Management Console. 

= Amazon SES is only letting me *send to* my registered addresses = You need to apply for production access from your Amazon SES Management Console (there's a big button - you can't miss it).  Until production access is granted, you will only be able to send email your registered addresses.

= Why do the emails sent from my blog to a GMail address or Microsoft Outlook have *via email-bounces.amazonses.com* appended to the 'From:' address? = This is an indication that you do not have DKIM set up or set up correctly. If each message is properly DKIM signed, this *via email-bounces.amazonses.com* notification should dissapear.

= How do I find out my SMTP host, and/or if I need to use SMTPAuth and what my username and password for that are? = Check out the settings for your local e-mail program.  More than likely that is configured to use an outgoing SMTP server.  Otherwise, contact your host or someone more intimately knowledgeable about your situation.

= I've sent out a few test e-mails using the test button after having tried different values for some of the settings; how do I know which one worked? =

If your settings worked, you should receive the test e-mail at the e-mail address associated with your WordPress blog user account.  That e-mail contains a time-stamp which was reported to you by the plugin when the e-mail was sent.  If you are trying out various setting values, be sure to record what your settings were and what the time-stamp was when sending with those settings.

= Why am I getting this error when attempting to send a test message: SMTP Error: Could not connect to SMTP host? = There are a number of reasons you could be getting this error:

1. Your server or its router, firewall or ISP may be blocking all outgoing SMTP traffic (particularly common with port 25).
2. Your mail server may be configured to allow SMTP connections only from certain servers.
3. You have supplied incorrect server settings (hostname, port number, secure protocol type).

= What am I getting this error: SMTP Error: Could not authenticate? = The connection to the SMTP server was successful, but the credentials you provided (username and/or password) are not correct.

= Where can I find out more.? = You can find out more about the plugin and us at [Anatta® Operational Innovation™](http://www.anatta.com/tools/amazon-ses-with-dkim-support-wordpress-plugin/).


== Screenshots ==

1. A screenshot of the plugin's admin settings page.
2. Another screenshot of the plugin's admin settings page.
3. Another screenshot of the plugin's admin settings page.

== Changelog ==

= 1.0.2 =
* Bugfix.
= 1.0.1 =
* Added button to check DKIM signatures with Brendon Checkett's [online tool](http://www.brandonchecketts.com/emailtest.php).
= 1.0 =
* Initial Release.

== Upgrade Notice ==

= 1.0.2 =
* If migrating from configure-smtp (or other mailer plugins), please remember to deactivate it first to avoid conflicts.
