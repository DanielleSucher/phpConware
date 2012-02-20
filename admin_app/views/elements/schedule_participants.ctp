<h4>Select Participants (users available and requested to participate):</h4>
<table>
<thead>
	<tr>
	<th>Ldr</th>
	<th>Mod</th>
	<th>Pnlst</th>
	<th>Name</th>
	<th>Role Reqst</th>
	<th>Rating</th>
	<th>Conflicts</th>
	<th>Avoids</th>
	<th>Collabs</th>
	<th>User Comment</th>
	</tr>
</thead>
<tbody>
<tr>
	<td>
		<input type="radio" name="data[PanelParticipant][leader]" value="0" <?php if($leader==0) echo 'checked="checked"';?> />
	</td>
	<td>
		<input type="radio" name="data[PanelParticipant][moderator]" value="0" <?php if($moderator==0) echo 'checked="checked"';?> />
	</td>
	<td colspan="8">
	No leader / moderator
	</td>
</tr>
<?php 
	foreach($participants as $pref) {
	    $user_name = $pref['User']['name'];
		$rating_str = '';
		if($pref['PanelPref']['panel_rating_id']) {
			$rating_str = substr($pref['PanelRating']['description'], 0, 3);  // 1st 3 chars
			$rating_str = str_replace(':', '', $rating_str);
		}
		$role_str = '';
		$leading_role = FALSE;
		if($pref['PanelPref']['opt_panelist']) {
			$role_str .= 'Pnlst, ';
		}
		if($pref['PanelPref']['opt_leader']) {
			$role_str .= 'Ldr, ';
			$leading_role = TRUE;
		}
		if($pref['PanelPref']['opt_moderator']) {
			$role_str .= 'Mod';
			$leading_role = TRUE;
		}
		$user_id = $pref['PanelPref']['user_id'];
		$is_panelist = !($leader==$user_id) && !($moderator==$user_id) && array_key_exists($user_id, $panelists_assigned);
		$avoid_str = '';
		if(array_key_exists($user_id, $user_avoids)) {
			$people_avoid = $user_avoids[$user_id];
			foreach($people_avoid as $person) {
				$person_id = $person['User']['id'];
				if(array_search($person_id, $participant_ids)) {
					$avoid_str .= '<b>' . $person['User']['name'] . '</b>, ';
				} else {
					$avoid_str .= $person['User']['name'] . ', ';
				}
			}
		} 
		$collab_str = '';
		if(array_key_exists($user_id, $user_collabs)) {
			$people_collab = $user_collabs[$user_id];
			foreach($people_collab as $person) {
				$person_id = $person['User']['id'];
				if(array_search($person_id, $participant_ids)) {
					$collab_str .= '<b>' . $person['User']['name'] . '</b>, ';
				} else {
					$collab_str .= $person['User']['name'] . ', ';
				}
			}
		}
		$conflict_str = '';
		$conflict_title_str = '';
		$booked_conflict_popup = '';
		$checkbox_class = '';
        if(array_key_exists($user_id, $user_conflicts_booked)) {
            $conflict_str .= '<font color=red>booked</font> ';
            $booked_conflict_panel = $user_conflicts_booked[$user_id];
            $booked_conflict_panel_name = $booked_conflict_panel[0]['Panel']['name'];
            $booked_conflict_panel_id = $booked_conflict_panel[0]['Panel']['id'];
            $booked_conflict_panel_rating_str = '';
            if(array_key_exists($user_id, $booked_participant_ratings)) {
                $booked_rating = $booked_participant_ratings[$user_id];
                foreach($booked_rating as $each) {
                    if($each['PanelPref']['panel_rating_id']) {
                        $booked_conflict_panel_rating_str = substr($each['PanelRating']['description'], 0, 3);  // 1st 3 chars
                        $booked_conflict_panel_rating_str = str_replace(':', '', $booked_conflict_panel_rating_str);
                    }
                }
            }
            $booked_conflict_panel_role_str = '';
            if($booked_conflict_panel[0]['PanelParticipant']['panelist']) {
            	$booked_conflict_panel_role_str = 'panelist';
            }
            if($booked_conflict_panel[0]['PanelParticipant']['leader']) {
                $booked_conflict_panel_role_str = 'leader';
            }
            if($booked_conflict_panel[0]['PanelParticipant']['moderator']) {
                $booked_conflict_panel_role_str = 'moderator';
            }
            $conflict_title_str .= ' '.$user_name.' is already scheduled for '.$booked_conflict_panel_name.' as a '.$booked_conflict_panel_role_str.' during this time slot';
            if($booked_conflict_panel_rating_str != '') {
                 $conflict_title_str.= ' (rating it '.$booked_conflict_panel_rating_str.')';
            }
            $conflict_title_str.= '.';
            // $booked_conflict_popup = $pref['User']['name'].' is already scheduled for '	.$booked_conflict_panel_name.' during this time slot. If you schedule them for '.$panel['Panel']['name'].' now, they will be automatically removed from '.$booked_conflict_panel_name.'. Are you sure you want to do that?';
            $checkbox_class = "already-booked";
        }
        if(array_key_exists($user_id, $user_conflicts_participate)) {
            $conflict_str .= 'participate ';
            $participate_conflict_panels = $user_conflicts_participate[$user_id];
            foreach($participate_conflict_panels as $panel) {
                $panel_name = $panel['Panel']['name'];
                $panel_id = $panel['Panel']['id'];
                $panel_rating_str = '';
                if($panel['PanelPref']['panel_rating_id']) {
                     $panel_rating_str = substr($panel['PanelRating']['description'], 0, 3);  // 1st 3 chars
                     $panel_rating_str = str_replace(':', '', $panel_rating_str);
                }
                $conflict_title_str .= ' '.$panel_name.' is already scheduled for this time slot, and '.$user_name.' wants to participate';
                if($panel_rating_str != '') {
                     $conflict_title_str.= ' (rating it '.$panel_rating_str.')';
                }
                $conflict_title_str.= '.';
            }
        }
        if(array_key_exists($user_id, $user_conflicts_watch)) {
            $conflict_str .= 'watch ';
            $watch_conflict_panels = $user_conflicts_watch[$user_id];
            foreach($watch_conflict_panels as $panel) {
                //add to the mouseover
                $panel_name = $panel['Panel']['name'];
                $panel_id = $panel['Panel']['id'];
                $panel_rating_str = '';
                if($panel['PanelPref']['panel_rating_id']) {
                    $panel_rating_str = substr($panel['PanelRating']['description'], 0, 3);  // 1st 3 chars
                    $panel_rating_str = str_replace(':', '', $panel_rating_str);
                }
                $conflict_title_str .= ' '.$panel_name.' is already scheduled for this time slot, and '.$user_name.' wants to watch';
                if($panel_rating_str != '') {
                    $conflict_title_str.= ' (rating it '.$panel_rating_str.')';;
                }
                $conflict_title_str.= '.';
            }
        }
        if(array_key_exists($user_id, $user_conflicts_adjacent)) {
            $conflict_str .= 'schedule ';
            $adjacent_conflict_panels = &$user_conflicts_adjacent[$user_id];
            if($checkbox_class != "already-booked") { 
            	 $checkbox_class = "side-by-side";
            }
            foreach($adjacent_conflict_panels as &$panel2) {
                //add to the mouseover
                $panel_name = $panel2['Panel']['name'];
                $panel_id = $panel2['Panel']['id'];
                $panel2['username'] = $user_name;
                $conflict_title_str .= ' '.$user_name.' is already assigned to '.$panel_name.' in an adjacent time slot, and '.$user_name.' has requested not to do back-to-back panels.';
            }
        }
        $conflict_title_str = htmlspecialchars($conflict_title_str, ENT_QUOTES);
?>
	<tr>
		<td>
			<input type="radio" name="data[PanelParticipant][leader]" class="<?php echo $checkbox_class; ?> <?php echo $user_id;?>" title="leader" value="<?php echo $user_id;?>" <?php if($leader==$user_id) echo 'checked="checked"';?> />
		</td>
		<td>
			<input type="radio" name="data[PanelParticipant][moderator]" class="<?php echo $checkbox_class; ?> <?php echo $user_id;?>" title="moderator" value="<?php echo $user_id;?>" <?php if($moderator==$user_id) echo 'checked="checked"';?> />
		</td>
		<td>
			<input type="checkbox" name="data[PanelParticipant][panelists][]" class="<?php echo $checkbox_class; ?> <?php echo $user_id;?>" title="panelist" value="<?php echo $user_id;?>" <?php if($is_panelist) echo 'checked="checked"';?> />
		</td>
		<td title="user id: <?php echo $user_id;?>"><?php if($leading_role) echo '<b>';?><?php echo $pref['User']['name'];?><?php if($leading_role) echo '</b>';?>
		</td>
		<td><?php echo $role_str;?></td>
		<td><?php echo $rating_str;?></td>
		<td title="<?php echo $conflict_title_str; ?>"><?php echo $conflict_str;?></td>
		<td style="color: red;"><?php echo $avoid_str;?></td>
		<td style="color: green;"><?php echo $collab_str;?></td>
		<td><?php echo $pref['PanelPref']['comment'];?></td>
	</tr>
<?php 
	} ?>
</tbody>
</table>
<?php $user_conflicts_adjacent_ids = array_keys($user_conflicts_adjacent); ?>
<script>
	var user_conflicts_adjacent = <?php echo json_encode($user_conflicts_adjacent); ?>;
	var reload_adjacent_conflicts = <?php echo json_encode($user_conflicts_adjacent); ?>;
</script>