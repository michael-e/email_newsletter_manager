# Email Newsletter Manager

The Email Newsletter Manager is the successor of the Email Newsletters extension. The latter will be marked as deprecated with the first stable release of the Email Newsletter Manager.

Conceptually this is a brand-new extension. It is not compatible with the Email Newsletters extension, nor is there an upgrade path. To ease the transition, the authors decided to even choose a new name. This allows to install both extensions in Symphony side-by-side and move any old newsletters to the new extension one after the other. No hard break (i.e. uninstalling the old Email Newsletters extension before having built everything new) is necessary.



## Concept

[The big picture diagram](https://github.com/downloads/creativedutchmen/email_newsletter_manager/EmailNewsletterManager.pdf) provides an overview of the connection between Email Newsletter Manager extension and Email Template Manager extension, fields and parameters used for filtering data sources containing newsletter content or recipients and use of Symphony's Core Email API by the extension.

The Email Newsletter Manager is another big step in a strategy to make Symphony the best emailing platform of all CMSs/frameworks. It builds on top of:

- The Email Template Manager (ETM) extension (used through its public API).
- The Core Email API (which has been introduced with Symphony 2.2).

In short words, the Email Newsletter Manager (plus the Email Template Manager) will:

- Allow to define multiple recipient groups based on sections in Symphony (with a datasource-like editor).
- Allow to define multiple senders.
- Add an Email Newsletter Manager field to sections (which in itself allows to choose which recipient groups and senders should be available for this special newsletter).
- Use templates defined in the ETM to render the email content.
- Send HTML and/or plain text emails (defined in the Email Template Manager extension).
- Use flexible recipient personalization (by using filtered datasources in the templates).
- Even perform "sender personalization" in XSLT (using the field's datasource output which includes the sender ID and value).

Upon sending it will:

- Use a stable background process.
- Prevent duplicate emails (to the same address).
- Give feedback in the publish panel (and in the entry overview table).
- Read and process recipients using pagination, which means that there is virtually no limit to the number of recipients.


### What this extension won't do

At the time of writing the following features are supposed to be built in separate extensions (when the time comes):

- email campaign statistics/tracking
- email bounce management


### Advantages over the Email Newsletters extension

- Newsletter setup is a lot easier, and so is maintenance.
- Removed the SwiftMailer framework dependancy. (Sending is done using Symphony's Core Emai API).
- Real-time preview of the number of recipients in groups.
- No need anymore to use Symphony pages in order to generate email content or recipients lists.
- Greatly improved scalability: The new concept has virtually no limits regarding the number of recipients.
- Public API.

The last point, the public API, is especially interesting. While to the user the Email Newsletter Manager which comes with this extension field looks rather similar to the field which was provided by the deprecated Email Newsletters extension, this field is now simply a *remote control* which plugs into the API of the extension. In other words: This field is just one possible way to send newsletters. Now you can also send mass emails using custom events!

This allows for interesting use cases (especially in conjunction with the Members extension):

- Send emails on a regular basis (e.g. "Please check if your contact data is still right") using a custom event and CRON.
- Send notification emails to big recipient groups based on events in the system (e.g. creating an entry)

Of course these possibilitie still require a bit of custom code. But the public API of the extension should really help you.


### Disadvantages over the Email Newsletters extension

* It is not possible to use multiple Datasources to build up a recipient group.


### Requirement: The PHP CLI

In simple words, the CLI SAPI allows to run PHP scripts from the command line, and **this can be initiated from within PHP scripts** even on hosting accounts without shell access. The Email Newsletter Manager extension runs the actual (background) mailing processes using the PHP CLI SAPI. Some useful articles on this topic:

- <http://articles.sitepoint.com/article/php-command-line-1>
- <http://articles.sitepoint.com/article/php-command-line-2>
- <http://php.net/manual/en/features.commandline.php>

If you are unsure if the PHP CLI SAPI is installed and you have command line access, type

	php -v

in your shell. If you don't get a verbose answer, the CLI SAPI is not installed. On Debian, you may install it by typing

	apt-get install php5-cli

If you are on a shared hosting account, you should ask your provider but CLI should be installed on most shared hosting accounts.



## Installation & Updating

Information about [installing and updating extensions](http://symphony-cms.com/learn/tasks/view/install-an-extension/) can be found in the Symphony documentation at <http://symphony-cms.com/learn/>.



## Configuration

The configuration is split into 4 parts:

* Newsletter Senders - found under Blueprints in Symphony
* Newsletter Recipients - found under Blueprints in Symphony
* Template (handled by Email Template Manager extension)
* Email Newsletter Manager field in your newsletter section


### Newsletter Senders

*Symphony: Blueprints -> Newsletter Senders*

Newsletter Senders allows defining multiple senders for the newsletter. There needs to be at least one sender defined before sending the newsletter.

*Sender Properties*

* **Name:** Reference name used to select sender before sending the newsletter. Fill with anything providing meaningful description.

*Email Gateway*

* **Gateway:** The Symphony core includes two gateways, and more gateways can be installed using extensions. The core gateways are:
	* Sendmail: The “traditional” way to send emails, using PHP’s mail() function and the server’s Sendmail socket. 
	* STMP: Allows the system to send emails using a remote SMTP server. This method is generally more reliable and can be especially helpful when mail/Sendmail is unavailable (e.g. on localhost, or on cloud setups).

* **From Name:** Name displayed to email newsletter recipient, for examply blog author name or company name.

* **From Email Address:** Email used to send the newsletter. In case of Sendmail gateway it typically is limited to addresses from domains pointing to the server (email@domain.com); SMTP gives more flexibility.

*Email Gateway: SMTP only*

* **Host:** IP or address of the host. For Gmail: `smtp.gmail.com`

* **Port:** Port of the host. For Gmail: `587`

* **Encryption:** Encryption of the host. For Gmail: `TLS encryption`

* **Requires Authentication:** Most SMTP servers require username and password. Check this field if that's the case. Enter your username (typically same as email address) and password in 2 fields below.

* **Username:** Self explanatory.

* **Password:** Self explanatory.

*Advanced Settings*

* **Reply-To Name:** Name to be used for reply by newsletter recipient's email client when replying.

* **Reply-To Email:** Email address to be used for reply by newsletter recipient's email client when replying.

* **Emails per batch:** The amount of emails the system should send per batch. A value of 10 or lower is recommended.

* **Time per batch:** The time reserved for each batch. Do not(!) set this value higher than the timeout value of php.

### Newsletter Recipients

*Symphony: Blueprints -> Newsletter Recipients*

Newsletter Recipients allows defining multiple recipient groups for the newsletter. There needs to be at least one recipient group defined before sending newsletter.

*Essentials*

* **Name:** A unique name for the data source containing email recipients group.

* **Source:** The source from which data will be drawn.

	* Dynamic Source: Any section or system source from your Symphony installation. Needs to be created in Symphony's Blueprints -> Sections and contains at least recipient's name and email address.
	* Static Recipients: A Static Recipients group is the right choice if you only need to send to a handful of recipients which will not change dynamically (based on section data).

*Filter Results: Dynamic Source only*

* Filtering of email addresses per every email sent happens automatically. There is no need to filter by email address hence you are free to filter data source as required by your needs. In case of typical subscription based newsletter it would be used for filtering only recipients who confirmed their subscription.

* For details about filtering data sources in Symphony refer to: ["Data Source Filters" - Concepts - Learn - Symphony.](http://symphony-cms.com/learn/concepts/view/data-source-filters/)

*Recipients: Static Recipients only*

* Static Recipients use the [*mailbox syntax* as described in RFC2822](http://tools.ietf.org/html/rfc2822#section-3.4) (like many email clients do). Like so:  
`"John Doe" <john@example.com>, chief@example.com, "Jane" jane@example.com`

*Filter Results: Dynamic Source only*

* **Email:** Specifies field storing email address for recipients.

* **Name Field(s) and XSLT:** The idea behind it is rather simple: You might have the full name of your recpients in a single field of your section, but you might as well have separate fields for the name, the family name, the gender, whatever. In these cases the simplest way to build a "full name" is to use XSLT. Here are some examples:

	* The full name is stored in field called `name`:

		`<xsl:template match="/entry">  
			<xsl:value-of select="name"/>  
		</xsl:template>`

	* Two fields, `first name` and `last name`:

		`<xsl:template match="/entry">  
			<xsl:value-of select="concat(first-name, ' ', last-name)"/>  
		</xsl:template>`


### Email Template Manager: basic email newsletter step-by-step

*Symphony: Blueprints -> Email Templates  
[Email Template Manager](https://github.com/creativedutchmen/email_template_manager) extension has to be installed first.*

Email Templates provides clean interface, separated from default Symphony pages, for managing email templates.  
For the sake of an example, we will go step-by-step through creating basic newsletter.

* **Model recipients section.**   
For details about sections in Symphony view: ["Sections" – Concepts – Learn – Symphony.](http://symphony-cms.com/learn/concepts/view/sections/)

	* Required minimum data for recipient is name and email address.   
	Create two text input fields called `name` and `email`.

	* Go to Blueprints -> Newsletter Recipients, create new recipients group and make created recipients section as it's source. Fill other fields appropriately.

	* If you want to refer to recipient dynamically in the email template (for example: to begin the email with Hi `name`) then create regular data source called `Recipient` and filter the email address field by `{$etm-recipient}` parameter.

* **Model newsletter sction.**   
In this section emails will be created and sent.

	* In our example email template will only require `subject` text input field and `content` textarea. Create section with those.

	* Do not add `Email Newsletter Manager` field to you your section yet.

	* To make testing the email template easier, create the first entry and fill it with content.

	* Create regular data source called `Newsletter` and include both `subject` and `content` elements to it.

* **Create email template.**

	* Head back to Blueprints -> Email Templates and create new template.   
	Name it in a meaningful way and select `Newsletter` and `Recipient` data sources.

	* It is advised to send both HTML and Plain text emails so use both layouts if possible.

	* Ignore `Recipients`, `Reply-To Name` and `Reply-To Email Address` fields in *Email Settings*. These were already provided in *Blueprints -> Newsletter Senders*.

	* As we want dynamic subject based on `Newsletter` section's `Subject` field, provide proper XPath to it.  
	`{/data/newsletter/entry/subject}`

	* Edit HTML and Plain text templates to match your needs. Remember that you can refer to filtered recipient per every email sent using `{/data/recipient/entry}` data source output.

* **Add Email Newsletter Manager field to newsletter section.**

	* Add `Email Newsletter Manager` field to `Newsletter` section. Select the template, recipients groups and senders.

	* Open `Newsletter` data source and filter `Email Newsletter Manager` field by `{$enm-newsletter-id}` so that proper entry is used for sent emails.

* **Remember few simple email styling rules.**

	* Use tables instead of divs.

	* Do not attach external stylesheet. Make use of inline CSS.

	* Store images on your server and provide their source as a full, absolute url.

	* Do not assume images will be viewed. Most email clients block images by default and average users leave them that way.

	* Include `view in the browser` link. Put it at the top of the email.  
	Create regular Symphony page that will hold all email templates filtered by title, subject or whatever suitable.

	* Always include Plain text version unless you specifically know your audience.

	* Test everything before sending, especially if you are doing this for a client.   
	Test at least in Outlook, Thunderbird and Gmail. Apple Mail if you use Mac.

* **Enjoy.**



## Legal

This Symphony extension is released under the MIT/X11 license. The license file is included in the distribution.

Please be aware of morality and legal conditions in your country concerning mass mailings. In many countries special recipient opt-in and opt-out procedures may be required, and you might encounter the need to store opt-in evidence on your server. Meeting such regulations is beyond the scope of this extension.

Never use this extension for SPAM. If you do so we, will hate you.



## API

(This part of the README is still missing.)

- select sender
- select recipient group(s)
- select ETM template
- send



## Data Source output

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

The "Send" button actually is a "Save and Send" button, so it will save the entry and start the "mailing engine" with a single click. We think that this is what people expect this button to do.

There are bugs concerning HTML form button values in Internet Explorer 6 and 7 (which shouldn't be used for Symphony anyway). This means that:

- You won't be able to send a newsletter in IE6 (who cares?)
- You won't be able to handle multiple email newsletters (i.e. Email Newsletter Manager fields) **in the same section** using IE7. This is considered a rare setup (but is actually a supported feature in modern browsers).

These constraints are regarded a small price for having a combined "Save and Send" button (which is simply called "Send"). (We actually need the button's value to implement this functionality.)



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
