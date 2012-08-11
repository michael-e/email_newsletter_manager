<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template name="essentials">
	<fieldset class="settings">
		<legend>Essentials</legend>
		<div class="two columns">
			<div class="column">
				<xsl:if test="/data/errors/name">
					<xsl:attribute name="class">
						<xsl:text>invalid</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<label>
					<xsl:text>Name</xsl:text>
					<input type="text" name="fields[name]">
						<xsl:attribute name="value">
							<xsl:choose>
								<xsl:when test="/data/post-fields">
									<xsl:value-of select="/data/post-fields/name"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="/data/recipientgroups/entry/name"/>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:attribute>
					</input>
				</label>
				<xsl:if test="/data/errors/name">
					<p><xsl:value-of select="/data/errors/name"/></p>
				</xsl:if>
			</div>
			<div class="column">
				<label>
					<xsl:text>Source</xsl:text>
					<select id="ds-context" name="fields[source]">
						<optgroup label="Sections">
							<xsl:for-each select="/data/sections/entry">
								<option value="{id}">
									<xsl:choose>
										<xsl:when test="/data/post-fields">
											<xsl:if test="/data/post-fields/source = current()/id">
												<xsl:attribute name="selected">
													<xsl:text>selected</xsl:text>
												</xsl:attribute>
											</xsl:if>
										</xsl:when>
										<xsl:otherwise>
											<xsl:if test="/data/recipientgroups/entry/source = current()/id">
												<xsl:attribute name="selected">
													<xsl:text>selected</xsl:text>
												</xsl:attribute>
											</xsl:if>
										</xsl:otherwise>
									</xsl:choose>
									<xsl:value-of select="name"/>
								</option>
							</xsl:for-each>
						</optgroup>
						<optgroup label="System">
							<option value="authors">
								<xsl:choose>
									<xsl:when test="/data/post-fields">
										<xsl:if test="/data/post-fields/source = 'authors'">
											<xsl:attribute name="selected">
												<xsl:text>selected</xsl:text>
											</xsl:attribute>
										</xsl:if>
									</xsl:when>
									<xsl:otherwise>
										<xsl:if test="/data/recipientgroups/entry/source = 'authors'">
											<xsl:attribute name="selected">
												<xsl:text>selected</xsl:text>
											</xsl:attribute>
										</xsl:if>
									</xsl:otherwise>
								</xsl:choose>
								Authors
							</option>
						</optgroup>
						<optgroup label="Static">
							<option value="static_recipients">
								<xsl:choose>
									<xsl:when test="/data/post-fields">
										<xsl:if test="/data/post-fields/source = 'static_recipients'">
											<xsl:attribute name="selected">
												<xsl:text>selected</xsl:text>
											</xsl:attribute>
										</xsl:if>
									</xsl:when>
									<xsl:otherwise>
										<xsl:if test="/data/recipientgroups/entry/source = 'static_recipients'">
											<xsl:attribute name="selected">
												<xsl:text>selected</xsl:text>
											</xsl:attribute>
										</xsl:if>
									</xsl:otherwise>
								</xsl:choose>
								Static Recipients
							</option>
						</optgroup>
					</select>
				</label>
			</div>
		</div>
	</fieldset>
</xsl:template>

</xsl:stylesheet>