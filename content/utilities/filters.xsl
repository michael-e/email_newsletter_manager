<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template name="filters">
	<fieldset class="settings contextual sections Sections authors System">
		<legend>Filter Results</legend>
		<p class="help">Use <code>{$param}</code> syntax to filter by parameters.</p>
		<div class="contextual authors">
			<ol class="filters-duplicator" data-add="Add filter" data-remove="Remove filter">
				<li class="unique template" data-type="id">
					<header><h4>ID</h4></header>
					<label>
						<xsl:text>Value</xsl:text>
						<input name="fields[filter][author][id]" type="text" />
					</label>
				</li>
				<li class="unique template" data-type="username">
					<header><h4>Username</h4></header>
					<label>
						<xsl:text>Value</xsl:text>
						<input name="fields[filter][author][username]" type="text" />
					</label>
				</li>
				<li class="unique template" data-type="first_name">
					<header><h4>First Name</h4></header>
					<label>
						<xsl:text>Value</xsl:text>
						<input name="fields[filter][author][first_name]" type="text" />
					</label>
				</li>
				<li class="unique template" data-type="last_name">
					<header><h4>Last Name</h4></header>
					<label>
						<xsl:text>Value</xsl:text>
						<input name="fields[filter][author][last_name]" type="text" />
					</label>
				</li>
				<li class="unique template" data-type="email">
					<header><h4>Email</h4></header>
					<label>
						<xsl:text>Value</xsl:text>
						<input name="fields[filter][author][email]" type="text" />
					</label>
				</li>
				<li class="unique template" data-type="user_type">
					<header><h4>User Type</h4></header>
					<label>
						<xsl:text>Value</xsl:text>
						<input name="fields[filter][author][user_type]" type="text" />
					</label>
				</li>
				<xsl:for-each select="/data/recipientgroups/entry/filters/entry[contains(label/input/@name, '[authors]')]">
					<li class="unique" data-type="{@data-type}">
						<xsl:copy-of select="*"/>
					</li>
				</xsl:for-each>
			</ol>
		</div>
		<xsl:for-each select="/data/sections/entry">
			<div class="contextual {id}">
				<ol class="filters-duplicator" data-add="Add filter" data-remove="Remove filter">
					<!--
						Checking for strings in @name attributes looks rather dangerous;
						unfortunately that is the only way to go with the current XML.
					-->
					<xsl:choose>
						<xsl:when test="/data/post-fields">
							<xsl:for-each select="/data/recipientgroups/entry/filters/entry[contains(label/input/@name, concat('[', current()/id,']'))]">
								<li class="unique" data-type="{@data-type}">
									<xsl:copy-of select="*"/>
								</li>
							</xsl:for-each>
						</xsl:when>
						<xsl:otherwise>
							<xsl:for-each select="/data/recipientgroups/entry/filters/entry[contains(label/input/@name, concat('[', current()/id,']'))]">
								<li class="unique" data-type="{@data-type}">
									<xsl:copy-of select="*"/>
								</li>
							</xsl:for-each>
						</xsl:otherwise>
					</xsl:choose>

					<li class="unique template" data-type="id">
						<header><h4>System ID</h4></header>
						<label>
							<xsl:text>Value</xsl:text>
							<input name="fields[filter][{id}][id]" type="text"/>
						</label>
					</li>
					<li class="unique template" data-type="system:date">
						<header><h4>System Date</h4></header>
						<label>
							<xsl:text>Value</xsl:text>
							<input name="fields[filter][{id}][system:date]" type="text" />
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
	</fieldset>
</xsl:template>

</xsl:stylesheet>