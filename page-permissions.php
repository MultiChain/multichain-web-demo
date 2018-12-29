<?php
	$const_permission_names=array(
		'connect' => 'Connect',
		'send' => 'Send',
		'receive' => 'Receive',
		'create' => 'Create',
		'issue' => 'Issue',
		'mine' => 'Mine',
		'activate' => 'Activate',
		'admin' => 'Admin',
	);
	
	if (multichain_has_custom_permissions())
		$const_permission_names=array_merge($const_permission_names, array(
			'high1' => 'High 1',
			'high2' => 'High 2',
			'high3' => 'High 3',
			'low1' => 'Low 1',
			'low2' => 'Low 2',
			'low3' => 'Low 3',
		));
	
	if (@$_POST['grantrevoke']) {
		$permissions=array();
		
		foreach ($const_permission_names as $type => $label)
			if (@$_POST[$type])
				$permissions[]=$type;
		
		if ($_POST['operation']=='grant')
			$success=no_displayed_error_result($permissiontxid, multichain('grantfrom',
				$_POST['from'], $_POST['to'], implode(',', $permissions)));

		elseif ($_POST['operation']=='revoke')
			$success=no_displayed_error_result($permissiontxid, multichain('revokefrom',
				$_POST['from'], $_POST['to'], implode(',', $permissions)));
				
		if ($success)
			output_success_text('Permissions successfully changed in transaction '.$permissiontxid);

		$to=$_POST['to'];

	} else
		$to=@$_GET['address'];

	$adminaddresses=array();
	$keymyaddresses=array();
	$getinfo=multichain_getinfo();
	$labels=array();

	if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {

		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'admin,activate', implode(',', array_get_column($getaddresses, 'address')))
		))
			$adminaddresses=array_unique(array_get_column($listpermissions, 'address'));

		$labels=multichain_labels();

		foreach ($getaddresses as $address)
			if ($address['ismine'])
				$keymyaddresses[$address['address']]=true;
	}
?>

			<div class="row">

				<div class="col-sm-5">
					<h3>Current Permissions</h3>
			
<?php
	if (no_displayed_error_result($listpermissions, multichain('listpermissions'))) {
		
		$addresspermissions=array();
		
		foreach ($keymyaddresses as $address => $dummy)
			$addresspermissions[$address]=array(); // ensure all local addresses shown as well
		
		foreach ($listpermissions as $permission)
			$addresspermissions[$permission['address']][$permission['type']]=true;
		
		foreach ($addresspermissions as $address => $permissions) {
			if ($address==$getinfo['burnaddress'])
				continue;
			
			if (count($permissions))
				$permissions_text=implode(', ', array_keys($permissions));
			else
				$permissions_text='none';
				
			$label=@$labels[$address];
?>
						<table class="table table-bordered table-condensed table-break-words <?php echo ($address==@$_POST['to']) ? 'bg-success' : 'table-striped'?>">
<?php
			if (isset($label)) {
?>
							<tr>
								<th style="width:25%;">Label</th>
								<td><?php echo html($label)?></td>
							</tr>
<?php
			}
?>
							<tr>
								<th style="width:25%;">Address</th>
								<td class="td-break-words small"><?php echo html($address)?><?php echo @$keymyaddresses[$address] ? ' (local)' : ''?></td>
							</tr>
							<tr>
								<th>Permissions</th>
								<td><?php echo html($permissions_text)?></td>
							</tr>
						</table>
<?php
		}
	}
?>
				</div>
				
				<div class="col-sm-7">
					<h3>Change Permissions</h3>
					
					<form class="form-horizontal" method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
						<div class="form-group">
							<label for="from" class="col-sm-3 control-label">Admin address:</label>
							<div class="col-sm-9">
							<select class="form-control col-sm-6" name="from" id="from">
<?php
	foreach ($adminaddresses as $address) {
?>
								<option value="<?php echo html($address)?>"><?php echo format_address_html($address, true, $labels)?></option>
<?php
	}
?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="to" class="col-sm-3 control-label">For address:</label>
							<div class="col-sm-9">
								<input class="form-control" name="to" id="to" placeholder="1..." value="<?php echo html($to)?>">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Operation:</label>
							<div class="col-sm-9">
								<label class="radio-inline">
									<input type="radio" name="operation" id="operation" value="grant" checked> Grant
								</label>
								<label class="radio-inline">
									<input type="radio" name="operation" id="operation" value="revoke"> Revoke
								</label>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Permissions:</label>
							<div class="col-sm-9">
<?php
	foreach ($const_permission_names as $type => $label) {
		if ( ($type=='create') || ($type=='activate') || ($type=='high1') || ($type=='low1') )
			echo '<br/>';
?> 
								<label class="checkbox-inline">
									<input type="checkbox" name="<?php echo html($type)?>" value="1"> <?php echo html($label)?> &nbsp;
								</label>
<?php
	}
?>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="grantrevoke" value="Change Permissions">
							</div>
						</div>
					</form>

				</div>
			</div>