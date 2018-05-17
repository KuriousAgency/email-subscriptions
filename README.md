# Email Subscriptions plugin for Craft CMS 3.x

Allows subscribing and unsubscribing from 3rd party email lists.

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1.  Open your terminal and go to your Craft project:

        cd /path/to/project

2.  Then tell Composer to load the plugin:

        composer require KuriousAgency/email-subscriptions

3.  In the Control Panel, go to Settings → Plugins and click the “Install” button for Email Subscriptions.

## Email Subscriptions Overview

This is a simple plugin that allows a user to manage their email list subscriptions on a 3rd party service like MailChimp for example.

They can subscribe and unsubscribe from any of the lists within your 3rd party service account.

Currently the only supported service is MailChimp.

## Configuring Email Subscriptions

Within the plugin settings, choose a 3rd party service and enter the API key.

## Example Form

```twig
<form method="post" accept-charset="UTF-8">

	{{ csrfInput() }}
	<input type="hidden" name="action" value="email-subscriptions/update">

	{% for list in craft.emailSubscriptions.lists %}
		<input type="checkbox" id="list_{{ list.id }}" name="lists[]" value="{{ list.id }}">
		<label for="list_{{ list.id }}">{{ list.name }}</label>
	{% endfor %}

	<button type="submit">Update</submit>

</form>
```

## Variables

You can get the lists that an email is subscribed to via:

```twig
craft.emailSubscriptions.getListsByEmail('name@email.com')
```

if you do not pass in an email address then it will use the current user's email.

```twig
craft.emailSubscriptions.listsByEmail
```

## Flash Messages

Two messages can set by the plugin:

```
{% set success = craft.session.getFlash('notice') %}

{% set error = craft.session.getFlash('error') %}
```

## Email Subscriptions Roadmap

Some things to do, and ideas for potential features:

*   Support other 3rd party services.
*   update subscriptions on saveUser event.

Brought to you by [Kurious Agency](https://kurious.agency)
