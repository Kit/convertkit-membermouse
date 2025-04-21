<h1>Filters</h1><table>
				<thead>
					<tr>
						<th>File</th>
						<th>Filter Name</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody><tr>
						<td colspan="3">../includes/class-convertkit-mm-settings.php</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_settings_get_defaults"><code>convertkit_settings_get_defaults</code></a></td>
						<td>The default settings, used when the ConvertKit Plugin Settings haven't been saved e.g. on a new installation.</td>
					</tr>
					</tbody>
				</table><h3 id="convertkit_settings_get_defaults">
						convertkit_settings_get_defaults
						<code>includes/class-convertkit-mm-settings.php::387</code>
					</h3><h4>Overview</h4>
						<p>The default settings, used when the ConvertKit Plugin Settings haven't been saved e.g. on a new installation.</p><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody><tr>
							<td>$defaults</td>
							<td>array</td>
							<td>Default Settings.</td>
						</tr>
						</tbody>
					</table><h4>Usage</h4>
<pre>
add_filter( 'convertkit_settings_get_defaults', function( $defaults ) {
	// ... your code here
	// Return value
	return $defaults;
}, 10, 1 );
</pre>
<h1>Actions</h1><table>
				<thead>
					<tr>
						<th>File</th>
						<th>Filter Name</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody><tr>
						<td colspan="3">../includes/class-convertkit-mm.php</td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_membermouse_initialize_admin"><code>convertkit_membermouse_initialize_admin</code></a></td>
						<td></td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_membermouse_initialize_frontend"><code>convertkit_membermouse_initialize_frontend</code></a></td>
						<td></td>
					</tr><tr>
						<td>&nbsp;</td>
						<td><a href="#convertkit_membermouse_initialize_global"><code>convertkit_membermouse_initialize_global</code></a></td>
						<td></td>
					</tr>
					</tbody>
				</table><h3 id="convertkit_membermouse_initialize_admin">
						convertkit_membermouse_initialize_admin
						<code>includes/class-convertkit-mm.php::94</code>
					</h3><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table><h4>Usage</h4>
<pre>
do_action( 'convertkit_membermouse_initialize_admin', function(  ) {
	// ... your code here
}, 10, 0 );
</pre>
<h3 id="convertkit_membermouse_initialize_frontend">
						convertkit_membermouse_initialize_frontend
						<code>includes/class-convertkit-mm.php::115</code>
					</h3><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table><h4>Usage</h4>
<pre>
do_action( 'convertkit_membermouse_initialize_frontend', function(  ) {
	// ... your code here
}, 10, 0 );
</pre>
<h3 id="convertkit_membermouse_initialize_global">
						convertkit_membermouse_initialize_global
						<code>includes/class-convertkit-mm.php::134</code>
					</h3><h4>Parameters</h4>
					<table>
						<thead>
							<tr>
								<th>Parameter</th>
								<th>Type</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table><h4>Usage</h4>
<pre>
do_action( 'convertkit_membermouse_initialize_global', function(  ) {
	// ... your code here
}, 10, 0 );
</pre>
