<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<h2>
		<span>Email Senders</span>
		<a href="{concat($root, '/symphony/extension/email_newsletters/senders/new')}" class="create button">Create New</a>
	</h2>
	<form method="post" action="{$current-url}">
		<table class="selectable">
			<thead>
				<tr>
					<th scope="col">Name</th>
					<th scope="col">Email</th>
					<th scope="col">Reply-To Name</th>
					<th scope="col">Reply-To Email Address</th>
				</tr>
			</thead>
			<tbody>
				<xsl:if test="/data/senders/entry">
					<xsl:apply-templates select="/data/senders/entry"/>
				</xsl:if>
				<xsl:if test="not(/data/senders/entry)">
					<tr>
						<td class="inactive" colspan="4">
							None found
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

<xsl:template match="senders/entry">
	<tr>
		<td>
			<a href="{concat($root, '/symphony/extension/email_newsletters/senders/edit/', id)}"><xsl:value-of select="name"/></a>
			<input name="items[{id}]" type="checkbox" />
		</td>
		<td>
			<xsl:value-of select="email"/>
		</td>
		<td>
			<xsl:if test="/data/senders/entry/reply-to-name">
				<xsl:value-of select="reply-to-name"/>
			</xsl:if>
			<xsl:if test="not(/data/senders/entry/reply-to-name)">
				<xsl:attribute name="class">
					<xsl:text>inactive</xsl:text>
				</xsl:attribute>
				Use Default
			</xsl:if>
		</td>
		<td>
			<xsl:if test="/data/senders/entry/reply-to-email">
				<xsl:value-of select="reply-to-email"/>
			</xsl:if>
			<xsl:if test="not(/data/senders/entry/reply-to-email)">
				<xsl:attribute name="class">
					<xsl:text>inactive</xsl:text>
				</xsl:attribute>
				Use Default
			</xsl:if>
		</td>
	</tr>
</xsl:template>
</xsl:stylesheet>