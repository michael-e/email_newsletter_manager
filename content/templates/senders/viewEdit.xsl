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
	<form method="POST">
		<fieldset class="settings">
			<legend>Sender Properties</legend>
			<div class="group">
				<div>
					<xsl:if test="/data/errors/name">
						<xsl:attribute name="class">
							<xsl:text>invalid</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<label>
						Name
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
				<div>
					<xsl:if test="/data/errors/email">
						<xsl:attribute name="class">
							<xsl:text>invalid</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<label>
						Email
						<input type="text" name="fields[email]">
							<xsl:attribute name="value">
								<xsl:if test="/data/fields">
									<xsl:value-of select="/data/fields/email"/>
								</xsl:if>
								<xsl:if test="not(/data/fields) and /data/senders/entry/email">
									<xsl:value-of select="/data/senders/entry/email"/>
								</xsl:if>
							</xsl:attribute>
						</input>
					</label>
					<xsl:if test="/data/errors/email">
						<p><xsl:value-of select="/data/errors/email"/></p>
					</xsl:if>
				</div>
			</div>
			<div class="group">
				<div>
					<label>
						Reply-To Name <i>optional</i>
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
					<p class="help">If Reply-To Name or Reply-To Email is left empty, the defaults from the preferences page will be used.</p>
				</div>
				<div>
					<label>
						Reply-To Email <i>optional</i>
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