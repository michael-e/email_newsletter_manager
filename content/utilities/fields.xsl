<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template name="fields">
	<fieldset class="settings contextual sections Sections">
		<legend>Fields</legend>
		<p class="help">From the section, select the fields that are storing your Name and Email information.</p>
		<div>
			<xsl:for-each select="/data/sections/entry">
				<div class="contextual {id}">
					<div>
						<label>
							<xsl:text>Email</xsl:text>
							<select name="fields[email]">
								<xsl:for-each select="field/elements">
									<option value="{item}"><xsl:value-of select="item"/></option>
								</xsl:for-each>
							</select>
						</label>
					</div>
					<div class="group">
						<div>
							<label>
							<xsl:text>Name Field(s)</xsl:text>
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
								<xsl:text>Name XSLT</xsl:text>
								<i>optional</i>
								<textarea class="code" name="fields[name-xslt]" rows="10" style="height:9.166em">
									<xsl:text>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"&gt;

&lt;xsl:template match="/"&gt;
	&lt;xsl:value-of select="."/&gt;
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
</xsl:template>

</xsl:stylesheet>