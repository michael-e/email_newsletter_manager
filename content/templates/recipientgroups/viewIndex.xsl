<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<h2>
		<span>Newsletter Recipient Groups</span>
		<a href="{concat($root, '/symphony/extension/email_newsletter_manager/recipientgroups/new')}" class="create button">Create New</a>
	</h2>
	<form method="post" action="{$current-url}">
		<table class="selectable">
			<thead>
				<tr>
					<th scope="col">Name</th>
					<th scope="col">Unique Recipients</th>
					<th scope="col">Preview</th>
				</tr>
			</thead>
			<tbody>
				<xsl:if test="/data/recipientgroups/entry">
					<xsl:apply-templates select="/data/recipientgroups/entry"/>
				</xsl:if>
				<xsl:if test="not(/data/recipientgroups/entry)">
					<tr>
						<td class="inactive" colspan="3">
							<xsl:text>None found</xsl:text>
						</td>
					</tr>
				</xsl:if>
			</tbody>
		</table>
		<div class="actions">
			<select name="with-selected">
				<option value="">With Selected...</option>
				<option class="confirm" value="delete">Delete</option>
			</select>
			<input type="submit" value="Apply" name="action[apply]" />
		</div>
	</form>
</xsl:template>

<xsl:template match="recipientgroups/entry">
	<tr>
		<td>
			<a href="{concat($root, '/symphony/extension/email_newsletter_manager/recipientgroups/edit/', handle)}"><xsl:value-of select="name"/></a>
			<input name="items[{handle}]" type="checkbox" />
		</td>
		<td>
			<xsl:if test="not(count)"><xsl:text>0</xsl:text></xsl:if>
			<xsl:value-of select="count"/>
		</td>
		<td>
			<a href="{concat($root, '/symphony/extension/email_newsletter_manager/recipientgroups/preview/', handle)}">Preview</a>
		</td>
	</tr>
</xsl:template>
</xsl:stylesheet>