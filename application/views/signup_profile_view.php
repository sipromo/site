<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE HTML>
<html>
<head>

	<title>Sipromo - Registration</title>

	<?php $this->view('common/header'); ?>
	
	<link rel='stylesheet' type='text/css' href='<?= base_url(); ?>assets/css/signup.css?v=0.03' />
	
	<script src='<?= base_url(); ?>assets/js/signup.js?v=0.4'></script>

</head>
<body>

	<div class='container'>
		
		<?php $this->view('common/top_bar'); ?>
		
		<div class='back container_form_signup_back'>
			
			<div class='front txt-c container_signup_front'>
				
				<?php $this->view('include/signup_detail_user'); ?>
				
			</div>
			
			<?php $this->view('common/loader'); ?>
			
		</div>
		
	</div>

</body>
</html>