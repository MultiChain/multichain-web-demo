<?php
	
	if (@$_POST['approvetxfilter'] || @$_POST['disapprovetxfilter']) {
		if (no_displayed_error_result($approvetxid, multichain(
			'approvefrom', $_POST['approvefrom'], $_GET['txfilter'], @$_POST['approvetxfilter'] ? true : false
		)))
			output_success_text('Approval successfully changed in transaction '.$approvetxid);
	}
	
	if (@$_POST['approvestreamfilter'] || @$_POST['disapprovestreamfilter']) {
		if (no_displayed_error_result($approvetxid, multichain(
			'approvefrom', $_POST['approvefrom'], $_GET['streamfilter'], array(
				'for' => @$_POST['stream'],
				'approve' => @$_POST['approvestreamfilter'] ? true : false
		))))
			output_success_text('Approval successfully changed in transaction '.$approvetxid);
	}
	
	if (@$_GET['txfilter']) {
		if (no_displayed_error_result($listtxfilters, multichain(
			'listtxfilters', $_GET['txfilter'], true
		)))
			$txfilter=$listtxfilters[0];
			
		no_displayed_error_result($filtercode, multichain(
			'getfiltercode', $_GET['txfilter']
		));
	}
	
	if (@$_GET['streamfilter']) {
		if (no_displayed_error_result($liststreamfilters, multichain(
			'liststreamfilters', $_GET['streamfilter'], true
		)))
			$streamfilter=$liststreamfilters[0];
			
		no_displayed_error_result($filtercode, multichain(
			'getfiltercode', $_GET['streamfilter']
		));
		
		no_displayed_error_result($liststreams, multichain(
			'liststreams', '*', true
		));
	}

	$labels=multichain_labels();
	
	if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
		foreach ($getaddresses as $index => $address)
			if (!$address['ismine'])
				unset($getaddresses[$index]);
				
		if (@$_GET['txfilter']) { // get global admin addresses
			$adminaddresses=array();

			if (no_displayed_error_result($listpermissions,
				multichain('listpermissions', 'admin', implode(',', array_get_column($getaddresses, 'address')))
			))
				$adminaddresses=array_unique(array_get_column($listpermissions, 'address'));
		}

		if (@$_GET['streamfilter']) { // get global send addresses
			$sendaddresses=array();

			if (no_displayed_error_result($listpermissions,
				multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
			))
				$sendaddresses=array_unique(array_get_column($listpermissions, 'address'));
		}		
	}
	
?>

			<div class="row">

				<div class="col-sm-12">
				
<?php
	
	if (@$_GET['txfilter']) {
		require_once 'functions-filter.php';
	
?>
				
					<h3>Approve transaction filter</h3>
					
					<form class="form-horizontal" method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>&txfilter=<?php echo html($_GET['txfilter'])?>">
						<div class="form-group">
							<label class="col-sm-2 control-label">Filter name</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?php echo html($txfilter['name'])?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">Code</label>
							<div class="col-sm-9">
								<div class="form-control-static" style="height:16em; overflow:scroll; white-space:pre;"><code><?php echo html($filtercode)?></code></div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">Status</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?php output_txfilter_status($txfilter); ?></p>
							</div>
						</div>
						<div class="form-group">
							<label for="approvefrom" class="col-sm-2 control-label">Admin address:</label>
							<div class="col-sm-9">
							<select class="form-control col-sm-6" name="approvefrom" id="approvefrom">
<?php

		foreach ($adminaddresses as $address) 
			echo '<option value="'.html($address).'">'.format_address_html($address, true, $labels).'</option>';

?>						
							</select>
							</div>
						</div>

						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-9">
								<input class="btn btn-default" type="submit" name="approvetxfilter" value="Approve Transaction Filter">
								&nbsp;
								<input class="btn btn-default" type="submit" name="disapprovetxfilter" value="Disapprove Transaction Filter">
							</div>
						</div>
					</form>
					
<?php
	}

	if (@$_GET['streamfilter']) {
		require_once 'functions-filter.php';
	
?>
				
					<h3>Approve stream filter</h3>
					
					<form class="form-horizontal" method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>&streamfilter=<?php echo html($_GET['streamfilter'])?>">
						<div class="form-group">
							<label class="col-sm-2 control-label">Filter name</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?php echo html($streamfilter['name'])?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">Code</label>
							<div class="col-sm-9">
								<div class="form-control-static" style="height:16em; overflow:scroll; white-space:pre;"><code><?php echo html($filtercode)?></code></div>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">Active on</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?php

		$oneoutput=false;
		
		foreach ($liststreams as $stream)
			foreach ($stream['filters'] as $streamfilter)
				if ($streamfilter['createtxid']==$_GET['streamfilter']) {
					echo ($oneoutput ? '<br/>' : '').html($stream['name']);
					$oneoutput=true;
				}
				
		if (!$oneoutput)
			echo 'no streams';

?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">For stream</label>
							<div class="col-sm-9">
							<select class="form-control col-sm-6" name="stream" id="stream">
							
<?php

		foreach ($liststreams as $stream) 
			echo '<option value="'.html($stream['createtxid']).'">'.html($stream['name']).' &ndash; created by '.format_address_html($stream['creators'][0], false, $labels).'</option>';

?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="approvefrom" class="col-sm-2 control-label">Stream admin address:</label>
							<div class="col-sm-9">
							<select class="form-control col-sm-6" name="approvefrom" id="approvefrom">
<?php

		foreach ($sendaddresses as $address) 
			echo '<option value="'.html($address).'">'.format_address_html($address, true, $labels).'</option>';

?>						
							</select>
							</div>
						</div>

						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-9">
								<input class="btn btn-default" type="submit" name="approvestreamfilter" value="Approve Filter for this Stream">
								&nbsp;
								<input class="btn btn-default" type="submit" name="disapprovestreamfilter" value="Disapprove Filter for this Stream">
							</div>
						</div>
					</form>
					
<?php
	}
?>

				</div>
			</div>