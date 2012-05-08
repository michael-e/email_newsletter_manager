<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<h2>
		<span><xsl:choose><xsl:when test="/data/senders/entry/name"><xsl:value-of select="/data/senders/entry/name" /></xsl:when><xsl:otherwise>New Sender</xsl:otherwise></xsl:choose></span>
	</h2>
	<form method="post">
		<fieldset class="settings">
			<legend>Sender Properties</legend>
			<div>
				<div>
					<xsl:if test="/data/errors/name">
						<xsl:attribute name="class">
							<xsl:text>invalid</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<label>
						<xsl:text>Name</xsl:text>
						<input type="text" name="fields[name]">
							<xsl:attribute name="value">
								<xsl:if test="/data/fields">
									<xsl:value-of select="/data/fields/name"/>
								</xsl:if>
								<xsl:if test="not(/data/fields) and /data/senders/entry/name">
									<xsl:value-of select="/data/senders/entry/name"/>
								</xsl:if>
							</xsl:attribute>
						</input>
					</label>
					<xsl:if test="/data/errors/name">
						<p><xsl:value-of select="/data/errors/name"/></p>
					</xsl:if>
				</div>
			</div>
		</fieldset>
		<fieldset class="settings picker">
			<legend>Email Gateway</legend>
			<label>
				<select name="settings[gateway]">
					<xsl:for-each select="/data/senders/gateways/entry">
						<option value="{handle}">
							<xsl:if test="/data/senders/entry/*[name() = current()/handle]">
								<xsl:attribute name="selected">
									<xsl:text>selected</xsl:text>
								</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="name"/>
						</option>
					</xsl:for-each>
				</select>
			</label>
		</fieldset>
		<xsl:for-each select="/data/senders/gateways/entry">
			<xsl:copy-of select="config_panel/node()" />
		</xsl:for-each>
		<fieldset class="settings">
			<legend>Advanced Settings</legend>
			<div class="group">
				<div>
					<label>
						<xsl:text>Reply-To Name </xsl:text>
						<i>optional</i>
						<input type="text" name="fields[reply-to-name]">
							<xsl:attribute name="value">
								<xsl:if test="/data/fields">
									<xsl:value-of select="/data/fields/reply-to-name"/>
								</xsl:if>
								<xsl:if test="not(/data/fields) and /data/senders/entry/reply-to-name">
									<xsl:value-of select="/data/senders/entry/reply-to-name"/>
								</xsl:if>
							</xsl:attribute>
						</input>
					</label>
				</div>
				<div>
					<label>
						<xsl:text>Reply-To Email </xsl:text>
						<i>optional</i>
						<input type="text" name="fields[reply-to-email]">
							<xsl:attribute name="value">
								<xsl:if test="/data/fields">
									<xsl:value-of select="/data/fields/reply-to-email"/>
								</xsl:if>
								<xsl:if test="not(/data/fields) and /data/senders/entry/reply-to-email">
									<xsl:value-of select="/data/senders/entry/reply-to-email"/>
								</xsl:if>
							</xsl:attribute>
						</input>
					</label>
				</div>
			</div>
			<div class="group">
				<div>
					<label>
						<xsl:text>Emails per batch</xsl:text>
						<i>optional</i>
						<input type="text" name="fields[throttle-emails]">
							<xsl:attribute name="value">
								<xsl:if test="/data/fields">
									<xsl:value-of select="/data/fields/throttle-emails"/>
								</xsl:if>
								<xsl:if test="not(/data/fields) and /data/senders/entry/throttle-emails">
									<xsl:value-of select="/data/senders/entry/throttle-emails"/>
								</xsl:if>
								<xsl:if test="not(/data/fields) and not(/data/senders/entry/throttle-emails)">
									<xsl:text>10</xsl:text>
								</xsl:if>
							</xsl:attribute>
						</input>
					</label>
					<p class="help">The amount of emails the system should send per batch. A value of 10 or lower is recommended.</p>
				</div>
				<div>
					<label>
						<xsl:text>Time per batch</xsl:text>
						<i>optional</i>
						<input type="text" name="fields[throttle-time]">
							<xsl:attribute name="value">
								<xsl:if test="/data/fields">
									<xsl:value-of select="/data/fields/throttle-time"/>
								</xsl:if>
								<xsl:if test="not(/data/fields) and /data/senders/entry/throttle-time">
									<xsl:value-of select="/data/senders/entry/throttle-time"/>
								</xsl:if>
								<xsl:if test="not(/data/fields) and not(/data/senders/entry/throttle-time)">
									<xsl:text>10</xsl:text>
								</xsl:if>
							</xsl:attribute>
						</input>
					</label>
					<p class="help">The time reserved for each batch. Do not(!) set this value higher than the timeout value of php.</p>
				</div>
			</div>
		</fieldset>
		<div class="actions">
			<input type="submit" accesskey="s" name="action[save]">
				<xsl:attribute name="value">
					<xsl:choose>
						<xsl:when test="/data/senders/entry/name">Save Changes</xsl:when>
						<xsl:otherwise>Create Sender</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
			</input>
			<xsl:if test="not(/data/context/item[@index=1] = 'new')" >
				<button name="action[delete]" class="button confirm delete" title="Delete this page" accesskey="d">Delete</button>
			</xsl:if>
		</div>
	</form>
</xsl:template>
</xsl:stylesheet>