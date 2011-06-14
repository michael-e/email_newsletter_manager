<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template name="essentials">
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
					<xsl:text>Name</xsl:text>
					<input type="text" name="fields[name]">
						<!--
							TODO add postback value
						-->
						<xsl:attribute name="value">
							<xsl:if test="/data/fields">
								<xsl:value-of select="/data/fields/name"/>
							</xsl:if>
							<xsl:if test="not(/data/fields) and /data/recipientgroups/entry/name">
								<xsl:value-of select="/data/recipientgroups/entry/name"/>
							</xsl:if>
						</xsl:attribute>
					</input>
				</label>
				<xsl:if test="/data/errors/name">
					<p><xsl:value-of select="/data/errors/name"/></p>
				</xsl:if>
			</div>
			<div>
				<label>
					<xsl:text>Source</xsl:text>
					<select id="context" name="fields[source]">
						<optgroup label="Sections">
							<xsl:for-each select="/data/sections/entry">
								<option value="{id}">
									<xsl:if test="/data/recipientgroups/entry/source = current()/id">
										<xsl:attribute name="selected">
											<xsl:text>yes</xsl:text>
										</xsl:attribute>
									</xsl:if>
									<xsl:value-of select="name"/>
								</option>
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
</xsl:template>

</xsl:stylesheet>