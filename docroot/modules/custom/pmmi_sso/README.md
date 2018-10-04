# Introduction

As described in the official Personify SSO protocol documentation:

"Single Sign-On (SSO) is an application developed by Personify primarily for 
 associations to integrate different vendor sites. Using SSO, associations can 
 provide a seamless experience to their members for connecting to other vendors’ 
 websites. Personify Single Sign-On provides a means for organizations to facilitate
 movement of their members and customers between their organization website and the 
 websites of their third-party vendors and partners. The Identity Management System 
 (IMS) enhances the member’s experience for both the association’s website and the 
 integrated websites from other vendors. Using IMS, vendors can manage members’ 
 access to the different areas of their websites."
 
Using a single-sign on service like Personify SSO is a benefitial because it provides:

* Convenience. Your users don't need to remember credentials for multiple
  different web services.
* Security. Your Drupal website never sees a user's password.

Not all parts of the specification are implemented, but the core functionality
that the protocol describes works well.

# Requirements

This module requires the following modules: 

* External Authentication (https://drupal.org/porject/externalauth)

# Installation

Download and install the module as you would with any other Drupal module:

* Download this module and move the folder to the DRUPAL_ROOT/modules 
  directory.
* Enable the module in your Drupal admin interface.

# Configuration

## Getting Started and Basic Usage

All of the settings for this module are on the single configuration page
described above. To get started, you simply need to configure the settings
for your Personify SSO server.

This module exposes a specific URL path on your website that will trigger
the Personify SSO authentication process for your users:

http://yoursite.com/ssologin

Users will be redirected to your Personify SSO server to authenticate. If they 
already have an active session with the Personify SSO server, they will immediately 
be redirected back to your site and authenticated seemlessly. If not, they will be 
prompted to enter their credentials and then redirected back to your Drupal site 
and authenticated.

You can create a login button on your website that links directly to this
path to provide a way for your users to easily login.

## Account Handling & Auto Registration

This module simply provides a way to automatically register users.
This way, when a user authenticates with Personify SSO, a local Drupal account will
automatically be created for that user. The password for the account will
be randomly generated and is not revealed to the user.

This module does NOT prevent local Drupal authentication (using the standard
login form). If a user knew their randomly generated password, they could bypass 
Personify SSO authenticaton and login to the Drupal site directly unless Forced Login
is properly configured.

## Gateway Login

With this feature enabled, anonymous users that view some or all pages on
your site will automatically be logged in IF they already have an active
Personify SSO session with the Personify SSO server.

If the user does not have an active session with the Personify SSO server, 
they will see the Drupal page requested as normal, if they have permission.

This feature works by quickly redirecting the user to the Personify SSO 
server to check for an active session, and then redirecting back to the 
page they originally requested on your website.

# Troubleshooting
The fastest way to determine why the module is not behaving as expected it to
enable the debug logging in this module's settings page. Messages related to
the authentication process, including errors, will be logged. To view these
logs, enable the Database Logging module or the Syslog module.

# API

## Events
Modules may subscribe to events to alter the behavior of the Personify SSO 
module or act on the data it provides.

All of the events that this module dispatches are located in the `src/Event`
folder. Please see the comments in each of those event classes for details about
what each event does and the common use cases for subscribing to them.

## Forcing authentication yourself
The Personify SSO module will always attempt to authenticate a user when they 
visit the /ssologin path, or if they visit a Gateway path that's configured in 
the module's settings.

If there are other times you'd like to force a user to authenticate, use the
`PMMISSORedirector` service. This service allows you to build a redirect response
object that will redirect users to the Personify SSO server for authentication.

Inject this service class into one of your own services (like a kernel event
subscriber) and call the `buildRedirectResponse` method to create the response
object.
