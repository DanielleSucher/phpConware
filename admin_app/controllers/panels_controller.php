<?php
class PanelsController extends AppController {

	var $name = 'Panels';
	var $uses = array('Panel', 'PanelPref', 'PanelRating', 
						'ConDay', 'TimeSlot', 'DayTimeSlot', 
						'UserTimeSlot', 'PanelSchedule',
						'PanelParticipant', 'UserAvoid', 'UserCollab', 'User');
	var $paginate = array(
		'limit' => 200,
	);
	
	var $helpers = array('Form', 'Html', 'Javascript', 'Time', 'Util');

	//helper functions for the panelist_edit and schedule_new views
	function delete_adjacent_conflicts($id,$adjacent_conflicts,$previous_slot,$next_slot) {
		if(in_array($id, $adjacent_conflicts)) {
			$conditions = array (
				'PanelParticipant.user_id' => $id, 
				"OR" => array(
                	'PanelParticipant.day_time_slot_id = ' . $previous_slot,
                	'PanelParticipant.day_time_slot_id = ' . $next_slot,
            	),
			);
			$this->PanelParticipant->deleteAll($conditions); 
		}
	}

	function delete_booked_conflicts($id,$booking_conflicts,$slot_id) {
		if(array_key_exists($id, $booking_conflicts)) {
			$conditions = array (
				'PanelParticipant.user_id' => $id, 
				'PanelParticipant.day_time_slot_id' => $slot_id,
			);
			$this->PanelParticipant->deleteAll($conditions); 
		}
	}


	function save_panelists($slot_id,$panel_id) {
		$next_slot = $slot_id + 1;
        $previous_slot = $slot_id - 1;
		// Prepare to identify booking conflicts to be deleted
		$booking_conflicts = $this->PanelParticipant->find('all', array(
            'recursive' => 1,
            'conditions' => array(
            	'PanelParticipant.day_time_slot_id = ' . $slot_id,
            	'PanelParticipant.panel_id != ' . $panel_id,
            ),
            'order' => array('PanelParticipant.id DESC'),
        ));
        $booking_conflicts = $this->hashListByKey($booking_conflicts, 'PanelParticipant', 'user_id');
        $booked_conflict_ids = array_keys($booking_conflicts);
        // Prepare to identify adjacent panel conflicts to be deleted
        $adjacent_conflicts = $this->data['adjacent_panels_to_delete'];
        $adjacent_conflicts = explode(",", $adjacent_conflicts);

		if($this->data['PanelParticipant']) {

			$leader_id = (int)$this->data['PanelParticipant']['leader'];
			if($leader_id) {
				// Remove the leader from the panel they were previously assigned to in this timeslot, if any
				$this->delete_booked_conflicts($leader_id,$booking_conflicts,$slot_id);

				// Remove the leader from any adjacent panels, if any and if indicated
				$this->delete_adjacent_conflicts($leader_id,$adjacent_conflicts,$previous_slot,$next_slot);

				// Save Leader, if applicable
				$this->PanelParticipant->create();
				$leader_data = array();
				$leader_data['PanelParticipant']['day_time_slot_id'] = $slot_id;
				$leader_data['PanelParticipant']['panel_id'] = $panel_id;
				$leader_data['PanelParticipant']['user_id'] = $leader_id;
				$leader_data['PanelParticipant']['leader'] = 1;
				$this->PanelParticipant->save($leader_data); 
			}

			$moderator_id = (int)$this->data['PanelParticipant']['moderator'];
			if($moderator_id) {
				// Remove the moderator from the panel they were previously assigned to in this timeslot, if any
				$this->delete_booked_conflicts($moderator_id,$booking_conflicts,$slot_id);

				// Remove the moderator from any adjacent panels, if any and if indicated
				$this->delete_adjacent_conflicts($leader_id,$adjacent_conflicts,$previous_slot,$next_slot);

				// Save Moderator, if applicable
				$this->PanelParticipant->create();
				$moderator_data = array();
				$moderator_data['PanelParticipant']['day_time_slot_id'] = $slot_id;
				$moderator_data['PanelParticipant']['panel_id'] = $panel_id;
				$moderator_data['PanelParticipant']['user_id'] = $moderator_id;
				$moderator_data['PanelParticipant']['moderator'] = 1;
				$this->PanelParticipant->save($moderator_data);
			}
            // Save watchers
            // if(array_key_exists('watchers', $this->data['PanelParticipant'])) {
            //     $selected_watchers = $this->data['PanelParticipant']['watchers'];
            // } else {
            //     $selected_watchers = array();
            // }
            // foreach($selected_watchers as $watcher_id) {
            //     $watcher_id = (int)$watcher_id;
            //     if(!$watcher_id) {
            //         continue;
            //     }
            //     $this->PanelParticipant->create();
            //     $panelist_data = array();
            //     $panelist_data['PanelParticipant']['day_time_slot_id'] = $slot_id;
            //     $panelist_data['PanelParticipant']['panel_id'] = $panel_id;
            //     $panelist_data['PanelParticipant']['user_id'] = $watcher_id;
            //     $panelist_data['PanelParticipant']['watcher'] = 1;
            //     $this->PanelParticipant->save($panelist_data);
            // }
			// Save other panelists
			if(array_key_exists('panelists', $this->data['PanelParticipant'])) {
				$selected_panelists = $this->data['PanelParticipant']['panelists'];
			} else {
				$selected_panelists = array();
			}
			foreach($selected_panelists as $panelist_id) {
				$panelist_id = (int)$panelist_id;
				if(!$panelist_id) {
					continue;
				}

				// Remove the panelists from the panels they were previously assigned to in this timeslot, if any
				$this->delete_booked_conflicts($panelist_id,$booking_conflicts,$slot_id);

				// Remove the panelists from any adjacent panels, if any and if indicated
				$this->delete_adjacent_conflicts($panelist_id,$adjacent_conflicts,$previous_slot,$next_slot);

				// Save panelists
				$this->PanelParticipant->create();
				$panelist_data = array();
				$panelist_data['PanelParticipant']['day_time_slot_id'] = $slot_id;
				$panelist_data['PanelParticipant']['panel_id'] = $panel_id;
				$panelist_data['PanelParticipant']['user_id'] = $panelist_id;
				$panelist_data['PanelParticipant']['panelist'] = 1;
				$this->PanelParticipant->save($panelist_data); 
			}
		}
	}


	   function define_participant_arrays($slot,$panel,$slot_id,$panel_id,$rooms,$user_conflicts_booked) {
	   	$next_slot = $slot_id + 1;
        $previous_slot = $slot_id - 1;
		$this->PanelPref->unbindModel(array('belongsTo' => array('Panel')));
		$participants = $this->PanelPref->find('all', array(
			'recursive' => 1,
			'conditions' => array(
				'PanelPref.panel_id' => $panel_id,
				'PanelPref.interest' => 2,
			),
			'order' => array('PanelPref.panel_rating_id DESC', 'User.name'),
			'joins' => array(
				array(
					'table' => 'users',
					'alias' => 'User',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array('PanelPref.user_id = User.id'),
				),
				array(
					'table' => 'user_time_slots',
					'alias' => 'UserTimeSlot',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array(
						'UserTimeSlot.user_id = User.id',
						'UserTimeSlot.day_time_slot_id = ' . $slot_id,
						'UserTimeSlot.available = 1',
					),
				),
			),
		));
        $watchers = $this->PanelPref->find('all', array(
            'recursive' => 1,
            'conditions' => array(
                'PanelPref.panel_id' => $panel_id,
                'PanelPref.interest' => 1,
            ),
            'order' => array('PanelPref.panel_rating_id DESC', 'User.name'),
            'joins' => array(
                array(
                    'table' => 'users',
                    'alias' => 'User',
                    'type' => 'inner',
                    'foreignKey' => false,
                    'conditions' => array('PanelPref.user_id = User.id'),
                ),
                array(
                    'table' => 'user_time_slots',
                    'alias' => 'UserTimeSlot',
                    'type' => 'inner',
                    'foreignKey' => false,
                    'conditions' => array(
                        'UserTimeSlot.user_id = User.id',
                        'UserTimeSlot.day_time_slot_id = ' . $slot_id,
                        'UserTimeSlot.available = 1',
                    ),
                ),
            ),
        ));
		$panelists_assigned = $this->PanelParticipant->find('all', array(
			'conditions' => array(
				'PanelParticipant.panel_id' => $panel_id,
			),
			'recursive' => -1,
		));
        $panelists_assigned = $this->hashByKey($panelists_assigned, 'PanelParticipant', 'user_id');
        $watchers_assigned = $this->PanelParticipant->find('all', array(
            'conditions' => array(
                'PanelParticipant.panel_id' => $panel_id,
                'PanelParticipant.watcher' => 1,
            ),
            'recursive' => -1,
        ));
        $watchers_assigned = $this->hashByKey($watchers_assigned, 'PanelParticipant', 'user_id');
        $leader = $this->PanelParticipant->find('first', array(
            'conditions' => array(
                'PanelParticipant.panel_id' => $panel_id,
                'PanelParticipant.leader' => 1,
            ),
        ));
		if($leader) {
			$leader = $leader['PanelParticipant']['user_id'];
		} else {
			$leader = 0;
		}
		$moderator = $this->PanelParticipant->find('first', array(
			'conditions' => array(
				'PanelParticipant.panel_id' => $panel_id,
				'PanelParticipant.moderator' => 1,
			),
		));
		if($moderator) {
			$moderator = $moderator['PanelParticipant']['user_id'];
		} else {
			$moderator = 0;
		}

		$participants_by_id = $this->hashListByKey($participants, 'User', 'id');
		$participant_ids = array_keys($participants_by_id);

        $watchers_by_id = $this->hashListByKey($watchers, 'User', 'id');
        $watcher_ids = array_keys($watchers_by_id);
		
		// Now get a list of users force-assigned to the panel (but not necessarily marked as available or interested)
		$assigned_conditions = array(
				'PanelParticipant.panel_id' => $panel_id,
		);
		if($participant_ids) {  // Filter out potential panelists already accounted for
			$assigned_conditions[] = 'PanelParticipant.user_id NOT IN (' . implode(',', $participant_ids) . ')';
		}
		$non_avail_assigned = $this->PanelParticipant->find('all', array(
			'conditions' => $assigned_conditions,
			'joins' => array(
				array(
					'table' => 'users',
					'alias' => 'User',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array('PanelParticipant.user_id = User.id', 'PanelParticipant.watcher = 0'),
				),
			),
		));
		
		if($participant_ids) {
			$user_avoids = $this->UserAvoid->find('all', array(
				'recursive' => 1,
				'conditions' => array(
					'UserAvoid.requester_id IN ('. implode(',', $participant_ids) . ')',
				),
			));
			$user_avoids = $this->hashListByKey($user_avoids, 'UserAvoid', 'requester_id');
			
			$user_collabs = $this->UserCollab->find('all', array(
				'recursive' => 1,
				'conditions' => array(
					'UserCollab.requester_id IN ('. implode(',', $participant_ids) . ')',
				),
			));
			$user_collabs = $this->hashListByKey($user_collabs, 'UserCollab', 'requester_id');

            $user_conflicts_watch = $this->PanelPref->find('all', array(
                'recursive' => 1,
                'conditions' => array(
                    'PanelPref.user_id IN ('. implode(',', $participant_ids) . ')',
                    'PanelPref.interest' => 1,
                ),
                'order' => array('PanelPref.panel_rating_id DESC', 'Panels.name'),
                'joins' => array(
                    array(
                        'table' => 'panel_schedules',
                        'alias' => 'PanelSchedule',
                        'type' => 'inner',
                        'foreignKey' => false,
                        'conditions' => array(
                            'PanelSchedule.panel_id = PanelPref.panel_id',
                            'PanelSchedule.day_time_slot_id = ' . $slot_id,
                        ),
                    ),
                    array(
                        'table' => 'panels',
                        'alias' => 'Panels',
                        'type' => 'inner',
                        'foreignKey' => false,
                        'conditions' => array(
                            'Panels.id = PanelPref.panel_id',
                        ),
                    ),
                ),
            ));
            $user_conflicts_watch = $this->hashListByKey($user_conflicts_watch, 'PanelPref', 'user_id');

            $user_conflicts_participate = $this->PanelPref->find('all', array(
                'recursive' => 1,
                'conditions' => array(
                    'PanelPref.user_id IN ('. implode(',', $participant_ids) . ')',
                    'PanelPref.interest' => 2,
                    'PanelPref.panel_id != ' . $panel_id,
                ),
                'order' => array('PanelPref.panel_rating_id DESC', 'Panels.name'),
                'joins' => array(
                    array(
                        'table' => 'panel_schedules',
                        'alias' => 'PanelSchedule',
                        'type' => 'inner',
                        'foreignKey' => false,
                        'conditions' => array(
                            'PanelSchedule.panel_id = PanelPref.panel_id',
                            'PanelSchedule.day_time_slot_id = ' . $slot_id,
                        ),
                    ),
                    array(
                        'table' => 'panels',
                        'alias' => 'Panels',
                        'type' => 'inner',
                        'foreignKey' => false,
                        'conditions' => array(
                            'Panels.id = PanelPref.panel_id',
                        ),
                    ),
                ),
            ));
            $user_conflicts_participate = $this->hashListByKey($user_conflicts_participate, 'PanelPref', 'user_id');

			$this->PanelParticipant->unbindModel(array('belongsTo' => array('User')));
            $user_conflicts_adjacent = $this->PanelParticipant->find('all', array(
                'recursive' => 1,
                'conditions' => array(
                    'PanelParticipant.user_id IN ('. implode(',', $participant_ids) . ')',
                    "OR" => array(
                        'PanelParticipant.day_time_slot_id = ' . $previous_slot,
                        'PanelParticipant.day_time_slot_id = ' . $next_slot,
                    ),
                ),
                'order' => array('Panels.name'),
                'joins' => array(
                    array(
                        'table' => 'panels',
                        'alias' => 'Panels',
                        'type' => 'inner',
                        'foreignKey' => false,
                        'conditions' => array(
                            'Panels.id = PanelParticipant.panel_id',
                        ),
                    ),
                    array(
                        'table' => 'question_answers',
                        'alias' => 'QuestionAnswer',
                        'type' => 'inner',
                        'foreignKey' => false,
                        'conditions' => array(
                            'QuestionAnswer.user_id = PanelParticipant.user_id',
                            'QuestionAnswer.question_id' => 14, // Question: okay to have back-to-back panels?
                            'QuestionAnswer.question_option_id' => 43, // Answer: not okay to have back-to-back panels!
                        ),
                    ),
                ),
            ));
            $user_conflicts_adjacent = $this->hashListByKey($user_conflicts_adjacent, 'PanelParticipant', 'user_id');

            $this->PanelParticipant->bindModel(array('hasOne' => array('PanelPref' => array(
                'foreignKey' => false,
                'conditions' => array(
                    'PanelPref.user_id = PanelParticipant.user_id',
                    'PanelPref.panel_id = PanelParticipant.panel_id',
                ),
            ))));
            $user_conflicts_booked = $this->PanelParticipant->find('all', array(
                'recursive' => 1,
                'conditions' => array(
                    'PanelParticipant.user_id IN ('. implode(',', $participant_ids) . ')',
                    'PanelParticipant.day_time_slot_id = ' . $slot_id,
                    'PanelParticipant.panel_id != ' . $panel_id,
                ),
                'order' => array('PanelPref.panel_rating_id DESC', 'Panels.name'),
                'joins' => array(
                    array(
                        'table' => 'panels',
                        'alias' => 'Panels',
                        'type' => 'inner',
                        'foreignKey' => false,
                        'conditions' => array(
                            'Panels.id = PanelParticipant.panel_id',
                    ),
                ),
            )));
            $user_conflicts_booked_panels = $this->hashListByKey($user_conflicts_booked, 'PanelParticipant', 'panel_id');
            $booked_participant_panel_ids = array_keys($user_conflicts_booked_panels);
            $user_conflicts_booked = $this->hashListByKey($user_conflicts_booked, 'PanelParticipant', 'user_id');
            $booked_participant_ids = array_keys($user_conflicts_booked);

            if($booked_participant_ids && $booked_participant_panel_ids) {
                $booked_participant_ratings = $this->PanelPref->find('all', array(
                    'recursive' => 1,
                    'conditions' => array(
                        'PanelPref.user_id IN ('. implode(',', $booked_participant_ids) . ')',
                        'PanelPref.panel_id IN ('. implode(',', $booked_participant_panel_ids) . ')',
                    ),
                ));
                $booked_participant_ratings = $this->hashListByKey($booked_participant_ratings, 'PanelPref', 'user_id');
            } else {
                $booked_participant_ratings= array();
            }
		} else {
			$user_avoids = array();
			$user_collabs = array();
            $user_conflicts_watch = array();
            $user_conflicts_participate = array();
            $user_conflicts_adjacent= array();
            $user_conflicts_booked= array();
            $booked_participant_ratings= array();
		}
		
		
		$this->set(compact('panel', 'slot', 'rooms', 'participants', 'watchers', 
            'leader', 'moderator', 'panelists_assigned', 'watchers_assigned', 
            'user_avoids', 'user_collabs', 'user_conflicts_watch', 'user_conflicts_participate',
            'user_conflicts_adjacent', 'user_conflicts_booked', 'booked_participant_ratings', 'participant_ids', 'watcher_ids', 'non_avail_assigned'));
	}




	//views
	function avail_panelists() {
		$panels = $this->Panel->find('all', array(
			'conditions' => array('Panel.disabled' => 0),
			'recursive' => 0,
			'order' => array('Panel.panel_type_id'),
		));
		$this->set(compact('panels'));
	}
	
	function by_time() {
		$days = $this->ConDay->find('all');
		$slots_by_day = $this->DayTimeSlot->slotsByDay($days);
		
//		$avail_by_slot = $this->UserTimeSlot->find('all', array(
//			'conditions' => array('UserTimeSlot.available' => 1),
//			'recursive' => 0,
//		));
//		$avail_by_slot = $this->hashListByKey($avail_by_slot, 'UserTimeSlot', 'day_time_slot_id');
		$avail_by_slot = array();
		$prefs_by_user = $this->PanelPref->find('all', array(
			'fields' => array(
				'UserTimeSlot.day_time_slot_id', 'PanelPref.panel_id',
				'Panel.name',
				'COUNT(DISTINCT PanelPref.user_id) AS panels_int' 
			),
			'conditions' => array(
				'PanelPref.interest' => 2,
				'UserTimeSlot.available' => 1,
				'PanelSchedule.id' => NULL,
				'PanelParticipant.id' => NULL,
			),
			'joins' => array(
				array(
					'table' => 'user_time_slots',
					'alias' => 'UserTimeSlot',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array('PanelPref.user_id = UserTimeSlot.user_id'),
				),
				array(
					'table' => 'day_time_slots',
					'alias' => 'DayTimeSlot',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array(
						'UserTimeSlot.day_time_slot_id = DayTimeSlot.id',
						'DayTimeSlot.enabled' => 1,
					),
				),
				array(
					'table' => 'time_slots',
					'alias' => 'TimeSlot',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array(
						'DayTimeSlot.time_slot_id = TimeSlot.id',
						'TimeSlot.hour' => 1,
					),
				),
				array(
					'table' => 'panels',
					'alias' => 'Panel',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array(
						'PanelPref.panel_id = Panel.id',
						'Panel.disabled' => 0,
					),
				),
				array(
					'table' => 'panel_schedules',
					'alias' => 'PanelSchedule',
					'type' => 'left outer',
					'foreignKey' => false,
					'conditions' => array(
						'PanelPref.panel_id = PanelSchedule.panel_id',
					),
				),
				array(
					'table' => 'panel_participants',
					'alias' => 'PanelParticipant',
					'type' => 'left outer',
					'foreignKey' => false,
					'conditions' => array(
						'PanelParticipant.user_id = PanelPref.user_id',
						'PanelParticipant.day_time_slot_id = UserTimeSlot.day_time_slot_id',
					),
				),
			),
			'group' => array('UserTimeSlot.day_time_slot_id', 'PanelPref.panel_id', 'Panel.name' ),
			'order' => array('panels_int ASC'),
			'recursive' => -1,
		));
		$poss_slots_by_panel = $this->hashListByKey($prefs_by_user, 'PanelPref', 'panel_id');
		$prefs_by_user = $this->hashListByKey($prefs_by_user, 'UserTimeSlot', 'day_time_slot_id');
		
		$scheduled_panels = $this->PanelSchedule->find('all', array('recursive' => 1));
		$scheduled_user_panels = $this->hashListByKey($scheduled_panels, 'PanelSchedule', 'panel_id');
		$scheduled_panels = $this->hashListByKey($scheduled_panels, 'PanelSchedule', 'day_time_slot_id');
		
		if($scheduled_panels) {
			$scheduled_user_panels = array_keys($scheduled_user_panels);
			$this->PanelParticipant->unbindModel(array('belongsTo' => array('Panel')));
			$scheduled_users = $this->PanelParticipant->find('all', array(
				'recursive' => 0,
				'conditions' => array(
					'PanelParticipant.panel_id IN (' . implode(',', $scheduled_user_panels) . ')',
				),
			));
			
			$scheduled_users = $this->hashListByKey($scheduled_users, 'PanelParticipant', 'panel_id');
		} else {
			$scheduled_users = array();
		}
		
		$unscheduled_panels = $this->Panel->find('all', array(
			'recursive' => 1,
			'order' => array('Panel.id'),
			'conditions' => array(
				'PanelSchedule.id' => NULL,
				'Panel.disabled' => 0
			),
			'joins' => array(
				array(
					'table' => 'panel_schedules',
					'alias' => 'PanelSchedule',
					'type' => 'left outer',
					'foreignKey' => false,
					'conditions' => array('PanelSchedule.panel_id = Panel.id'),
				),
			),
		));
		
		$this->set(compact('days', 'slots_by_day', 'avail_by_slot', 'prefs_by_user', 'scheduled_panels', 
			'poss_slots_by_panel', 'scheduled_users',
			'unscheduled_panels'));
	}
	
	function index() {
		$this->Panel->recursive = 0;
		$this->set('panels', $this->paginate());
		
//		$not_interested = $this->PanelPref->find('list', array(
//			'fields' => array('PanelPref.panel_id', 'interest_count'),
//			'conditions' => array('PanelPref.interest' => NO_THANKS),
//			'group' => array('PanelPref.panel_id'),
//			'recursive' => -1,
//		));
//		$watch = $this->PanelPref->find('list', array(
//			'fields' => array('PanelPref.panel_id', 'interest_count'),
//			'conditions' => array('PanelPref.interest' => WATCH),
//			'group' => array('PanelPref.panel_id'),
//			'recursive' => -1,
//		));
//		$participate = $this->PanelPref->find('list', array(
//			'fields' => array('PanelPref.panel_id', 'interest_count'),
//			'conditions' => array('PanelPref.interest' => PARTICIPATE),
//			'group' => array('PanelPref.panel_id'),
//			'recursive' => -1,
//		));
//		$this->set(compact('not_interested', 'watch', 'participate'));
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid panel', true));
			$this->redirect(array('action' => 'index'));
		}
		$this->set('panel', $this->Panel->read(null, $id));
	}

	function add() {
		if (!empty($this->data)) {
			$this->Panel->create();
			if ($this->Panel->save($this->data)) {
				$this->Session->setFlash(__('The panel has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The panel could not be saved. Please, try again.', true));
			}
		}
		$panelTypes = $this->Panel->PanelType->find('list');
		$panelLengths = $this->Panel->PanelLength->find('list');
		$tracks = $this->Panel->Track->find('list');
		$this->set(compact('panelTypes', 'panelLengths', 'tracks'));
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid panel', true));
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Panel->save($this->data)) {
				$this->Session->setFlash(__('The panel has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The panel could not be saved. Please, try again.', true));
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Panel->read(null, $id);
		}
		$panelTypes = $this->Panel->PanelType->find('list');
		$panelLengths = $this->Panel->PanelLength->find('list');
		$tracks = $this->Panel->Track->find('list');
		$this->set(compact('panelTypes', 'panelLengths', 'tracks'));
	}

	function condensed_schedule() {
//		App::import('Vendor', 'phpexcel');
//		App::import('Vendor', 'ExcelWriter2007', array('file' => 'PHPExcel'.DS.'Writer'.DS.'Excel2007.php'));
//		App::import('Vendor', 'ZipArchive', array('file' => 'PHPExcel'.DS.'Shared'.DS.'ZipArchive.php'));
		$users = $this->User->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'User.id', 'User.last_name', 'User.name'
			),
			'conditions' => array(
				'User.roles' => 'pro',
			),
			'order' => array('User.last_name', 'User.first_name')
		));
		$panels_sorted = $this->PanelSchedule->getSortedPanels();
		
		$panel_details = $this->Panel->find('all', array(
			'recursive' => 1,
		));
		$panel_details = $this->hashByKey($panel_details, 'Panel', 'id');
		$panels_by_id = $this->hashByKey($panels_sorted, array(0, 'PanelSchedule'), 'panel_id');
		$slot_details = $this->DayTimeSlot->find('all', array(
		));
		$slot_details = $this->hashByKey($slot_details, 'DayTimeSlot', 'id');
		
		$panel_participants = $this->PanelParticipant->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT PanelParticipant.user_id', 'PanelParticipant.leader', 'PanelParticipant.moderator',
				'PanelParticipant.panel_id',
			),
		));
		$panel_participants = $this->hashListByKey($panel_participants, 'PanelParticipant', 'panel_id');
		
		// Assemble the list of panels, for each user, in sorted order
		$user_sorted_panels = array();
		foreach($panels_sorted as $panel) {
			$panel_id = $panel['PanelSchedule']['panel_id'];
			if(!array_key_exists($panel_id, $panel_participants)) {
				continue;  // Skip panels with no panelists assigned (like the Meet the Prose party)
			}
			$users_on_panel = $panel_participants[$panel_id];
			foreach($users_on_panel as $assignment) {
				$user_id = $assignment['PanelParticipant']['user_id'];
				$user_sorted_panels[$user_id][] = array_merge($assignment, $panel);
			}
		}
		
//		$objPHPExcel = new PHPExcel();
//		// Set properties
//		$objPHPExcel->getProperties()->setCreator("Readercon.org");
//		$objPHPExcel->getProperties()->setLastModifiedBy("Readercon.org");
//		$objPHPExcel->getProperties()->setTitle("Concise Schedule");
//		$objPHPExcel->getProperties()->setSubject("Concise Schedule");
//		$objPHPExcel->getProperties()->setDescription("Concise Schedule for labels");
//		$objPHPExcel->setActiveSheetIndex(0);
//
//		$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Hello');
//		$objPHPExcel->getActiveSheet()->SetCellValue('B2', 'world!');
//		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
//		$objWriter->save('test.xlsx');

		$this->set(compact('users', 'panel_details', 'slot_details', 
					'user_sorted_panels'));
	}
	
	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for panel', true));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Panel->delete($id)) {
			$this->Session->setFlash(__('Panel deleted', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(__('Panel was not deleted', true));
		$this->redirect(array('action' => 'index'));
	}
	
	function print_signup() {
		$this->layout = 'print';
		$panels = $this->Panel->find('all', array(
					'recursive' => 0,
					'order' => 'Panel.id'));
		$this->set(compact('panels'));
	}
	
	function program_guide() {
		$panels_sorted = $this->PanelSchedule->getSortedPanels();
		$panel_details = $this->Panel->find('all', array(
			'recursive' => 1,
		));
		$panel_details = $this->hashByKey($panel_details, 'Panel', 'id');
		$panels_by_slot = $this->hashByKey($panels_sorted, array(0, 'PanelSchedule'), 'day_time_slot_id');
		$slot_ids = array_keys($panels_by_slot);
		$slot_details = $this->DayTimeSlot->find('all', array(
		));
		$slot_details = $this->hashByKey($slot_details, 'DayTimeSlot', 'id');

		$this->PanelParticipant->unbindModel(
			array('belongsTo' => array('Panel', 'DayTimeSlot'))
		);
		$all_participants = $this->PanelParticipant->find('all', array(
			'fields' => array(
				'DISTINCT PanelParticipant.user_id', 'PanelParticipant.leader', 'PanelParticipant.moderator',
				'PanelParticipant.panel_id',
				'User.first_name', 'User.last_name'
			),
			'joins' => array(
				array(
					'table' => 'users',
					'alias' => 'User',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array('User.id = PanelParticipant.user_id'),
				),
			),
			'order' => array(
				'PanelParticipant.panel_id',
				'User.last_name',
				'User.first_name',
			),
		));
		$all_participants = $this->hashListByKey($all_participants, 'PanelParticipant', 'panel_id');
		$this->set(compact('panels_sorted', 'panel_details', 'slot_details', 'all_participants'));
	}
	
	function panelist_edit() {
		$panel_id = (int)$this->params['named']['panel'];
		$slot_id = (int)$this->params['named']['slot'];
		if(!$panel_id || !$slot_id) {
			$this->Session->setFlash('Error: Panel or Slot not specified.');
			$this->redirect(array('controller' => 'panels', 'action' => 'by_time'));
		}
		$slot = $this->DayTimeSlot->findById($slot_id);
		$panel = $this->Panel->findById($panel_id);
		$panel_length = $panel['PanelLength']['minutes'];

		$rooms = array();
		$user_conflicts_booked = array();
		$this->define_participant_arrays($slot,$panel,$slot_id,$panel_id,$rooms,$user_conflicts_booked);


		$success = TRUE;
		// Incoming post data
		if (!empty($this->data)) {
			$error_msg = 'The panel could not be scheduled for this room and time slot. Please, try again.';
			// Clear existing panelists
			$this->PanelParticipant->deleteAll(array(
				'PanelParticipant.panel_id' => $panel_id,
			));
			
			// Save panelists
			$this->save_panelists($slot_id,$panel_id);
			
			if ($success) {
				$this->Session->setFlash(__('The panelists/watchers have been saved', true));
				$this->redirect(array('controller' => 'panels', 'action' => 'by_time'));
			} else {
				$this->Session->setFlash(__($error_msg, true));
			}
		}

	}
	
	function panelist_index() {
		$panel_guide_numbers = $this->PanelSchedule->getPanelsByProgramGuideOrder();
		$panels_by_user = $this->PanelParticipant->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT PanelParticipant.user_id',
				'PanelParticipant.panel_id'
			),
		));
		$panels_by_user = $this->hashListByKey($panels_by_user, 'PanelParticipant', 'user_id');
		
		$users = $this->User->find('all', array(
			'fields' => array(
				'User.id',
				'User.first_name',
				'User.last_name'
			),
			'order' => array('User.last_name', 'User.first_name'),
		));
		
		$this->set(compact('panel_guide_numbers', 'panels_by_user', 'users'));
	}
	
/**
 * Clear room and time slot assignments for a panel. 
 * Assigned panelists are unaffected
 */
	function schedule_clear() {
		$panel_id = (int)$this->params['named']['panel'];
		if(!$panel_id) {
			$this->Session->setFlash('Error: Panel not specified.');
			$this->redirect(array('controller' => 'panels', 'action' => 'by_time'));
		}
		
		// Make sure panel exists in the db
		$panel = $this->Panel->findById($panel_id);
		if(!$panel) {
			$this->Session->setFlash('Clear Schedule - Error: No panel found by that id.');
			$this->redirect(array('controller' => 'panels', 'action' => 'by_time'));
		}
		
		// Clear the room and time slot assignment for this panel
		$this->PanelSchedule->deleteAll(array(
			'PanelSchedule.panel_id' => $panel_id,
		));
		
		// Also clear the timeslot of the assigned panelists for that panel
		// (leave assignments intact)
		$this->PanelParticipant->updateAll(
			array('PanelParticipant.day_time_slot_id' => NULL),
			array('PanelParticipant.panel_id' => $panel_id)
		);
		$panel_name = $panel['Panel']['name'];
		$this->Session->setFlash('Room and time slot assignment cleared for ' . $panel_name . '.');
		$this->redirect(array('controller' => 'panels', 'action' => 'by_time'));
	}
	
	function schedule_new() {
		$panel_id = (int)$this->params['named']['panel'];
		$slot_id = (int)$this->params['named']['slot'];
        $next_slot = $slot_id + 1;
        $previous_slot = $slot_id - 1;
		if(!$panel_id || !$slot_id) {
			$this->Session->setFlash('Error: Panel or Slot not specified.');
			$this->redirect(array('controller' => 'panels', 'action' => 'by_time'));
		}
		$slot = $this->DayTimeSlot->findById($slot_id);
		$panel = $this->Panel->findById($panel_id);
		$panel_length = $panel['PanelLength']['minutes'];

		$assigned_rooms = $this->PanelSchedule->find('all', array(
			'fields' => array('PanelSchedule.room_id'),
			'conditions' => array('PanelSchedule.day_time_slot_id' => $slot_id),
		));
		$room_options = array();
		$assigned_rooms = $this->hashByKey($assigned_rooms, 'PanelSchedule', 'room_id');
		if($assigned_rooms) {
			$assigned_rooms = array_keys($assigned_rooms);
			$assigned_rooms = implode(',', $assigned_rooms);
			$room_options['conditions'] = array(
				'Room.id NOT IN (' . $assigned_rooms . ')'
			);
		}

		$rooms = $this->PanelSchedule->Room->find('list', $room_options);
		$user_conflicts_booked = array();
		$this->define_participant_arrays($slot,$panel,$slot_id,$panel_id,$rooms,$user_conflicts_booked);

		// Incoming post data
		if (!empty($this->data)) {
			$error_msg = 'The panel could not be scheduled for this room and time slot. Please, try again.';
			// Schedule the first slot.
			$this->data['PanelSchedule']['panel_id'] = $panel_id;
			
			$scheduled_minutes = 0;
			$success = TRUE;
			$panel_slot_id = $slot_id; 
			while(($scheduled_minutes < $panel_length) && $success) {
				$this->data['PanelSchedule']['day_time_slot_id'] = $panel_slot_id;
				$this->PanelSchedule->create();
				if (!$this->PanelSchedule->save($this->data)) {
//				if(!print "Saved: slot $slot_id, panel $panel_id, room {$this->data['PanelSchedule']['room_id']}<br />") {
					$success = FALSE;
				}
				$panel_slot_id += 1;  // Schedule the next slot if necessary
				$scheduled_minutes += 30;
			}
			
			// Now save panelists
			$this->save_panelists($slot_id,$panel_id);
			
			if ($success) {
				$this->Session->setFlash(__('The panel has been scheduled', true));
				$this->redirect(array('controller' => 'panels', 'action' => 'by_time'));
			} else {
				$this->Session->setFlash(__($error_msg, true));
			}
		}
		
//		$panelists_scheduled_for_slot = $this->PanelParticipant->find('all', array(
//			'recursive' => 0,
//			'fields' => array('PanelParticipant.user_id')
//		));
	}
	
	function schedule_sheets() {
		$this->layout = 'print';
		
		$users = $this->User->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'User.id', 'User.last_name', 'User.name'
			),
			'conditions' => array(
				'User.roles' => 'pro',
			),
			'order' => array('User.last_name', 'User.first_name')
		));
		$panels_sorted = $this->PanelSchedule->getSortedPanels();
		
		$panel_details = $this->Panel->find('all', array(
			'recursive' => 1,
		));
		$panel_details = $this->hashByKey($panel_details, 'Panel', 'id');
//		$panels_by_id = $this->hashByKey($panels_sorted, array(0, 'PanelSchedule'), 'panel_id');
		$slot_details = $this->DayTimeSlot->find('all', array(
		));
		$slot_details = $this->hashByKey($slot_details, 'DayTimeSlot', 'id');
		
		$panel_participants = $this->PanelParticipant->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT PanelParticipant.user_id', 'PanelParticipant.leader', 'PanelParticipant.moderator',
				'PanelParticipant.panel_id',
			),
		));
		$panel_participants = $this->hashListByKey($panel_participants, 'PanelParticipant', 'panel_id');
		
		// Assemble the list of panels, for each user, in sorted order
		$user_sorted_panels = array();
		foreach($panels_sorted as $panel) {
			$panel_id = $panel['PanelSchedule']['panel_id'];
			if(!array_key_exists($panel_id, $panel_participants)) {
				continue;  // Skip panels with no panelists assigned (like the Meet the Prose party)
			}
			$users_on_panel = $panel_participants[$panel_id];
			foreach($users_on_panel as $assignment) {
				$user_id = $assignment['PanelParticipant']['user_id'];
				$user_sorted_panels[$user_id][] = array_merge($assignment, $panel);
			}
		}

		$all_participants = $this->PanelParticipant->find('all', array(
			'fields' => array(
				'DISTINCT PanelParticipant.user_id', 'PanelParticipant.leader', 'PanelParticipant.moderator',
				'PanelParticipant.panel_id',
				'User.first_name', 'User.last_name'
			),
			'joins' => array(
				array(
					'table' => 'users',
					'alias' => 'User',
					'type' => 'inner',
					'foreignKey' => false,
					'conditions' => array('User.id = PanelParticipant.user_id'),
				),
			),
			'order' => array(
				'PanelParticipant.panel_id',
				'User.last_name',
				'User.first_name',
			),
		));
		$all_participants = $this->hashListByKey($all_participants, 'PanelParticipant', 'panel_id');
		
		$this->set(compact('users', 'panel_details', 'slot_details', 
					'user_sorted_panels', 'all_participants'));
	}
	
	function scheduled() {
		$scheduled_panels = $this->PanelSchedule->find('all', array(
			'recursive' => 1,
			'order' => array('PanelSchedule.panel_id'),
		));
		$scheduled_user_panels = $this->hashListByKey($scheduled_panels, 'PanelSchedule', 'panel_id');
		
		if($scheduled_panels) {
			$scheduled_user_panels = array_keys($scheduled_user_panels);
			$this->PanelParticipant->unbindModel(array('belongsTo' => array('Panel')));
			$scheduled_users = $this->PanelParticipant->find('all', array(
				'recursive' => 0,
				'conditions' => array(
					'PanelParticipant.panel_id IN (' . implode(',', $scheduled_user_panels) . ')',
				),
				'order' => array(
					'PanelParticipant.leader DESC',
					'PanelParticipant.moderator DESC',
					'PanelParticipant.user_id',
				),
			));
			
			$scheduled_users = $this->hashListByKey($scheduled_users, 'PanelParticipant', 'panel_id');
		} else {
			$scheduled_users = array();
		}
		
		$time_slots = $this->DayTimeSlot->find('all', array(
			'conditions' => array(
			),
		));
		$time_slots = $this->hashByKey($time_slots, 'DayTimeSlot', 'id');
		$this->set(compact('scheduled_panels', 'scheduled_users', 'time_slots'));
	}

	function unscheduled() {
		$unscheduled_panels = $this->Panel->find('all', array(
			'recursive' => 1,
			'order' => array('Panel.id'),
			'conditions' => array(
				'PanelSchedule.id' => NULL,
				'Panel.disabled' => 0
			),
			'joins' => array(
				array(
					'table' => 'panel_schedules',
					'alias' => 'PanelSchedule',
					'type' => 'left outer',
					'foreignKey' => false,
					'conditions' => array('PanelSchedule.panel_id = Panel.id'),
				),
			),
		));
		$this->set(compact('unscheduled_panels'));
	}
}
?>