<?php

get_header();

?>

<div id="primary">
	<div id="content" role="main">
		<h2 class="entry-title">Integrator Configuration Error</h2>
		<p>An error was returned by Integrator 3 that requires the attention of the site administrator:<br/>
		<span style="width: 50px; float: left; display: block; ">&nbsp;</span><strong><?php echo $data['message']; ?></strong></p>
	</div>
</div>

<?php
get_footer();
?>