# Email Newsletter Manager

The Email Newsletter Manager is the successor of the Email Newsletters extension. The latter will be marked as deprecated with the first stable release of the Email Newsletter Manager.

Conceptually this is a brand-new extension. It is not compatible with the Email Newsletters extension, nor is there an upgrade path. To ease the transition, the authors decided to even choose a new name. This allows to install both extensions in Symphony side-by-side and move any old newsletters to the new extension on after the other. No hard break (i.e. uninstalling the old Email Newsletters extension before having built everything new) necessary.


## Concept

The Email Newsletter Manager is another big step in a strategy to make Symphony the best emailing platform of all CMSs/frameworks. It builds on top of:

- The Core Email API (which has been introduced with Symphony 2.2)
- The Email Template Manager (ETM) extension (used through its public API)

In short words, the Email Newsletter Manager does:

- allow to define recipient groups based on sections in Symphony (with a datasource-like editor)
- allow to define senders
- add an Email Newsletter Manager field to sections (which in itself allows to choose which recipient groups and senders should be available for this special newsletter)
- send HTML and/or plain text emails (defined in the Email Template Manager extension)
- upon sending:
	- use a stable background process
	- give feedback in the publish panel (and in the entry overview table)
	- use templates defined in the ETM to render the email content
	- copy the recipients to a database table based on the definitions (section, filters, fields) of the recipients groups
	- read and process these recipients using pagination
	- send email from the chosen sender to these recipients using the Core Email API

Being able to use the Email Template Manager means that newsletter emails may include:

- Plain and HTML text
- sender personalization
- recipient personalization (by using filtered datasources in the templates)


### Advantages over the Email Newsletters extension

- Newsletter setup is a lot easier, and so is maintenance.
- Removed the SwiftMailer framework dependancy. (Sending is done using Symphony's Core Emai API).
- Removed the PHP CLI dependancy. (Using a custom PHP background process now.)
- Improved database logging, removed filesystem logs.
- Real-time preview of the number of recipients in groups.
- No need anymore to use Symphony pages in order to generate email content or recipients lists.
- Greatly improved scalability: The new concept has virtually no limits regarding the number of recipients.
- Public API.

The last point, the public API, is especially interesting. While to the user the Email Newsletter Manager which comes with this extension field looks rather similar to the field which was provided by the deprecated Email Newsletters extension, this field is now simply a *remote control* which plugs into the API of the extension. In other words: This field is just one possible way to send newsletters. Now you can also send mass emails using custom events!

This allows for interesting use cases (especially in conjunction with the Members extension):

- Send emails (e.g. "Please check if your contact data is still right") on a regular basis using a custom event and CRON.
- Send notification emails to big recipient groups based on events in the system (e.g. creating an entry)

Of course these possibilitie still require a bit of custom code. But the public API of the extension should really help you.


### Features

- background processes for sending
- feedback in the publish panel (and in the entry overview table)
- send html and/or text emails
- multiple recipient groups
- flexible recipient personalization
- multiple senders
- "sender personalization" can be done in XSLT (using the field's datasource output which includes the sender ID and value)
- verbose log files, gzipped (if available)


### What this extension won't do

At the time of writing the following features are supposed to be built in separate extensions (when the time comes):

- email campaign statistics/tracking
- email bounce management


## Installation & Updating

Information about [installing and updating extensions](http://symphony-cms.com/learn/tasks/view/install-an-extension/) can be found in the Symphony documentation at <http://symphony-cms.com/learn/>.



## Configuration

Most of the configuration is really easy, because there is a Symphony-style user interface for recipient groups and senders alike. However, the recipient group has one special textarea to deal with, the "Name XSLT" textarea.

The idea behind it is rather simple: You might have the full name of your recpients in a single field of your section, but ou might as well have separate fields for the name, the family name, the gender, whatever. In these cases the simplest way to build a "full name" is to use XSLT. Here are some examples:

* The full name is stored in field called `name`:

		<xsl:template match="/entry">
			<xsl:value-of select="name"/>
		</xsl:template>

* Two fields, `first name` and `last name`:

		<xsl:template match="/entry">
			<xsl:value-of select="concat(first-name, ' ', last-name)"/>
		</xsl:template>


### Static Recipients

A Static Recipients group is the right choice if you only need to send to a handful of recipients which will not change dynamically (based on section data).

Static Recipients use the [*mailbox syntax* as described in RFC2822](http://tools.ietf.org/html/rfc2822#section-3.4) (like many email clients do). Like so:

	"John Doe" <john@example.com>, chief@example.com, "Jane" jane@example.com


## Legal

This Symphony extension is released under the MIT/X11 license. The license file is included in the distribution.

Please be aware of morality and legal conditions in your country concerning mass mailings. In many countries special recipient opt-in and opt-out procedures may be required, and you might encounter the need to store opt-in evidence on your server. Meeting such regulations is beyond the scope of this extension.

Never use this extension for SPAM. If you do so we will hate you.


## API

(This documentation is still work in progress.)

- select sender
- select recipient group(s)
- select ETM template
- send


## Data Source output

<!--
	TODO check if DS output description is still true for ENM
-->

The data source output of the Email Newsletter field contains:

- newsletter-id
- author-id
- status
- total (emails)
- sent (emails)
- failed (emails)
- subject
- sender
- recipient groups
- email template ("about")

The XML output allows for advanced email customization using XSLT. You may, for example, append custom headers or footers for certain sender IDs.


## Param Pool value

If you use the Email Newsletter field to be output to the param pool (for Data Source chaining), output will be the **sender ID**! (This seems to be the most useful output.)


## The "Send" button

The "Send" button actually is a "Save and Send" button, so it will save the entry and start the "mailing engine" with a single click. I think that this is what people expect this button to do. (The implementation in Symphony has been rather hard.)

If you click the button, the system will prepare for sendind (e.g. count the recipients and display the number in the GUI), then wait for some seconds before actually starting the send process. This allows for "last minute cancelling" in case a user has not really (?) meant to really (!) send the newsletter. :-)


## Before you start

Please note that successfully sending mass mailings will require your email box to be set up "more than correctly". So please check the following:

- correct MX records
- SPF (Sender Policy Framework) record
- optional: reverse DNS entry (PTR/Reverse DNS checks)
- optional: Domain Keys / DKIM

It is beyond the scope of this software to explain these measures in detail. Anyway the first two are really important if you don't want your email to be flagged as spam. If you don't know what it is, ask your provider or consult the web (i.e. Google, isn't it?).

Here are some useful links concerning SPF records:

- <http://phpmailer.codeworxtech.com/index.php?pg=tip_spf>
- <http://old.openspf.org/wizard.html>

Here is a simple example DNS record which worked very well in my tests:

	example.com. IN TXT "v=spf1 a mx"


## Miscellaneous


### Recipient email duplicates

By design the extension will not send an email to one address multiple times.


## Known issues

- There are bugs concerning HTML form button values in Internet Explorer 6 and 7 (which shouldn't be used for Symphony anyway). This means that:

	- You won't be able to send a newsletter in IE6 (who cares?)
	- You won't be able to handle multiple email newsletters (i.e. Email Newsletter Manager fields) **in the same section** using IE7. This is considered a rare setup (but is actually a supported feature in modern browsers).

	These constraints are regarded a small price for having a combined "Save and Send" button (which is simply called "Send"). (We actually need the button's value to implement this functionality.)


---


## 2do

## Concept

Backend pages:

- Email Templates (ETM)
- Email Senders (EN2)
	- A sender is similar to the "default sender" on the prefs page, with a unique name.
- Email Recipients (EN2)
	- 1 or multiple DSs.
	- 1 or multiple param/value pairs.
	- Params will be used to filter the DSs.

EN2 field:

- in the section editor you select what will later be visible in the entry editor (=entry edit page):
	- select 1 or multiple senders
	- select 1 or multiple recipient groups
	- ETM template (layouts + subject)
- on the entry edit page you select what ahould be used for this newsletter:
	- select 1 sender
	- select 1 or multiple recipient groups

EN2 inner workings:

- ETM will __not__ send any emails!
- EN2 "send" method
	- has to build the full recipient list (all recipient groups) using pagination, then save it to the DB
	- has to write the sender to the DB
	- has to pass the task to a background process
- the background process
	- has to build "recipients slices"
	- has to get the rendered output (HTML and/or PLAIN) for each email from the ETM
	- has to send the emails using the Core Email API
	- has to write logs to the DB and/or files
	- has to update the "status" field in the DB (same as EN1)


**Recipient Groups are NOT defined in the ETM**! So the follwing is left here just for reference:

	Englishmen: {//members[nation/@handle='great-britain']/name} <{//members[nation/@handle='great-britain']/name}>;
	Dutchmen: {//members[nation/@handle='netherlands']/name} <{//members[nation/@handle='netherlands']/name}>;
	Krauts: {//members[nation/@handle='germany']/name} <{//members[nation/@handle='germany']/name}>;
	Special: {//members[nation/@handle='germany']/name} <{//members[nation/@handle='germany']/name}>,
	         michael-e,
	         <{//authors/email}>
	;


	Email Template  <- 1 Subject
	                <- 1 HTML layout (XSLT)
	                <- 1 PLAIN layout (XSLT)
	                <- n recipient groups <- m recipients each
	                <- 1 reply-to-email (may be overwritten by EN)
	                <- 1 reply-to-name  (may be overwritten by EN)


	EN2 Field  <- 1 Email Template <- n recipient groups (incl. PREVIEW)
	           <- n senders <- name (displayed in the field if multiple senders)
	                        <- smtp-host
	                        <- smtp-port
	                        <- requires_authentication
	                        <- smtp-username
	                        <- smtp-password
	                        <- from-email
	                        <- from-name
	                        <- reply-to-email
	                        <- reply-to-name
	           <- 1 throttling (number/period)
