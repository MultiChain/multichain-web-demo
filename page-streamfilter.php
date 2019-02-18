<?php
	require_once 'functions-filter.php';
	
	$success=false; // set default value
	
	if (@$_POST['teststreamfiltercode']) {
		if (no_displayed_error_result($teststreamfilter, multichain('teststreamfilter', false, $_POST['code']))) {
			if ($teststreamfilter['compiled'])
				output_success_text('Filter code successfully compiled');
			else
				output_error_text('Filter code failed to compile:'."\n".$teststreamfilter['reason']);
		}
	}
	
	if (@$_POST['createstreamfilter']) {
		$success=no_displayed_error_result($createtxid, multichain('createfrom',
			$_POST['createfrom'], 'streamfilter', $_POST['name'], false, $_POST['code']
		));
		
		if ($success)
			output_success_text('Filter successfully created in transaction '.$createtxid);
	}
	

	if (@$_POST['teststreamfilterpublish']) {
		if ($_POST['format']=='json') {
			$json=json_decode($_POST['data']);
				
			if ($json===null) {
				output_html_error('The entered JSON structure does not appear to be valid');
				$data=null;
			} else
				$data=array('json' => $json);
				
		} elseif ($_POST['format']=='text')
			$data=array('text' => $_POST['data']);
		
		else
			$data=trim($_POST['data']);
		
		if (isset($data) && no_displayed_error_result($createrawsendfrom, multichain('createrawsendfrom',
			$_POST['sendfrom'], new stdClass(), array(array(
				'for' => $_POST['stream'],
				'keys' => preg_split('/\n|\r\n?/', trim($_POST['keys'])),
				'data' => $data,
				'options' => $_POST['offchain'] ? 'offchain' : ''
			)), 'sign'
		))) {

			if (no_displayed_error_result($teststreamfilter, multichain_with_raw(
				$teststreamfilterraw, 'teststreamfilter', false, $_POST['code'], $createrawsendfrom['hex']
			))) {

				if ($teststreamfilter['compiled']) {
					$suffix=' (time taken '.number_format($teststreamfilter['time'], 6).' seconds)';

					if ($teststreamfilter['passed'])
						output_success_text('Filter code allowed this stream item'.$suffix);
					else
						output_error_text('Filter code blocked this stream item with the reason: '.$suffix."\n".$teststreamfilter['reason']);
					
					if ($_POST['callbacks'])
						output_filter_test_callbacks($teststreamfilterraw);
						
				} else
					output_error_text('Filter code failed to compile:'."\n".$teststreamfilter['reason']);
			}
		}
	}

	$filterkeystreams=array();

	if (no_displayed_error_result($liststreams, multichain('liststreams', '*', true)))
		foreach ($liststreams as $stream)
			foreach ($stream['filters'] as $streamfilter)
				$filterkeystreams[$streamfilter['createtxid']][$stream['createtxid']]=$stream['name'];
				
	$labels=multichain_labels();

	if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
		foreach ($getaddresses as $index => $address)
			if (!$address['ismine'])
				unset($getaddresses[$index]);
				
		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
		))
			$sendaddresses=array_get_column($listpermissions, 'address');

		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'create', implode(',', array_get_column($getaddresses, 'address')))
		))
			$createaddresses=array_unique(array_get_column($listpermissions, 'address'));
	}
	
?>

			<div class="row">

				<div class="col-sm-4">
					<h3>Stream filters</h3>
			
<?php
	if (no_displayed_error_result($liststreamfilters, multichain('liststreamfilters', '*', true))) {

		foreach ($liststreamfilters as $filter) {
			$name=$filter['name'];

?>
						<table class="table table-bordered table-condensed table-break-words <?php echo ($success && ($name==@$_POST['name'])) ? 'bg-success' : 'table-striped'?>">
							<tr>
								<th style="width:30%;">Name</th>
								<td><?php echo html($name)?></td>
							</tr>
							<tr>
								<th>Code</th>
								<td><a href="./filter-code.php?chain=<?php echo html($_GET['chain'])?>&txid=<?php echo html($filter['createtxid'])?>"><?php echo number_format($filter['codelength'])?> bytes of <?php echo html(ucfirst($filter['language']))?></a></td>
							</tr>
							<tr>
								<th>Active on</th>
								<td><?php
	
	if (@count($filterkeystreams[$filter['createtxid']])) {
		foreach ($filterkeystreams[$filter['createtxid']] as $streamcreatetxid => $streamname)
			echo html(strlen($streamname) ? $streamname : $streamcreatetxid).' ';

	} else
		echo 'no streams ';
								
								?>&ndash; <a href="./?chain=<?php echo html($chain)?>&page=approve&streamfilter=<?php echo html($filter['createtxid'])?>">change</a></td>
							</tr>
						</table>

<?php
		}
	}	
?>

				</div>
				
				<div class="col-sm-8">
					<h3>Test or create stream filter</h3>
					
					<form class="form-horizontal" method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
						<div class="form-group">
							<label for="code" class="col-sm-3 control-label">Filter code:</label>
							<div class="col-sm-9">
								<textarea class="form-control" style="font-family:monospace;" rows="12" name="code" id="code"><?php if (strlen(@$_POST['code'])) echo html($_POST['code']); else {

?>function filterstreamitem()
{
    var item=getfilterstreamitem();
    
    if (item.keys.length<2)
        return "At least two keys required";
}<?php } ?></textarea>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="teststreamfiltercode" value="Test Compiling Filter Code Only">
							</div>
						</div>
						<div class="form-group">
							&nbsp;
						</div>
						<div class="form-group">
							<label for="sendfrom" class="col-sm-3 control-label">Test publish address:</label>
							<div class="col-sm-9">
							<select class="form-control col-sm-6" name="sendfrom" id="sendfrom">
<?php

	foreach ($sendaddresses as $address) 
		echo '<option value="'.html($address).'"'.((@$_POST['sendfrom']==$address) ? ' selected' : '').'>'.format_address_html($address, true, $labels).'</option>';

?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="stream" class="col-sm-3 control-label">Test publish stream:</label>
							<div class="col-sm-9">
								<select class="form-control" name="stream" id="stream">
<?php
	foreach ($liststreams as $stream)
		echo '<option value="'.html($stream['createtxid']).'"'.((@$_POST['stream']==$stream['createtxid']) ? ' selected' : '').'>'.html($stream['name']).'</option>';
?>						
								</select>
								<label class="checkbox-inline">
									<input type="checkbox" name="offchain" value="1" <?php echo @$_POST['offchain'] ? 'checked' : ''?>>Publish as off-chain item
								</label>
							</div>
						</div>
						<div class="form-group">
							<label for="keys" class="col-sm-3 control-label">Test publish item keys:</label>
							<div class="col-sm-9">
								<textarea class="form-control" rows="3" name="keys" id="keys"><?php echo html(@$_POST['keys'])?></textarea>
								<span id="helpBlock" class="help-block">To use multiple keys, enter one per line.</span>
							</div>
						</div>
						<div class="form-group">
							<label for="format" class="col-sm-3 control-label">Test publish data:</label>
							<div class="col-sm-9">
								<select class="form-control" name="format" id="format">
									<option value="">Raw binary (enter hexadecimal below)</option>
									<option value="text"<?php echo (@$_POST['format']=='text') ? ' selected' : ''?>>Text (enter text below)</option>
									<option value="json"<?php echo (@$_POST['format']=='json') ? ' selected' : ''?>>JSON (enter JSON below)</option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="data" class="col-sm-3 control-label"></label>
							<div class="col-sm-9">
								<textarea class="form-control" rows="8" name="data" id="data"><?php echo html(@$_POST['data'])?></textarea>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="teststreamfilterpublish" value="Test Publishing Item with This Filter">
								&nbsp;
								<input type="checkbox" name="callbacks" id="callbacks" value="1" <?php echo @$_POST['callbacks'] ? 'checked' : ''?>> <label class="control-label" for="callbacks" style="font-weight:normal;">Display callback results</label>
							</div>
						</div>
						<div class="form-group">
							&nbsp;
						</div>
						<div class="form-group">
							<label for="createfrom" class="col-sm-3 control-label">Create from address:</label>
							<div class="col-sm-9">
							<select class="form-control col-sm-6" name="createfrom" id="createfrom">
<?php

	foreach ($createaddresses as $address) 
		echo '<option value="'.html($address).'"'.((@$_POST['createfrom']==$address) ? ' selected' : '').'>'.format_address_html($address, true, $labels).'</option>';

?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="name" class="col-sm-3 control-label">Create filter name:</label>
							<div class="col-sm-9">
								<input class="form-control" name="name" id="name" placeholder="filter1" value="<?php echo html($_POST['name'])?>">
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="createstreamfilter" value="Create as a New On-Chain Stream Filter">
							</div>
						</div>
					</form>

				</div>
			</div>
