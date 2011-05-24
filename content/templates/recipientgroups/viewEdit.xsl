<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<h2>	
		<span><xsl:choose><xsl:when test="/data/recipientgroup/entry/name"><xsl:value-of select="/data/recipientgroup/entry/name" /></xsl:when><xsl:otherwise>New Recipient Group</xsl:otherwise></xsl:choose></span>
	</h2>
	<form method="POST">
		<fieldset class="settings">
			<legend>Essentials</legend>
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
							<xsl:if test="not(/data/fields) and /data/recipientgroup/entry/name">
								<xsl:value-of select="/data/recipientgroup/entry/name"/>
							</xsl:if>
						</xsl:attribute>
					</input>
				</label>
				<xsl:if test="/data/errors/name">
					<p><xsl:value-of select="/data/errors/name"/></p>
				</xsl:if>
			</div>
		</fieldset>
		<fieldset class="settings recipients-select">
			<legend>Recipients</legend>
			<p class="label">Get recipients from</p>
			<ol id="duplicator" class="duplicator collapsible">
				<xsl:call-template name="duplicator"/>
			</ol>
		</fieldset>
		<div class="actions">
			<input type="submit" accesskey="s" name="action[save]">
				<xsl:attribute name="value">
					<xsl:choose>
						<xsl:when test="/data/recipientgroup/entry/name">Save Changes</xsl:when>
						<xsl:otherwise>Create Recipient Group</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
			</input>
			<xsl:if test="not(/data/context/item[@index=1] = 'new')" >
				<button name="action[delete]" class="button confirm delete" title="Delete this page" accesskey="d">Delete</button>
			</xsl:if>
		</div>
	</form>
</xsl:template>

<xsl:template name="duplicator">
	<li class="template field-section" data-type="section">
		<h4>Section</h4>
		<div class="content">
			<div>
				<label class="meta">
					Section
					<select id="context" name="fields[recipients][-1][section]">
						<optgroup label="Sections">
							<option value="7">Recipients</option>
						</optgroup>
						<optgroup label="System">
							<option value="system:authors">Authors</option>
						</optgroup>
					</select>
				</label>
			</div>
			<div class="group">
				<div>
					<label class="meta">
						Email field
						<select name="fields[recipients][-1][email]" class="filtered">
							<optgroup label="Authors">
								<option value="system:id">System ID</option>
								<option value="username">Username</option>
								<option value="first-name">First Name</option>
								<option value="last-name">Last Name</option>
								<option value="email">Email</option>
								<option value="status">Status</option>
							</optgroup>
							<optgroup label="Recipients">
								<option value="system:id">System ID</option>
								<option value="system:date">System Date</option>
								<option value="email">email</option>
							</optgroup>
						</select>
					</label>
				</div>
				<div>
					<label class="meta">
						Name field
						<i>Optional</i>
						<select name="fields[recipients][-1][name]" class="filtered">
							<optgroup label="Authors">
								<option value="system:id">System ID</option>
								<option value="username">Username</option>
								<option value="first-name">First Name</option>
								<option value="last-name">Last Name</option>
								<option value="email">Email</option>
								<option value="status">Status</option>
							</optgroup>
							<optgroup label="Recipients">
								<option value="system:id">System ID</option>
								<option value="system:date">System Date</option>
								<option value="email">email</option>
							</optgroup>
						</select>
					</label>
				</div>
			</div>
			<div>
				<div class="contextual 7">
					<p class="label">Filter Recipients by</p>
					<ol class="filters-duplicator">
						<li class="unique template" data-type="id">
							<h4>System ID</h4>
							<label>Value
								<input name="fields[filter][7][id]" type="text" />
							</label>
						</li>
						<li class="unique template" data-type="system:date">
							<h4>System Date</h4>
							<label>Value
								<input name="fields[filter][7][system:date]" type="text" />
							</label>
						</li>
						<li class="unique template" data-type="email">
							<h4>email <i>Text Input</i></h4>
							<label>Value
								<input name="fields[filter][7][14]" type="text" />
							</label>
						</li>
					</ol>
				</div>
			</div>
			<div>
				<label>
					Required parameters
					<i>Optional</i>
					<input type="text" name="fields[name]"></input>
				</label>
				<p class="help">An empty result will be returned when this parameter does not have a value. Do not wrap the parameter with curly-braces.</p>
			</div>
		</div>
	</li>
</xsl:template>

</xsl:stylesheet>