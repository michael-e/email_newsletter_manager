<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	xmlns:xhtml="http://www.w3.org/1999/xhtml">

<xsl:template name="static-recipients">
	<fieldset class="settings contextual static_recipients">
		<legend>Recipients</legend>
		<p class="help">Enter your recipients in the following format: <code>Name &lt;email&gt;</code> or <code>&quot;Name&quot; &lt;email&gt;</code>.</p>
		<div>
			<label>Recipients
				<textarea class="code" name="fields[static_recipients]" rows="12" cols="50"></textarea>
			</label>
		</div>
	</fieldset>
</xsl:template>

</xsl:stylesheet>