<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template name="fields">
	<fieldset class="settings contextual sections Sections">
		<legend>Fields</legend>
		<p class="help">From the section, select the fields that are storing your Name and Email information.</p>
		<div>
			<div>
				<label>
					<xsl:text>Email</xsl:text>
					<select name="fields[email-field]" class="filtered">
						<xsl:for-each select="/data/sections/entry">
							<optgroup label="{name}">
								<xsl:for-each select="field/elements">
									<option value="{item}">
										<xsl:choose>
											<xsl:when test="/data/post-fields">
												<xsl:if test="/data/post-fields/email-field = item">
													<xsl:attribute name="selected">selected</xsl:attribute>
												</xsl:if>
											</xsl:when>
											<xsl:otherwise>
												<xsl:if test="/data/recipientgroups/entry/fields/email = item">
													<xsl:attribute name="selected">selected</xsl:attribute>
												</xsl:if>
											</xsl:otherwise>
											</xsl:choose>
										<xsl:value-of select="item"/>
									</option>
								</xsl:for-each>
							</optgroup>
						</xsl:for-each>
					</select>
				</label>
			</div>
			<div class="group">
				<div>
					<label>
					<xsl:text>Name Field(s)</xsl:text>
					<i>Optional</i>
						<select name="fields[name-fields][]" multiple="multiple" class="filtered">
							<option value="0">
								<xsl:text> </xsl:text>
							</option>
							<xsl:for-each select="/data/sections/entry">
								<optgroup label="{name}">
									<xsl:for-each select="field/elements">
										<option value="{item}">
											<xsl:choose>
												<xsl:when test="/data/post-fields">
													<xsl:if test="/data/post-fields/name-fields/item = item">
														<xsl:attribute name="selected">selected</xsl:attribute>
													</xsl:if>
												</xsl:when>
												<xsl:otherwise>
													<xsl:if test="/data/recipientgroups/entry/fields/name/fields/item = item">
														<xsl:attribute name="selected">selected</xsl:attribute>
													</xsl:if>
												</xsl:otherwise>
											</xsl:choose>
											<xsl:value-of select="item"/>
										</option>
									</xsl:for-each>
								</optgroup>
							</xsl:for-each>
						</select>
					</label>
				</div>
				<div>
					<xsl:if test="/data/errors/name-xslt">
						<xsl:attribute name="class">
							<xsl:text>invalid</xsl:text>
						</xsl:attribute>
					</xsl:if>
					<label>
						<xsl:text>Name XSLT</xsl:text>
						<i>optional</i>
						<textarea class="code" name="fields[name-xslt]" rows="11">
							<xsl:variable name="name-xslt">
								<xsl:choose>
									<xsl:when test="/data/context/item[@index=1] = 'new' or (not(/data/post-fields/name-xslt) and not(/data/recipientgroups/entry/fields/name/xslt))">
<xsl:text><![CDATA[<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="text" />

<xsl:template match="/entry">
	<xsl:value-of select="name"/>
</xsl:template>

</xsl:stylesheet>]]></xsl:text>
									</xsl:when>
									<xsl:when test="/data/post-fields/name-xslt">
										<xsl:value-of select="/data/post-fields/name-xslt"/>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="data/recipientgroups/entry/fields/name/xslt"/>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:variable>
							<xsl:value-of select="$name-xslt"/>
							<!-- prevent output of self-closing textarea element -->
							<xsl:if test="$name-xslt = ''">
								<xsl:text>&#010;</xsl:text>
							</xsl:if>
						</textarea>
					</label>
					<xsl:if test="/data/errors/name-xslt">
						<p><xsl:value-of select="/data/errors/name-xslt"/></p>
					</xsl:if>
				</div>
			</div>
		</div>
	</fieldset>
</xsl:template>

</xsl:stylesheet>