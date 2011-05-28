<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xhtml="http://www.w3.org/1999/xhtml">

<xsl:template name="filters">
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
</xsl:template>

</xsl:stylesheet>