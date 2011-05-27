<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="html"
	omit-xml-declaration="yes"
	encoding="UTF-8"
	indent="yes" />

<xsl:template match="/">
	<h2>
		<xsl:choose>
			<xsl:when test="/data/recipientgroup/entry/name">
				<span><xsl:value-of select="/data/recipientgroup/entry/name" /></span>
				<a href="#" class="button">Preview Recipients</a>
			</xsl:when>
			<xsl:otherwise><span>New Recipient Group</span></xsl:otherwise>
		</xsl:choose>
	</h2>
	<form method="POST">
		<fieldset class="settings">
			<legend>Essentials</legend>
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
				<div>
					<label>Source
						<select id="context" name="fields[source]">
							<optgroup label="Sections">
								<xsl:for-each select="/data/sections/entry">
									<option value="{id}"><xsl:value-of select="name"/></option>
								</xsl:for-each>
							</optgroup>
							<optgroup label="System">
								<option value="authors">Authors</option>
							</optgroup>
							<optgroup label="Static">
								<option value="static_recipients">Static Recipients</option>
							</optgroup>
						</select>
					</label>
				</div>
			</div>
		</fieldset>
		<fieldset class="settings contextual sections Sections authors System">
			<legend>Filter Results</legend>
			<p class="help">Use <code>{$param}</code> syntax to filter by page parameters.</p>
			<div>
				<div class="contextual authors">
					<p class="label">Filter Authors by</p>
					<ol class="filters-duplicator">
						<li class="unique template" data-type="id">
							<h4>ID</h4>
							<label>Value
								<input name="fields[filter][author][id]" type="text" />
							</label>
						</li>
						<li class="unique template" data-type="username">
							<h4>Username</h4>
							<label>Value
								<input name="fields[filter][author][username]" type="text" />
							</label>
						</li>
						<li class="unique template" data-type="first_name">
							<h4>First Name</h4>
							<label>Value
								<input name="fields[filter][author][first_name]" type="text" />
							</label>
						</li>
						<li class="unique template" data-type="last_name">
							<h4>Last Name</h4>
							<label>Value
								<input name="fields[filter][author][last_name]" type="text" />
							</label>
						</li>
						<li class="unique template" data-type="email">
							<h4>Email</h4>
							<label>Value
								<input name="fields[filter][author][email]" type="text" />
							</label>
						</li>
						<li class="unique template" data-type="user_type">
							<h4>User Type</h4>
							<label>Value
								<input name="fields[filter][author][user_type]" type="text" />
							</label>
						</li>
					</ol>
				</div>
				<xsl:for-each select="/data/sections/entry">
					<div class="contextual {id}">
						<p class="label">Filter <xsl:value-of select="name"/> by</p>
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
							<xsl:for-each select="field">
								<li class="unique template" data-type="{element-name}">
									<xsl:copy-of select="filter_html/node()"/>
								</li>
							</xsl:for-each>
						</ol>
					</div>
				</xsl:for-each>
			</div>
			<div>
				<label>Required URL Parameter
					<i>Optional</i>
					<input type="text" name="fields[required_url_param]" />
				</label>
				<p class="help">An empty result will be returned when this parameter does not have a value. Do not wrap the parameter with curly-braces.</p>
			</div>
		</fieldset>
		<fieldset class="settings contextual static_recipients">
			<legend>Recipients</legend>
			<p class="help">Enter your recipients in the following format: <code>Name &lt;email&gt;</code> or <code>&quot;Name&quot; &lt;email&gt;</code>.</p>
			<div>
				<label>Recipients
					<textarea class="code" name="fields[static_recipients]" rows="12" cols="50"></textarea>
				</label>
			</div>
		</fieldset>
		<fieldset class="settings contextual sections Sections">
			<legend>Fields</legend>
			<p class="help">From the section, select the fields that are storing your Name and Email information.</p>
			<div>
				<xsl:for-each select="/data/sections/entry">
					<div class="contextual {id}">
						<div>
							<label>Email
								<select name="fields[email]">
									<xsl:for-each select="field/elements">
										<option value="{item}"><xsl:value-of select="item"/></option>
									</xsl:for-each>
								</select>
							</label>
						</div>
						<div class="group">
							<div>
								<label>Name Field(s)
								<i>Optional</i>
									<select name="fields[name-fields][]" multiple="yes">
										<option value="0"></option>
										<xsl:for-each select="field/elements">
											<option value="{item}"><xsl:value-of select="item"/></option>
										</xsl:for-each>
									</select>
								</label>
							</div>
							<div>
								<label>
									Name XSLT
									<i>optional</i>
									<textarea class="code" name="fields[name-xslt]" rows="10" style="height:9.166em">
										<xsl:text>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform"&gt;

	&lt;xsl:template match="/"&gt;
		&lt;xsl:value-of select="/"/&gt;
	&lt;/xsl:template&gt;

&lt;/xsl:stylesheet&gt;</xsl:text>
									</textarea>
								</label>
							</div>
						</div>
					</div>
				</xsl:for-each>
			</div>
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
	<xsl:value-of select="'&lt;!--'" disable-output-escaping="yes"/>
	<xsl:copy-of select="/node()" />
	<xsl:value-of select="'--&gt;'" disable-output-escaping="yes"/>

</xsl:template>

</xsl:stylesheet>