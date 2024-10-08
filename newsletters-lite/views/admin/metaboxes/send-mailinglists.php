<?php // phpcs:ignoreFile ?>
<!-- Mailinglists/Subscribers Box -->

<?php

global $wpdb, $wp_roles;
$roles = $wp_roles -> get_names();
$count_users = count_users();

?>

<div id="mailingliststabs">
	<ul>
		<li><a href="#mailingliststabs-subscribers"><i class="fa fa-users fa-fw"></i> <?php esc_html_e('Subscribers', 'wp-mailinglist'); ?></a></li>
		<li><a href="#mailingliststabs-filter"><i class="fa fa-filter fa-fw"></i> <?php esc_html_e('Filter', 'wp-mailinglist'); ?></a></li>
	</ul>
	
	<!-- Groups, Roles and Mailing Lists -->
	<div id="mailingliststabs-subscribers">
		<div id="groupsdiv">
            <?php if ($groups = $this -> Group() -> select()) : ?>
                <div><label class="selectit" style="font-weight:bold;"><input type="checkbox" id="groupsselectall" name="groupsselectall" value="1" onclick="jqCheckAll(this, 'post', 'groups'); update_subscribers();" /> <?php esc_html_e('Select all Groups', 'wp-mailinglist'); ?></label></div>
                <div class="scroll-list">
                    <?php foreach ($groups as $group_id => $group_title) : ?>
                        <div><label class="selectit"><input onclick="update_subscribers();" <?php echo (!empty($_POST['groups']) && is_array($_POST['groups']) && in_array($group_id, $_POST['groups'])) ? 'checked="checked"' : ''; ?> type="checkbox" name="groups[]" id="checklist<?php echo esc_html( $group_id); ?>" value="<?php echo esc_attr($group_id); ?>" /> <?php echo esc_html($group_title); ?> (<?php echo esc_html( $Mailinglist -> count(array('group_id' => $group_id))); ?> <?php esc_html_e('lists', 'wp-mailinglist'); ?>)</label></div>
                    <?php endforeach; ?>
                </div>
                <br/>
            <?php else : ?>
            
            <?php endif; ?>
        </div>
    	<div id="listsdiv">
            <?php if ($mailinglists = $Mailinglist -> select(true)) : ?>
                <div><label class="selectit" style="font-weight:bold;"><input type="checkbox" id="mailinglistsselectall" name="mailinglistsselectall" value="1" onclick="jqCheckAll(this, 'post', 'mailinglists'); update_subscribers();" /> <?php esc_html_e('Select all Lists', 'wp-mailinglist'); ?></label></div>
                <div class="scroll-list">
                    <?php foreach ($mailinglists as $list_id => $list_title) : ?>
                        <div><label class="selectit"><input onclick="update_subscribers();" <?php echo (!empty($_POST['mailinglists']) && is_array($_POST['mailinglists']) && in_array($list_id, $_POST['mailinglists'])) ? 'checked="checked"' : ''; ?> type="checkbox" name="mailinglists[]" id="checklist<?php echo esc_attr($list_id); ?>" value="<?php echo esc_html( $list_id); ?>" /> <?php echo esc_html( $list_title); ?> (<?php echo esc_html( $SubscribersList -> count(array('list_id' => $list_id, 'active' => "Y"))); ?> <?php esc_html_e('active', 'wp-mailinglist'); ?>)</label></div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="newsletters_error"><?php esc_html_e('No lists are available', 'wp-mailinglist'); ?></p>
            <?php endif; ?>
        </div>
        <?php if (current_user_can('newsletters_admin_send_sendtoroles')) : ?>
	        <?php if (!empty($roles)) : ?>
	        	<br/>
	        	<div id="usersdiv">
	        		<div><label class="selectit" style="font-weight:bold;"><input type="checkbox" name="rolesselectall" value="1" id="rolesselectall" onclick="jqCheckAll(this, false, 'roles'); update_subscribers();" /> <?php esc_html_e('Select all Roles', 'wp-mailinglist'); ?></label></div>
	        		<div class="scroll-list">
	        			<?php foreach ($roles as $role_key => $role_name) : ?>
	        				<div><label class="selectit"><input onclick="update_subscribers();" <?php echo (!empty($_POST['roles']) && is_array($_POST['roles']) && in_array($role_key, $_POST['roles'])) ? 'checked="checked"' : ''; ?> type="checkbox" name="roles[]" value="<?php echo esc_attr($role_key); ?>" id="roles_<?php echo esc_html( $role_key); ?>" /> <?php echo esc_html($role_name); ?><?php echo (!empty($count_users['avail_roles'][$role_key])) ? ' (' . sprintf(__('%s users'), $count_users['avail_roles'][$role_key]) . ')' : ''; ?></label></div>
	        			<?php endforeach; ?>
	        		</div>
	        	</div>
	        <?php endif; ?>
	    <?php endif; ?>
	</div>
	
	<!-- Filter Subscribers -->
	<div id="mailingliststabs-filter">
		<?php if (apply_filters('newsletters_admin_createnewsletter_daterangesettings', true)) : ?>
	        <div class="misc-pub-section">
	        	<h4><label><input onclick="update_subscribers(); if (this.checked == true) { jQuery('#daterange_div').show(); } else { jQuery('#daterange_div').hide(); }" <?php echo (!empty($_POST['daterange']) && $_POST['daterange'] == "Y") ? 'checked="checked"' : ''; ?> type="checkbox" name="daterange" value="Y" id="daterange" /> <?php esc_html_e('Filter by date', 'wp-mailinglist'); ?></label>
	        	<?php echo ( $Html -> help(__('Specify a date range with a from/to date that subscribers subscribed to include in this newsletter. Both the From and To dates are required and should be in the format YYYY-MM-DD (without time).', 'wp-mailinglist'))); ?></h4>
	        	
	        	<div id="daterange_div" style="display:<?php echo (!empty($_POST['daterange']) && $_POST['daterange'] == "Y") ? 'block' : 'none'; ?>;">
	        		<p>
                        <label for="daterangefrom"><?php _e('From Date', 'wp-mailinglist'); ?></label>
                        <input placeholder="<?php echo esc_attr(wp_unslash($Html -> gen_date("Y-m-d", strtotime("-1 month")))); ?>" onblur="update_subscribers();" onkeyup="update_subscribers();" type="text" name="daterangefrom" value="<?php echo isset($_POST['daterangefrom']) ? esc_attr(wp_unslash($_POST['daterangefrom'])) : ''; ?>" id="daterangefrom" class="widefat" style="width:120px;" />
	        		</p>
	        		<p>
                        <label for="daterangeto"><?php _e('To Date', 'wp-mailinglist'); ?></label>
                        <input placeholder="<?php echo esc_attr(wp_unslash($Html -> gen_date("Y-m-d"))); ?>" onblur="update_subscribers();" onkeyup="update_subscribers();" type="text" name="daterangeto" value="<?php echo isset($_POST['daterangeto']) ? esc_attr(wp_unslash($_POST['daterangeto'])) : ''; ?>" id="daterangeto" class="widefat" style="width:120px;" />
	        		</p>
	        	</div>
	        	
	        	<script type="text/javascript">
	        	jQuery(document).ready(function() {
                        jQuery('#daterangefrom').datepicker({showButtonPanel:true, numberOfMonths:1, changeMonth:true, changeYear:true, defaultDate:"<?php echo isset($_POST['daterangefrom']) ? esc_js($_POST['daterangefrom']) : '' ; ?>", dateFormat:"yy-mm-dd"});
                        jQuery('#daterangeto').datepicker({showButtonPanel:true, numberOfMonths:1, changeMonth:true, changeYear:true, defaultDate:"<?php echo isset($_POST['daterangeto']) ? esc_js($_POST['daterangeto']) : ''; ?>", dateFormat:"yy-mm-dd"});
	        	});
	        	</script>
	        </div>
	    <?php endif; ?>
	    <?php if (apply_filters('newsletters_admin_createnewsletter_fieldsconditionssettings', true)) : ?>
	    
	    	<?php $saveipaddress = $this -> get_option('saveipaddress'); ?>
	    	<?php if (!empty($saveipaddress)) : ?>
		    	<div class="misc-pub-section">
		        	<h4><label><input onclick="update_subscribers(); if (this.checked == true) { jQuery('#countries_div').show(); } else { jQuery('#countries_div').hide(); }" <?php echo (!empty($_POST['countries'])) ? 'checked="checked"' : ''; ?> type="checkbox" name="countries" value="1" id="countries" /> <?php esc_html_e('Filter by Country', 'wp-mailinglist'); ?></label>
		        	<?php echo ( $Html -> help(__('Specify a date range with a from/to date that subscribers subscribed to include in this newsletter. Both the From and To dates are required and should be in the format YYYY-MM-DD (without time).', 'wp-mailinglist'))); ?></h4>
		        	
		        	<div id="countries_div" style="display:<?php echo (!empty($_POST['countries'])) ? 'block' : 'none'; ?>;">
		        		<select onchange="update_subscribers();" name="selectedcountries[]" multiple="multiple" style="width:100%;">
			        		<?php if ($countries = $this -> Country() -> select_code()) : ?>
			        			<?php foreach ($countries as $country_code => $country_name) : ?>
			        				<option <?php echo (!empty($_POST['selectedcountries']) && in_array($country_code, $_POST['selectedcountries'])) ? 'selected="selected"' : ''; ?> value="<?php echo esc_attr($country_code); ?>"><?php echo esc_html( $country_name); ?></option>
			        			<?php endforeach; ?>
			        		<?php endif; ?>
		        		</select>
		        	</div>
		        </div>
		    <?php endif; ?>
	    
	        <?php 
		        
		    $Db -> model = $Field -> model;
		    $fieldsquery = "SELECT `id`, `title`, `type`, `validation`, `slug`, `fieldoptions` FROM `" . $wpdb -> prefix . $Field -> table . "` WHERE `type` = 'text' OR `type` = 'hidden' OR `type` = 'radio' OR `type` = 'checkbox' OR `type` = 'select' OR `type` = 'pre_country' OR `type` = 'pre_gender' ORDER BY `order` ASC"; 
		    
		    ?>
	        <?php
	        
	        $query_hash = md5($fieldsquery);
	        if ($ob_fields = $this -> get_cache($query_hash)) {
		        $fields = $ob_fields;
	        } else {
		        $fields = $wpdb -> get_results($fieldsquery);
		        $this -> set_cache($query_hash, $fields);
	        }
	        
	        ?>
	        <?php if (!empty($fields)) : ?>
	        
	        	<?php
		        	
		        foreach ($fields as $fkey => $field) {
			        $fields[$fkey] = $this -> init_class($Field -> model, $field);
		        }	
		        	
		        ?>
	        
	        	<div class="misc-pub-section">
	                <h4><label><input <?php echo (!empty($_POST['dofieldsconditions']) && !empty($_POST['conditions'])) ? 'checked="checked"' : ''; ?> type="checkbox" name="dofieldsconditions" value="1" id="dofieldsconditions" onclick="update_subscribers(); if (this.checked == true) { jQuery('#fieldsconditions').show(); } else { jQuery('#fieldsconditions').hide(); }" /> <?php esc_html_e('Filter by custom fields', 'wp-mailinglist'); ?></label>
	                <?php echo ( $Html -> help(__('This filter works on the custom fields of your subscribers. You can filter the subscribers in the chosen mailing list/s to queue/send to subscribers with specific custom field values only. For example, with a "Gender" custom field, you can choose "Male" here under this filter to send only to male subscribers.', 'wp-mailinglist'))); ?></h4>
	                
	                <div id="fieldsconditions" style="display:<?php echo (!empty($_POST['dofieldsconditions']) && !empty($_POST['conditions'])) ? 'block' : 'none'; ?>;">
	                	<p>
		                	<?php esc_html_e('Match', 'wp-mailinglist'); ?>
		                	<select onchange="update_subscribers();" name="fieldsconditionsscope" id="fieldsconditionsscope">
		                		<option <?php echo (empty($_POST['conditionsscope']) || $_POST['conditionsscope'] == "all") ? 'selected="selected"' : ''; ?> value="all"><?php esc_html_e('all', 'wp-mailinglist'); ?></option>
		                		<option <?php echo (!empty($_POST['conditionsscope']) && $_POST['conditionsscope'] == "any") ? 'selected="selected"' : ''; ?> value="any"><?php esc_html_e('any', 'wp-mailinglist'); ?></option>
		                	</select>
		                	<?php esc_html_e('of these conditions:', 'wp-mailinglist'); ?>
		                </p>
	                
						<div id="fieldsconditionsfields">
							<?php foreach ($fields as $field) : ?>
		                    	<?php $supportedfields = array('text', 'hidden', 'radio', 'checkbox', 'select', 'pre_country', 'pre_gender'); ?>
		                    	<?php if (!empty($field -> type) && in_array($field -> type, $supportedfields)) : ?>
		                            <p>
		                                <label for="fields_<?php echo esc_html( $field -> id); ?>" style="font-weight:normal;"><?php echo esc_html($field -> title); ?></label><br/>
		                                
		                                <small>
		                                <?php
		                                
		                                $condquery = false;
		                                if (!empty($_POST['condquery'][$field -> slug])) {
		                                	$condquery = sanitize_text_field(wp_unslash($_POST['condquery'][$field -> slug]));
										}
		                                
		                                switch ($field -> validation) {
		                                	case 'numeric'					:
		                                		?>
		                                		<label><input onclick="update_subscribers();" <?php echo (!empty($condquery) && $condquery == "smaller") ? 'checked="checked"' : ''; ?> type="radio" name="condquery[<?php echo esc_html( $field -> slug); ?>]" value="smaller" id="condquery_<?php echo esc_html( $field -> slug); ?>_smaller" /> <?php esc_html_e('Smaller', 'wp-mailinglist'); ?></label>
		                                		<label><input onclick="update_subscribers();" <?php echo (!empty($condquery) && $condquery == "larger") ? 'checked="checked"' : ''; ?> type="radio" name="condquery[<?php echo esc_html( $field -> slug); ?>]" value="larger" id="condquery_<?php echo esc_html( $field -> slug); ?>_larger" /> <?php esc_html_e('Larger', 'wp-mailinglist'); ?></label>
		                                		<?php
			                                case 'notempty'					:
			                                default							:
			                                	?>
		                                		<label><input onclick="update_subscribers();" <?php echo (empty($condquery) || (!empty($condquery) && $condquery == "equals")) ? 'checked="checked"' : ''; ?> type="radio" name="condquery[<?php echo esc_html( $field -> slug); ?>]" value="equals" id="condquery_<?php echo esc_html( $field -> slug); ?>_equals" /> <?php esc_html_e('Equals', 'wp-mailinglist'); ?></label>
												<label><input onclick="update_subscribers();" <?php echo (!empty($condquery) && $condquery == "contains") ? 'checked="checked"' : ''; ?> type="radio" name="condquery[<?php echo esc_html( $field -> slug); ?>]" value="contains" id="condquery_<?php echo esc_html( $field -> slug); ?>_contains" /> <?php esc_html_e('Contains', 'wp-mailinglist'); ?></label>
			                                	<?php
			                                	break;
		                                }
		                                
		                                ?>
		                                </small>	                                
                                    <br/>

		                                <?php
		                                
		                                switch ($field -> type) {
		                                	case 'text'				:
		                                	case 'hidden'			:
		                                	
		                                		$value = (empty($_POST['fields'][$field -> slug])) ? false : esc_attr(sanitize_text_field(wp_unslash($_POST['fields'][$field -> slug])));
		                                	
		                                		?>
		                                		
		                                		<input onkeyup="update_subscribers();" type="text" name="fields[<?php echo esc_html( $field -> slug); ?>]" value="<?php echo esc_html( $value); ?>" id="fields_<?php echo esc_html( $field -> id); ?>" />
		                                		
		                                		<?php
		                                		break;
											case 'radio'			:									
												?>
		                                        
		                                        <?php if (!empty($field -> newfieldoptions)) : ?>
		                                        	<?php $r = 1; ?>
		                                        	<br/>
		                                            <label><input <?php echo (empty($_POST['fields'][$field -> slug])) ? 'checked="checked"' : ''; ?> type="radio" name="fields[<?php echo esc_html( $field -> slug); ?>]" value="" onclick="update_subscribers();" id="fields_<?php echo esc_html( $field -> id); ?>-0" /> <?php esc_html_e('ALL', 'wp-mailinglist'); ?></label><br/>
		                                        	<?php foreach ($field -> newfieldoptions as $fieldoption_key => $fieldoption_val) : ?>
		                                        		<?php if (!empty($fieldoption_val)) : ?>
		                                            		<label><input <?php echo (!empty($_POST['fields'][$field -> slug]) && $_POST['fields'][$field -> slug] == $fieldoption_key) ? 'checked="checked"' : ''; ?> onclick="update_subscribers();" type="radio" name="fields[<?php echo esc_html( $field -> slug); ?>]" value="<?php echo esc_html( $fieldoption_key); ?>" id="fields_<?php echo esc_html( $field -> id); ?>-<?php echo $r; ?>"  /> <?php echo esc_html($fieldoption_val); ?></label><br/>
		                                            	<?php endif; ?>
		                                                <?php $r++; ?>
		                                            <?php endforeach; ?>
		                                        <?php endif; ?>
		                                        
		                                        <?php
												break;
											case 'checkbox'			:											
												?>
												<div>
												<?php if (!empty($field -> newfieldoptions)) : ?>
													<label style="font-weight:bold"><input type="checkbox" name="checkboxall<?php echo esc_html( $field -> id); ?>" value="1" id="checkboxall<?php echo esc_html( $field -> id); ?>" onclick="jqCheckAll(this, false, 'fields[<?php echo esc_html( $field -> slug); ?>]');" /> <?php esc_html_e('Select all', 'wp-mailinglist'); ?></label><br/>
													<?php foreach ($field -> newfieldoptions as $option_id => $option_value) : ?>
														<label><input onclick="update_subscribers();" <?php echo (!empty($_POST['fields'][$field -> slug]) && in_array($option_id, $_POST['fields'][$field -> slug])) ? 'checked="checked"' : ''; ?>  type="checkbox" name="fields[<?php echo esc_html( $field -> slug); ?>][]" value="<?php echo esc_html( $option_id); ?>" id="fields_<?php echo esc_html( $field -> id); ?>" /> <?php echo esc_attr($option_value); ?></label><br/>
													<?php endforeach; ?>
												<?php endif; ?>
												</div>
												<?php											
												break;
		                                    case 'select'			:
		                                        ?><select style="max-width:250px;" name="fields[<?php echo esc_html( $field -> slug); ?>]" id="fields_<?php echo esc_html( $field -> id); ?>" onchange="update_subscribers();">
		                                        <option value=""><?php esc_html_e('- Select -', 'wp-mailinglist'); ?></option>
		                                        <?php 
		                                        
		                                        //$fieldoptions = @unserialize($field -> fieldoptions);
		                                        $fieldoptions = $field -> newfieldoptions;
		                                        if (!empty($fieldoptions)) {
			                                        foreach ($fieldoptions as $fieldoption_key => $fieldoption_val) {
			                                            ?><option <?php echo (!empty($_POST['fields'][$field -> slug]) && $_POST['fields'][$field -> slug] == $fieldoption_key) ? 'selected="selected"' : ''; ?> value="<?php echo esc_html( $fieldoption_key); ?>"><?php echo esc_html($fieldoption_val); ?></option><?php
			                                        }
			                                    }
		                                        
		                                        ?>
		                                        </select><?php
		                                        break;
											case 'pre_country'		:
												?>
		                                        
		                                        <?php if ($countries = $this -> Country() -> select()) : ?>
		                                            <select style="max-width:250px;" name="fields[<?php echo esc_html( $field -> slug); ?>]" id="fields_<?php echo esc_html( $field -> id); ?>" onchange="update_subscribers();">
		                                                <option value=""><?php esc_html_e('- Select Country -', 'wp-mailinglist'); ?></option>
		                                                <?php foreach ($countries as $country_id => $country_name) : ?>
		                                                	<option <?php echo (!empty($_POST['fields'][$field -> slug]) && $_POST['fields'][$field -> slug] == $country_id) ? 'selected="selected"' : ''; ?> value="<?php echo esc_html( $country_id); ?>"><?php echo esc_html( $country_name); ?></option>
		                                                <?php endforeach; ?>
		                                            </select>
		                                        <?php endif; ?>
		                                        
		                                        <?php
												break;
											case 'pre_gender'		:
												?>
		                                        
		                                        <select style="max-width:250px;" name="fields[<?php echo esc_html( $field -> slug); ?>]" id="fields_<?php echo esc_html( $field -> id); ?>" onchange="update_subscribers();">
		                                        	<option value=""><?php esc_html_e('- Select Gender -', 'wp-mailinglist'); ?></option>
		                                            <option <?php echo (!empty($_POST['fields'][$field -> slug]) && $_POST['fields'][$field -> slug] == "male") ? 'selected="selected"' : ''; ?> value="male"><?php esc_html_e('Male', 'wp-mailinglist'); ?></option>
		                                            <option <?php echo (!empty($_POST['fields'][$field -> slug]) && $_POST['fields'][$field -> slug] == "female") ? 'selected="selected"' : ''; ?> value="female"><?php esc_html_e('Female', 'wp-mailinglist'); ?></option>
		                                        </select>
		                                        
		                                        <?php
												break;	
		                                }
		                                
		                                ?>
		                            </p>
		                        <?php endif; ?>
		                    <?php endforeach; ?>
						</div>
	                </div>
	            </div>
	        <?php endif; ?>
	    <?php endif; ?>
	</div>
</div>

<div class="submitbox">
	<div>
		<div class="misc-pub-section">
			<p>
				<label for="status_active" style="font-weight:bold;"><?php esc_html_e('Status:', 'wp-mailinglist'); ?></label><br/>
				<label class="newsletters_success"><input <?php echo (empty($_POST['status']) || $_POST['status'] == "active") ? 'checked="checked"' : ''; ?> onclick="update_subscribers();" type="radio" name="status" value="active" id="status_active" /> <?php esc_html_e('Active', 'wp-mailinglist'); ?></label><br/>
				<label class="newsletters_error"><input <?php echo (!empty($_POST['status']) && $_POST['status'] == "inactive") ? 'checked="checked"' : ''; ?> onclick="update_subscribers();" type="radio" name="status" value="inactive" id="status_inactive" /> <?php esc_html_e('Inactive', 'wp-mailinglist'); ?></label><br/>
				<label class="newsletters_warning"><input <?php echo (!empty($_POST['status']) && $_POST['status'] == "all") ? 'checked="checked"' : ''; ?> onclick="update_subscribers();" type="radio" name="status" value="all" id="status_active" /> <?php esc_html_e('All/Both', 'wp-mailinglist'); ?></label>
			</p>
		</div>
		 
        <!-- Mailing Lists Errors -->
        <?php global $errors, $wpdb; ?>
        <?php if (!empty($errors['mailinglists'])) : ?>
            <p class="newsletters_error"><?php echo wp_kses_post( $errors['mailinglists']); ?></p>
        <?php endif; ?>
        
        <?php if (apply_filters('newsletters_admin_createnewsletter_subscribercount', true)) : ?>
	        <div class="misc-pub-section misc-pub-section-last">
	            <div id="subscriberscount">
	                <p><?php esc_html_e('0 subscribers total', 'wp-mailinglist'); ?></p>
	            </div>
	            
	            <p>
	            	<?php /*<a class="button button-secondary" id="updatesubscriberscountbutton" href="javascript:update_subscribers();"><?php esc_html_e('Update Count', 'wp-mailinglist'); ?></a>*/ ?>
	            	<input type="button" class="button button-secondary" id="updatesubscriberscountbutton" onclick="update_subscribers(); return false;" value="<?php esc_html_e('Update Count', 'wp-mailinglist'); ?>" />
	            	<?php echo ( $Html -> help(__('Click this button to update the subscribers count above in real-time. The subscribers count is an accurate count of how many subscribers this newsletter will be sent to based on the group, mailing list, custom fields filters and other selections made.', 'wp-mailinglist'))); ?>
	            </p>
	        </div>
	    <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
var srequest = false;

jQuery(document).ready(function() {
	<?php if (!empty($_POST['mailinglists']) || !empty($_POST['roles'])) : ?>
		update_subscribers();
	<?php endif; ?>	
		
	if (jQuery.isFunction(jQuery.fn.tabs)) {
		jQuery('#mailingliststabs').tabs();
	}
});

function update_subscribers() {
	if (srequest) { srequest.abort(); }
	//jQuery('#updatesubscriberscountbutton').attr('disabled', "disabled");
	
	var formvalues = jQuery('form#post').serialize();
	
	jQuery('#updatesubscriberscountbutton').prop('disabled', true);
	jQuery("#subscriberscount").html('<p><i class="fa fa-refresh fa-spin fa-fw"></i> <?php echo addslashes(__('loading subscriber count...', 'wp-mailinglist')); ?></p>');
	
	srequest = jQuery.ajax({
		url: newsletters_ajaxurl + 'action=subscribercount&security=<?php echo esc_html( wp_create_nonce('subscribercount')); ?>',
		data: formvalues,
		type: "POST",
		cache: false
	}).done(function(data, textStatus, jqXHR) {
		if (data == 0) {
			jQuery('#sendbutton, #sendbutton2').prop('disabled', true);
			//jQuery('#savedraftbutton, #savedraftbutton2').prop('disabled', true);	
			jQuery('#subscriberscount').html('<p><?php esc_html_e('No subscribers are available', 'wp-mailinglist'); ?></p>');
		} else {
			jQuery('#subscriberscount').html(data);
			jQuery('#sendbutton, #sendbutton2').prop('disabled', false);
			//jQuery('#savedraftbutton, #savedraftbutton2').prop('disabled', false);
			jQuery('#updatesubscriberscountbutton').prop('disabled', false);
		}
	}).fail(function(jqXHR, textStatus, errorThrown) {
		// an error occurred
	}).always(function(data, textStatus, jqXHR) {
		// do nothing...
	});
}
</script>