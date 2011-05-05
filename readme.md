# Email Newsletters 2

Still in development...



	Recipient Groups are defined in the ETM:

	Englishmen: {//members[nation/@handle='great-britain']/name} <{//members[nation/@handle='great-britain']/name}>;
	Dutchmen: {//members[nation/@handle='netherlands']/name} <{//members[nation/@handle='netherlands']/name}>;
	Krauts: {//members[nation/@handle='germany']/name} <{//members[nation/@handle='germany']/name}>;
	Special: {//members[nation/@handle='germany']/name} <{//members[nation/@handle='germany']/name}>,
	         michael-e,
	         <{//authors/email}>
	;


	Email Template  <- 1 Subject
	                <- 1 HTML text
	                <- 1 PLAIN text
	                <- n recipient groups
	                <- 1 reply-to-email (may be overwritten by EN)
	                <- 1 reply-to-name  (may be overwritten by EN)


	EN2 Field  <- 1 Email Template <- n recipient groups (incl. PREVIEW)
	           <- n senders <- name (displayed in the field if multiple senders)
	                        <- smtp-host
	                        <- smtp-port
	                        <- smtp-username
	                        <- smtp-password
	                        <- from-email
	                        <- from-name
	                        <- reply-to-email
	                        <- reply-to-name
	           <- 1 throttling (number/period)


Questions:

- Multiple Templates per EN2 field? (e.g. send different email to site admin)
- How will the senders be configured in EN2?

What does this mean?

- EN2 has to get the full recipient list from the ETM, then save it to the DB
- EN2 has to get the rendered output (HTML and/or PLAIN) from the ETM (then save it to the DB?)
- EN2 has to pass the task to a background process
- the background process has to build "recipients slices" and send those slices using the Core Email API
- this means that ETM will __not__ send any emails!
- EN2 has to write logs to the DB and/or files
- EN2 has to update the "status" field in the DB (same as EN1)


DB content:

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

Log file:

- ? subject (string, XPath/params)
- ? content_html (XSLT)
- ? content_text (XSLT)
- errors (verbose)

Questions:

- link to the log file in the backend = security risk?

