<div class="wrap">
	<h1>Facebook to WordPress Posts</h1>

	<?php settings_errors(FBWPP_OPTIONS_GROUP); ?>

	<form method="post" action="options.php">
		<?php settings_fields(FBWPP_OPTIONS_GROUP); ?>

		<table class="form-table">
			<tbody>
				<tr>
					<th>Facebook App ID</th>
					<td>
						<input name="<?= FBWPP_OPTIONS_SLUG; ?>[app_id]" type="text" value="<?= $options['app_id']; ?>" class="regular-text" required>
					</td>
				</tr>

				<tr>
					<th>Facebook App Secret</th>
					<td>
						<input name="<?= FBWPP_OPTIONS_SLUG; ?>[app_secret]" type="password" value="<?= $options['app_secret']; ?>" class="regular-text" required>
					</td>
				</tr>

                <tr>
                    <th>Facebook Access Token</th>
                    <td>
                        <input name="<?= FBWPP_OPTIONS_SLUG; ?>[access_token]" type="password" value="<?= $options['access_token']; ?>" class="regular-text" required>
                    </td>
                </tr>

				<tr>
					<th>Facebook Page ID</th>
					<td>
						<input name="<?= FBWPP_OPTIONS_SLUG; ?>[page_id]" type="text" value="<?= $options['page_id']; ?>" class="regular-text" required>
					</td>
				</tr>

			<?php if (!empty($post_types)) : ?>
				<tr>
					<th>Post Type</th>
					<td>
						<select name="<?= FBWPP_OPTIONS_SLUG; ?>[post_type]">
						<?php
							foreach ($post_types as $post_type) :
								if ($post_type->name == 'media') {
									continue;
								}

								$selected = ($options['post_type'] == $post_type->name) ? ' selected' : '';
						?>
							<option value="<?= $post_type->name; ?>"<?= $selected; ?>><?= $post_type->label; ?></option>
						<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Update">
		</p>
	</form>
</div>