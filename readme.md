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
- upon sending:
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
- Removed dependancy on the SwiftMailer framework. (Sending is done using Symphony's Core Emai API).
- Improved database logging, removed filesystem logs
- Real-time preview of the number of recipients in groups.
- Public API.

The last pint, the public API, is especially interesting. While to the user the Email Newsletter Manager field looks rather similar to the field which was provided by the deprecated Email Newsletters extensions, it is now nothing more than a *remote control* which plugs into the API of the extension. In other words: This field is just one way to send newsletters. Now you can also send mass emails using custom events, for example.

This allows for interesting use cases (especially in conjunction with the Members extension):

- Send emails (e.g. "Please check if your contact data is still right") on a regular basis using a custom event and CRON.
- Send notification emails to big recipient groups based on events in the system (e.g. creating an entry)

Of course these possibilitie still require a bit of custom code. But the public API of the extension should really help you.


## API

<!--
	TODO Add the API :-)
-->
- select sender
- select recipient group(s)
- select ETM template
- send


## 2do

- ? make the recipients input field in the ETM a textarea (see configuration example below)

- Multiple Templates per EN2 field? (e.g. send different email to site admin)
- Does ETM allow datasource filtering for recipients **at all**? (e.g. `$country`) – NO
- Related: Should ETM support groups??? – NO
- Where will sender credentials be saved? (config file or DB?)
- logging to DB only or additional file?
- link to the log file in the backend (field)
- log email content? (probably not)
- not EN, but related: data backup/logging extension for opt-outs


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


## DB content

- author_id
- author_ip_address
- sender_id
- rec_group_ids
- status
- error_message (displayed in the field!)
- start_time
- end_time
- rec_all (-> also: statistics)
- rec_sent
- rec_errors
- log_file (i.e. name/path)

## Log file content

- ? subject (string, XPath/params)
- ? content_html (XSLT)
- ? content_text (XSLT)
- errors (verbose)


