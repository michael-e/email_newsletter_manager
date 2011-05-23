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
			<div>
				<xsl:if test="/data/errors/recipients">
					<xsl:attribute name="class">
						<xsl:text>invalid</xsl:text>
					</xsl:attribute>
				</xsl:if>
				<label>
					Recipients
					<input type="text" name="fields[recipients]">
						<xsl:attribute name="value">
							<xsl:if test="/data/fields">
								<xsl:value-of select="/data/fields/recipients"/>
							</xsl:if>
							<xsl:if test="not(/data/fields) and /data/recipientgroup/entry/recipients">
								<xsl:value-of select="/data/recipientgroup/entry/recipients"/>
							</xsl:if>
						</xsl:attribute>
					</input>
				</label>
				<xsl:if test="not(/data/errors/recipients)">
					<p class="help">Select multiple recipients by seperating them with commas. This is also possible dynamically: {/data/authors/name} &lt;{/data/authors/email}&gt; will return: name &lt;email@domain.com&gt;, name2 &lt;email2@domain.com&gt;</p>
				</xsl:if>
				<xsl:if test="/data/errors/recipients">
					<p><xsl:value-of select="/data/errors/recipients"/></p>
				</xsl:if>
			</div>
		</fieldset>
		<fieldset class="settings params-select">
			<legend>Parameters</legend>
			<p class="help">
				To make it easier to define your recipient groups, you can set parameters to filter your recipients datasource on.
				The EN will use this parameter only when it loads this group, so it is possible to use the same datasource for more than one group.
				Do not forget to filter your datasource on the parameter.
			</p>
			<p class="label">Params</p>
			<ol id="duplicator" class="duplicator collapsible">
				<xsl:call-template name="duplicator"/>
			</ol>
		</fieldset>
		<div class="actions">
			<input type="submit" accesskey="s" name="action[save]">
				<xsl:attribute name="value">
					<xsl:choose>
						<xsl:when test="/data/senders/entry/name">Save Changes</xsl:when>
						<xsl:otherwise>Create Sender</xsl:otherwise>
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
	<xsl:apply-templates select="/data/recipientgroup/entry/params/item" />
	<li class="template field-param" data-type="param">
		<h4>Parameter</h4>
		<div class="content group">
			<div>
				<label class="meta">
					Name
					<input type="text" name="fields[params][-1][name]"></input>
				</label>
				<p class="help">You can use the param by this name in the datasource editor.</p>
			</div>
			<div>
				<label>
					Value
					<input type="text" name="fields[params][-1][value]"></input>
				</label>
				<p class="help">The parameter value can <strong>not</strong> be dynamic.</p>
			</div>
		</div>
	</li>
</xsl:template>

<xsl:template match="params/item">
	<li class="field-param" data-type="param">
	<h4>Parameter</h4>
	<div class="content group">
		<div>
			<label class="meta">
				Name
				<input type="text" name="fields[params][-1][name]" value="{name}"></input>
			</label>
			<p class="help">You can use the param by this name in the datasource editor.</p>
		</div>
		<div>
			<label>
				Value
				<input type="text" name="fields[params][-1][value]" value="{value}"></input>
			</label>
			<p class="help">The parameter value can <strong>not</strong> be dynamic.</p>
			<input type="hidden" name="fields[params][-1][id]" value="{id}" />
		</div>
	</div>
</li>
</xsl:template>

</xsl:stylesheet>