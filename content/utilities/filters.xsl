<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

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
						<label>
							<xsl:text>Value</xsl:text>
							<input name="fields[filter][author][id]" type="text" />
						</label>
					</li>
					<li class="unique template" data-type="username">
						<h4>Username</h4>
						<label>
							<xsl:text>Value</xsl:text>
							<input name="fields[filter][author][username]" type="text" />
						</label>
					</li>
					<li class="unique template" data-type="first_name">
						<h4>First Name</h4>
						<label>
							<xsl:text>Value</xsl:text>
							<input name="fields[filter][author][first_name]" type="text" />
						</label>
					</li>
					<li class="unique template" data-type="last_name">
						<h4>Last Name</h4>
						<label>
							<xsl:text>Value</xsl:text>
							<input name="fields[filter][author][last_name]" type="text" />
						</label>
					</li>
					<li class="unique template" data-type="email">
						<h4>Email</h4>
						<label>
							<xsl:text>Value</xsl:text>
							<input name="fields[filter][author][email]" type="text" />
						</label>
					</li>
					<li class="unique template" data-type="user_type">
						<h4>User Type</h4>
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
					<p class="label">Filter <xsl:value-of select="name"/> by</p>
					<ol class="filters-duplicator">
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
							<h4>System ID</h4>
							<label>
								<xsl:text>Value</xsl:text>
								<input name="fields[filter][{id}][id]" type="text"/>
							</label>
						</li>
						<li class="unique template" data-type="system:date">
							<h4>System Date</h4>
							<label>
								<xsl:text>Value</xsl:text>
								<input name="fields[filter][{id}][system:date]" type="text" />
							</label>
						</li>
						<xsl:for-each select="field">
							<li class="unique template" data-type="{type}">
								<xsl:copy-of select="filter_html/node()"/>
							</li>
						</xsl:for-each>
					</ol>
				</div>
			</xsl:for-each>
		</div>
	</fieldset>
</xsl:template>

</xsl:stylesheet>