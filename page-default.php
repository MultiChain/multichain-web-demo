<?
	if (@$_POST['getnewaddress'])
		no_displayed_error_result($getnewaddress, multichain('getnewaddress'));
?>

			<div class="row">

				<div class="col-sm-6">
					<h3>My Node</h3>
<?
	$getinfo=multichain_getinfo();

	if (is_array($getinfo)) {
?>
					<table class="table table-bordered table-striped">
						<tr>
							<th>Name</th>
							<td><?=html($getinfo['chainname'])?></td>
						</tr>
						<tr>
							<th>Version</th>
							<td><?=html($getinfo['version'])?></td>
						</tr>
						<tr>
							<th>Protocol</th>
							<td><?=html($getinfo['protocolversion'])?></td>
						</tr>
						<tr>
							<th>Node address</th>
							<td><?=html($getinfo['nodeaddress'])?></td>
						</tr>
						<tr>
							<th>Blocks</th>
							<td><?=html($getinfo['blocks'])?></td>
						</tr>
						<tr>
							<th>Peers</th>
							<td><?=html($getinfo['connections'])?></td>
						</tr>
					</table>
<?	
	}
?>

					<h3>Connected Nodes</h3>
<?
	if (no_displayed_error_result($peerinfo, multichain('getpeerinfo'))) {
?>
					<table class="table table-bordered table-striped table-break-words">
<?
		foreach ($peerinfo as $peer) {
?>
						<tr>
							<th>Node IP address</th>
							<td><?=html(strtok($peer['addr'], ':'))?></td>
						</tr>
						<tr>
							<th>Handshake address</th>
							<td class="td-break-words small"><?=html($peer['handshake'])?></td>
						</tr>
						<tr>
							<th>Latency</th>
							<td><?=html(number_format($peer['pingtime'], 3))?> sec</td>
						</tr>
<?
		}
?>
					</table>
<?	
	}
?>
				</div>
				<div class="col-sm-6">
					<h3>My Addresses</h3>
			
<?
	if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
		$addressmine=array();
		
		foreach ($getaddresses as $getaddress)
			$addressmine[$getaddress['address']]=$getaddress['ismine'];
		
		$addresspermissions=array();
		
		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'all', implode(',', array_keys($addressmine)))
		))
			foreach ($listpermissions as $listpermission)
				$addresspermissions[$listpermission['address']][$listpermission['type']]=true;
		
		no_displayed_error_result($getmultibalances, multichain('getmultibalances', array(), array(), 0, true));
		
		$labels=multichain_labels();
	
		foreach ($addressmine as $address => $ismine) {
			if (count(@$addresspermissions[$address]))
				$permissions=implode(', ', @array_keys($addresspermissions[$address]));
			else
				$permissions='none';
				
			$label=@$labels[$address];
			$cansetlabel=$ismine && @$addresspermissions[$address]['send'];
			
			if ($ismine && !$cansetlabel)
				$permissions.=' (cannot set label)';
?>
						<table class="table table-bordered table-condensed table-break-words <?=($address==@$getnewaddress) ? 'bg-success' : 'table-striped'?>">
<?
			if (isset($label) || $cansetlabel) {
?>
							<tr>
								<th style="width:30%;">Label</th>
								<td><?=html(@$label)?><?
								
				if ($cansetlabel)
					echo (isset($label) ? ' &ndash; ' : '').
					'<a href="'.chain_page_url_html($chain, 'label', array('address' => $address)).'">'.
					(isset($label) ? 'change label' : 'Set label').
					'</a>';
				
								?></td>
							</tr>
<?
			}
?>
							<tr>
								<th style="width:30%;">Address</th>
								<td class="td-break-words small"><?=html($address)?><?=$ismine ? '' : ' (watch-only)'?></td>
							</tr>
							<tr>
								<th>Permissions</th>
								<td><?=html($permissions)?><?

					echo ' &ndash; <a href="'.chain_page_url_html($chain, 'permissions', array('address' => $address)).'">change</a>';

							?></td></tr>
<?
			if (isset($getmultibalances[$address])) {
				foreach ($getmultibalances[$address] as $addressbalance) {
?>
							<tr>
								<th><?=html($addressbalance['name'])?></th>
								<td><?=html($addressbalance['qty'])?></td>
							</tr>
<?
				}
			}
?>
						</table>
<?
		}
	}
?>
					<form class="form-horizontal" method="post" action="<?=chain_page_url_html($chain)?>">
						<div class="form-group">
							<div class="col-xs-12">
								<input class="btn btn-default" name="getnewaddress" type="submit" value="Get new address">
							</div>
						</div>
					</form>
				</div>
			</div>
