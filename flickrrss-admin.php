	<div class="wrap">
	<h2>flickrRSS Settings</h2>

	<form method="post">
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Display</th>
				<td>
					<select name="flickrRSS_num_items" id="flickrRSS_num_items">
						<?php for ($i=1; $i<=20; $i++) { ?>
							<option <?php if ($settings['num_items'] == $i) { echo 'selected'; } ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>	
						<?php } ?>
					</select>
					<select name="flickrRSS_type" id="flickrRSS_type">
						<option <?php if($settings['type'] == 'user') { echo 'selected'; } ?> value="user">user</option>
						<option <?php if($settings['type'] == 'set') { echo 'selected'; } ?> value="set">set</option>
						<option <?php if($settings['type'] == 'favorite') { echo 'selected'; } ?> value="favorite">favorite</option>
						<option <?php if($settings['type'] == 'group') { echo 'selected'; } ?> value="group">group</option>
						<option <?php if($settings['type'] == 'public') { echo 'selected'; } ?> value="public">community</option>
					</select>
					photos.
				</td> 
			</tr>
			<tr valign="top" id="userid">
				<th scope="row" id="userid_label">User or Group ID</th>
				<td><input name="flickrRSS_id" type="text" id="flickrRSS_id" value="<?php echo $settings['id']; ?>" size="20" />
					<a href="#" id="idgetter">Find your id</a></td>
			</tr>
			<tr valign="top" id="set">
				<th scope="row">Set ID</th>
				<td><input name="flickrRSS_set" type="text" id="flickrRSS_set" value="<?php echo $settings['set']; ?>" size="40" /> Use number from the set url</p>
			</tr>
			<tr valign="top" id="tags">
				<th scope="row">Tags (optional)</th>
				<td><input name="flickrRSS_tags" type="text" id="flickrRSS_tags" value="<?php echo $settings['tags']; ?>" size="40" /> Comma separated, no spaces</p>
			</tr>
			<tr valign="top">
				<th scope="row">HTML Builder</th>
				<td>
					<table style="margin-left: -10px">
						<tr>
							<td colspan="2" valign="top" style="border-width: 0px;">
								<label for="flickrRSS_before_list">Before List:</label><br/><input name="flickrRSS_before_list" type="text" id="flickrRSS_before_list" value="<?php echo htmlspecialchars(stripslashes($settings['before_list'])); ?>" style="width:400px;" />
							</td>
						</tr>
						<tr>
							<td valign="top" style="border-width: 0px;">
								<label for="flickrRSS_html">Item HTML:</label><br/> <textarea name="flickrRSS_html" type="text" id="flickrRSS_html" style="width:400px;" rows="10"><?php echo htmlspecialchars(stripslashes($settings['html'])); ?></textarea>
							</td>
							<td valign="top" style="border-width: 0px;">
								<div>
									<h4>"Item HTML" metatags:</h4>
									<ul>
										<li><code>%flickr_page%</code></li>
										<li><code>%title%</code></li>
										<li><code>%image_square%</code></li>
										<li><code>%image_small%</code></li>
										<li><code>%image_thumbnail%</code></li>
										<li><code>%image_medium%</code></li>
										<li><code>%image_large%</code></li>
									</ul>
								</div>
							</td>
						</tr>
						<tr>
							<td valign="top" colspan="2" style="border-width: 0px;">
								<label for="flickrRSS_after_list">After List:</label><br/> <input name="flickrRSS_after_list" type="text" id="flickrRSS_after_list" value="<?php echo htmlspecialchars(stripslashes($settings['after_list'])); ?>" style="width:400px;" />
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		<div class="submit">
			<input type="submit" name="reset_flickrRSS_settings" value="<?php _e('Reset') ?>" />
			<input type="submit" name="save_flickrRSS_settings" value="<?php _e('Save Settings') ?>" class="button-primary" />
		</div>
		<script>
			(function() {
				var $ = jQuery;
				$(document).ready(function(){
					function uiChange() {
						$("#set, #tags, #userid").hide();
						var sel = $("#flickrRSS_type").val();
						if (sel == "set") {
							$("#set").show();
						}
						if (sel.match(/(user|public)/)) {
							$("#tags").show();
						}
						if (sel.match(/(user|favorite|set|group)/)) {
							$("#userid").show();
							$("#userid_label").text(sel=="group"?"Group ID":"User ID");
						}
						$(".cachesettings")[ $("#flickrRSS_do_cache").attr("checked")?'show':'hide' ]();
					}
					$("#flickrRSS_type").change(uiChange);
					$("#flickrRSS_do_cache").change(uiChange);
					uiChange();
					
					$("#idgetter").click(function(event){
						var group = $("#flickrRSS_type").val()=="group";
						
						var x = prompt(
							group?"Enter here the URL of the Group pool:":"Enter here the URL of your Profile or photo pool:", 
							group?"http://flickr.com/groups/your_group/":"http://flickr.com/photos/your_username/"
						);
						if (!x) {
							return false;
						}
						var url = "http://api.flickr.com/services/rest/?"+
						"method="+(group?"flickr.urls.lookupGroup&":"flickr.urls.lookupUser&")+
						"api_key=bed56c11a80c6b68fa62f25ad393a94a&"+
						"format=json&"+
						"jsoncallback=?&"+
						"url="+x;
						$.getJSON(url,
							function(result) {
								if (result.stat != "ok") {
									alert("It seems that there is some kind of problem with the URL you provided. Please, try again.");
									return false;
								}
								$("#flickrRSS_id").val(group?result.group.id:result.user.id);
							}
						);
						return false;
					});
				});
			})();
		</script>
	</form>
</div>
