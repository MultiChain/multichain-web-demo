<?php
	define('const_max_for_entities', 3);
	
	require_once 'functions-filter.php';
	
	$success=false; // set default value
	
	$keyforentities=array();
	for ($forentity=1; $forentity<=const_max_for_entities; $forentity++)
		if (strlen($_POST['for'.$forentity]))
			$keyforentities[$_POST['for'.$forentity]]=true;
			
	$restrictions=count($keyforentities) ? array('for' => array_keys($keyforentities)) : false;
	
	if (@$_POST['testtxfiltercode']) {
		if (no_displayed_error_result($testtxfilter, multichain('testtxfilter', false, $_POST['code']))) {
			if ($testtxfilter['compiled'])
				output_success_text('Filter code successfully compiled');
			else
				output_error_text('Filter code failed to compile:'."\n".$testtxfilter['reason']);
		}
	}
	
	if (@$_POST['createtxfilter']) {
		$success=no_displayed_error_result($createtxid, multichain('createfrom',
			$_POST['createfrom'], 'txfilter', $_POST['name'], $restrictions, $_POST['code']
		));
		
		if ($success)
			output_success_text('Filter successfully created in transaction '.$createtxid);
	}
	
	$sendrawtx=null;
	
	if (@$_POST['testtxfiltersend'])
		if (no_displayed_error_result($createrawsendfrom, multichain('createrawsendfrom',
			$_POST['sendfrom'], array($_POST['to'] => array($_POST['asset'] => floatval($_POST['qty']))), array(), 'sign'
		))) {
			$sendrawtx=$createrawsendfrom['hex'];
			$showcallbacks=$_POST['sendcallbacks'];
		}
	
	if (@$_POST['testtxfilterraw']) {
		$sendrawtx=trim($_POST['rawtx']);
		$showcallbacks=$_POST['rawcallbacks'];
	}
	
	if (isset($sendrawtx)) {
		if (no_displayed_error_result($testtxfilter, multichain_with_raw(
			$testtxfilterraw, 'testtxfilter', $restrictions, $_POST['code'], $sendrawtx
		))) {

			if ($testtxfilter['compiled']) {
				$suffix=' (time taken '.number_format($testtxfilter['time'], 6).' seconds)';

				if ($testtxfilter['passed'])
					output_success_text('Filter code allowed this transaction'.$suffix);
				else
					output_error_text('Filter code blocked this transaction with the reason: '.$suffix."\n".$testtxfilter['reason']);
				
				if ($showcallbacks)
					output_filter_test_callbacks($testtxfilterraw);
					
			} else
				output_error_text('Filter code failed to compile:'."\n".$testtxfilter['reason']);
		}
	}
	
	$labels=multichain_labels();

	if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
		$keymyaddresses=array();
		$sendaddresses=array();
		$receiveaddresses=array();
		$adminaddresses=array();
		$admincreateaddresses=array();
		
		foreach ($getaddresses as $index => $address)
			if ($address['ismine'])
				$keymyaddresses[$address['address']]=true;
			else
				unset($getaddresses[$index]);
				
		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
		))
			$sendaddresses=array_get_column($listpermissions, 'address');
			
		if (no_displayed_error_result($listpermissions, multichain('listpermissions', 'receive')))
			$receiveaddresses=array_get_column($listpermissions, 'address');
		
		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'admin', implode(',', array_get_column($getaddresses, 'address')))
		))
			$adminaddresses=array_unique(array_get_column($listpermissions, 'address'));

		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'create', implode(',', $adminaddresses))
		))
			$admincreateaddresses=array_unique(array_get_column($listpermissions, 'address'));
	}
	
	no_displayed_error_result($listassets, multichain('listassets'));
	no_displayed_error_result($liststreams, multichain('liststreams'));

	$getinfo=multichain_getinfo();
	
	$usableassets=array();
	if (no_displayed_error_result($gettotalbalances, multichain('gettotalbalances')))
		$usableassets=array_get_column($gettotalbalances, 'name');
?>

			<div class="row">

				<div class="col-sm-4">
					<h3>Transaction filters</h3>
			
<?php
	if (no_displayed_error_result($listtxfilters, multichain('listtxfilters', '*', true))) {

		foreach ($listtxfilters as $filter) {
			$name=$filter['name'];

?>
						<table class="table table-bordered table-condensed table-break-words <?php echo ($success && ($name==@$_POST['name'])) ? 'bg-success' : 'table-striped'?>">
							<tr>
								<th style="width:30%;">Name</th>
								<td><?php echo html($name)?></td>
							</tr>

<?php
			if (count($filter['for'])) {
?>

							<tr>
								<th style="width:30%;">Only for</th>
								<td>

<?php
				foreach ($filter['for'] as $forindex => $filterfor) {
?>

							<?php echo ($forindex ? '<br/>' : '').$filterfor['name']?>

<?php
				}
?>								
								
								</td>
							</tr>

<?php
			}
?>						

							<tr>
								<th>Code</th>
								<td><a href="./filter-code.php?chain=<?php echo html($_GET['chain'])?>&txid=<?php echo html($filter['createtxid'])?>"><?php echo number_format($filter['codelength'])?> bytes of <?php echo html(ucfirst($filter['language']))?></a></td>
							</tr>
							<tr>
								<th>Status</th>
								<td><?php output_txfilter_status($filter)?> &ndash; <a href="./?chain=<?php echo html($chain)?>&page=approve&txfilter=<?php echo html($filter['createtxid'])?>">change</a></td>
							</tr>
						</table>

<?php
		}
	}	
?>

				</div>
				
				<div class="col-sm-8">
					<h3>Test or create transaction filter</h3>
					
					<form class="form-horizontal" method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
						<div class="form-group">
							<label for="code" class="col-sm-3 control-label">Filter code:</label>
							<div class="col-sm-9">
								<textarea class="form-control" style="font-family:monospace;" rows="12" name="code" id="code"><?php if (strlen(@$_POST['code'])) echo html($_POST['code']); else {

?>function filtertransaction()
{
    var tx=getfiltertransaction();

    if (tx.vout.length<1)
        return "One output required";
}<?php } ?></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="for1" class="col-sm-3 control-label">Only apply if tx uses:</label>
							<div class="col-sm-9">
								<div class="row row-no-gutters">
							
<?php
	$entities=array();

	foreach ($listassets as $asset)
		$entities[$asset['issuetxid']]=$asset['name'];

	foreach ($liststreams as $stream)
		$entities[$stream['createtxid']]=$stream['name'];

	for ($forentity=1; $forentity<=const_max_for_entities; $forentity++) {
?>
								<div class="col-sm-4">
									<select class="form-control" name="for<?php echo $forentity?>" id="for<?php echo $forentity?>">
										<option value=""></option>

<?php

	foreach ($entities as $entitytxid => $entityname)
		echo '<option value="'.html($entitytxid).'"'.((@$_POST['for'.$forentity]==$entitytxid) ? ' selected' : '').'>'.html($entityname).'</option>';

?>
								
									</select>
								</div>
<?php
	}
?>
								</div>
								<span id="helpBlock" class="help-block">The filter will only be applied to transactions which reference one or more of these entities. Leave the above options blank to apply this filter to all transactions.</span>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="testtxfiltercode" value="Test Compiling Filter Code Only">
							</div>
						</div>
						<div class="form-group">
							&nbsp;
						</div>
						<div class="form-group">
							<label for="sendfrom" class="col-sm-3 control-label">Test send address:</label>
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
							<label for="asset" class="col-sm-3 control-label">Test send asset:</label>
							<div class="col-sm-9">
							<select class="form-control" name="asset" id="asset">
<?php
	foreach ($usableassets as $asset)
		echo '<option value="'.html($asset).'"'.((@$_POST['asset']==$asset) ? ' selected' : '').'>'.html($asset).'</option>';
?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="to" class="col-sm-3 control-label">To address:</label>
							<div class="col-sm-9">
							<select class="form-control" name="to" id="to">
<?php
	foreach ($receiveaddresses as $address) {
		if ($address==$getinfo['burnaddress'])
			continue;
			
		echo '<option value="'.html($address).'"'.((@$_POST['to']==$address) ? ' selected' : '').'>'.format_address_html($address, @$keymyaddresses[$address], $labels).'</option>';
	}
?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="qty" class="col-sm-3 control-label">Quantity:</label>
							<div class="col-sm-9">
								<input class="form-control" name="qty" id="qty" placeholder="0.0" value="<?php echo @$_POST['qty']?>">
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="testtxfiltersend" value="Test Sending Asset with This Filter">
								&nbsp;
								<input type="checkbox" name="sendcallbacks" id="sendcallbacks" value="1" <?php echo @$_POST['sendcallbacks'] ? 'checked' : ''?>> <label class="control-label" for="sendcallbacks" style="font-weight:normal;">Display callback results</label>
							</div>
						</div>
						<div class="form-group">
							&nbsp;
						</div>
						<div class="form-group">
							<label for="rawtx" class="col-sm-3 control-label">Test raw transaction:</label>
							<div class="col-sm-9">
								<textarea class="form-control" style="font-family:monospace;" rows="12" name="rawtx" id="rawtx"><?php echo html($_POST['rawtx']);?></textarea>
								<span id="helpBlock" class="help-block">Raw transactions can be created using the <code>multichain-cli</code> command line tool and the <code>createrawsendfrom</code> or <code>createrawtransaction</code> command.</span>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="testtxfilterraw" value="Test Sending Raw Transaction with This Filter">
								&nbsp;
								<input type="checkbox" name="rawcallbacks" id="rawcallbacks" value="1" <?php echo @$_POST['rawcallbacks'] ? 'checked' : ''?>> <label class="control-label" for="rawcallbacks" style="font-weight:normal;">Display callback results</label>
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

	foreach ($admincreateaddresses as $address) 
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
								<input class="btn btn-default" type="submit" name="createtxfilter" value="Create as a New On-Chain Transaction Filter">
							</div>
						</div>
					</form>

				</div>
			</div>
