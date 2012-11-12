
<table class="form-table" cellspacing="2" cellpadding="5" style="width: 100%;">
	<tbody>
		<tr class="form-field">
			<th valign="top" scope="row">
				<label for="intlink_a">
					Connection
				</label>
			</th>
			<td>
				<select name="_a" id="intlink_a" onChange="updatepages(this.options[this.selectedIndex].value)">
					<option value="-1">- Select a Connection -</option>
					<?php foreach ( $cnxnoptns as $cnxn ) : ?>
						<option value="<?php echo $cnxn['id']; ?>"<?php if ( $cnxn['id'] == $data['_a'] ) : ?> selected<?php endif; ?>>
						<?php echo $cnxn['name']; ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th valign="top" scope="row">
				<label for="intlink_page">
					Page
				</label>
			</th>
			<td>
				<select name="page" id="intlink_page">
					<option value="0">- Select A Page -</option>
				</select>
			</td>
		</tr>
		<tr class="form-field">
			<th valign="top" scope="row">
				<label for="intlink_vars">
					Additional Variables
				</label>
			</th>
			<td>
				<input type="text" value="<?php echo $data['vars']; ?>" name="vars" size="20" />
			</td>
		</tr>
	</tbody>
</table>

<script type="text/javascript">
var cnxnlist = document.getElementById( 'intlink_a' );

var pages = new Array()
pages[0] = ["- Select A Page -|"]
<?php echo $pageoptns; ?>

function updatepages( selectedpagegroup, selectedpage )
{
	var pagelist = document.getElementById('intlink_page');
	pagelist.options.length=0
	
	for ( i=0; i < pages[selectedpagegroup].length; i++ ) {
		var value = pages[selectedpagegroup][i].split("|")[1]
		
		if ( value == selectedpage ) {
			pagelist.options[pagelist.options.length] = new Option( pages[selectedpagegroup][i].split("|")[0], value, true )
		}
		else { 
			pagelist.options[pagelist.options.length] = new Option( pages[selectedpagegroup][i].split("|")[0], value )
		}
		
	}
}

var tmp = document.getElementById( 'intlink_a' );
updatepages( tmp.options[tmp.selectedIndex].value, '<?php echo $data['page']; ?>' );

</script>