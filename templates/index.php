<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>Skvara import settings</h2>

	<?php if (!empty($tpl["imported"])): ?>
	<div id="message" class="updated">
		<p><strong>Articles imported:</strong></p>
		<ul>
			<?php foreach ($tpl["imported"] as $imported): ?>
				<li><?php echo htmlspecialchars($imported) ?></li>
			<?php endforeach ?>
		</ul>
	</div>
	<?php elseif(isset($tpl["imported"])): ?>
	<div id="message" class="updated">
		<p><strong>No new articles.</strong></p>
	</div>
	<?php endif ?>

	<?php if (isset($tpl["exception"])): ?>
	<div id="message" class="error">
		<p><strong>Exception:</strong> <?php echo htmlspecialchars($tpl["exception"]) ?></p>
	</div>
	<?php endif ?>

	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	
		<!-- <table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="url">Url</label></th>
				<td><input name="url" type="text" id="url" value="http://" class="regular-text code" />
				<span class="description">URL adress of the site you want to import.</span></td>
			</tr>
		</table>-->

		<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Import articles"/></p>
	</form>

</div>