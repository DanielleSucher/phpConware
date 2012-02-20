<h1>Panelist Registration</h1>
<?php 

	echo $session->flash();

	if($today <= $registration_end_date) {
		echo $this->element('user_registration');
	} else {
		echo $this->element('registration_is_over');
	}
?>



