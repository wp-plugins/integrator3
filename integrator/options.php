<div class="wrap">
	<h1>
		Integrator Settings
	</h1>
	
	<div id="message" class="<?php echo ( $status === true ? 'updated' : 'error' ); ?> fade">
		
		<?php if ( isset( $_POST['integratorUpdate'] ) ): ?>
			
			<p>
				<?php _e( 'Settings have been saved.' ) ?>
			</p>
			
		<?php endif; ?>
		
		<p>
			<?php echo ( $status === true ? __( 'Your settings are valid.' ) : sprintf( __( 'There is a problem with your settings: %s' ), $status ) ); ?>
		</p>
		
	</div>
	
	<form method="post">
	
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="integrator_url">
						Integrator URL:
					</label>
				</th>
				<td>
					
					<input id="integrator_url" name="integrator_url" type="text" value="<?php echo $integrator_url; ?>" size="100" />
					
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="integrator_apiusername">
						API Username:
					</label>
				</th>
				<td>
					
					<input id="integrator_apiusername" name="integrator_apiusername" type="text" value="<?php echo $integrator_apiusername; ?>" size="100" />
					
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="integrator_apipassword">
						API Password:
					</label>
				</th>
				<td>
					
					<input id="integrator_apipassword" name="integrator_apipassword" type="password" value="<?php echo $integrator_apipassword; ?>" size="100" />
					
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="integrator_apisecret">
						API Secret Key:
					</label>
				</th>
				<td>
					
					<input id="integrator_apisecret" name="integrator_apisecret" type="text" value="<?php echo $integrator_apisecret; ?>" size="100" />
					
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="integrator_cnxnid">
						cnxnid:
					</label>
				</th>
				<td>
					
					<input id="integrator_cnxnid" name="integrator_cnxnid" type="text" value="<?php echo $integrator_cnxnid; ?>" size="10" />
					
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					
					<input id="integratorUpdate" name="integratorUpdate" type='submit' value="Save Settings" class="button-primary" />
					
				</td>
			</tr>
			
		</table>
		
	</form>
	
</div>