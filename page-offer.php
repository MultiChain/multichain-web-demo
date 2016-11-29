<?
	if (@$_POST['unlockoutputs'])
		if (no_displayed_error_result($result, multichain('lockunspent', true)))
			output_success_text('All outputs successfully unlocked');
	
	if (@$_POST['createoffer']) {
		if (no_displayed_error_result($prepare, multichain('preparelockunspentfrom',
				$_POST['from'], array($_POST['offerasset'] => floatval($_POST['offerqty']))))) {
			
			if (no_displayed_error_result($rawexchange, multichain('createrawexchange',
				$prepare['txid'], $prepare['vout'], array($_POST['askasset'] => floatval($_POST['askqty']))))) {
			
				output_success_text('Offer successfully prepared using transaction '.$prepare['txid'].' - please copy the raw offer below.');
				
				echo '<pre>'.html($rawexchange).'</pre>';
			}
		}
	}
?>

			<div class="row">

				<div class="col-sm-5">
					<h3>Available Balances</h3>
			
<?
	$sendaddresses=array();
	$usableaddresses=array();
	$keymyaddresses=array();
	$keyusableassets=array();
	$allassets=array();
	$haslocked=false;
	$getinfo=multichain_getinfo();
	$labels=array();
	
	if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
		))
			$sendaddresses=array_get_column($listpermissions, 'address');
			
		foreach ($getaddresses as $address)
			if ($address['ismine'])
				$keymyaddresses[$address['address']]=true;
				
		$labels=multichain_labels();

		if (no_displayed_error_result($listassets, multichain('listassets')))
			$allassets=array_get_column($listassets, 'name');

		foreach ($sendaddresses as $address) {
			if (no_displayed_error_result($allbalances, multichain('getaddressbalances', $address, 0, true))) {
				
				if (count($allbalances)) {
					$assetunlocked=array();

					if (no_displayed_error_result($unlockedbalances, multichain('getaddressbalances', $address, 0, false))) {
						if (count($unlockedbalances))
							$usableaddresses[]=$address;
							
						foreach ($unlockedbalances as $balance)
							$assetunlocked[$balance['name']]=$balance['qty'];
					}
					
					$label=@$labels[$address];
?>
						<table class="table table-bordered table-condensed table-break-words <?=($address==@$getnewaddress) ? 'bg-success' : 'table-striped'?>">
<?
			if (isset($label)) {
?>
							<tr>
								<th style="width:25%;">Label</th>
								<td><?=html($label)?></td>
							</tr>
<?
			}
?>
							<tr>
								<th style="width:25%;">Address</th>
								<td class="td-break-words small"><?=html($address)?></td>
							</tr>
<?
					foreach ($allbalances as $balance) {
						$unlockedqty=floatval($assetunlocked[$balance['name']]);
						$lockedqty=$balance['qty']-$unlockedqty;
						
						if ($lockedqty>0)
							$haslocked=true;
						if ($unlockedqty>0)
							$keyusableassets[$balance['name']]=true;
?>
							<tr>
								<th><?=html($balance['name'])?></th>
								<td><?=html($unlockedqty)?><?=($lockedqty>0) ? (' ('.$lockedqty.' locked)') : ''?></td>
							</tr>
<?
					}
?>
						</table>
<?
				}
			}
		}
	}
	
	if ($haslocked) {
?>
				<form class="form-horizontal" method="post" action="./?chain=<?=html($_GET['chain'])?>&page=<?=html($_GET['page'])?>">
					<input class="btn btn-default" type="submit" name="unlockoutputs" value="Unlock all outputs">
				</form>
<?
	}
?>
				</div>
				
				<div class="col-sm-7">
					<h3>Create Offer</h3>
					
					<form class="form-horizontal" method="post" action="./?chain=<?=html($_GET['chain'])?>&page=<?=html($_GET['page'])?>">
						<div class="form-group">
							<label for="from" class="col-sm-3 control-label">From address:</label>
							<div class="col-sm-9">
							<select class="form-control" name="from" id="from">
<?
	foreach ($usableaddresses as $address) {
?>
								<option value="<?=html($address)?>"><?=format_address_html($address, true, $labels)?></option>
<?
	}
?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="offerasset" class="col-sm-3 control-label">Offer asset:</label>
							<div class="col-sm-9">
							<select class="form-control" name="offerasset" id="offerasset">
<?
	foreach ($keyusableassets as $asset => $dummy) {
?>
								<option value="<?=html($asset)?>"><?=html($asset)?></option>
<?
	}
?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="offerqty" class="col-sm-3 control-label">Offer qty:</label>
							<div class="col-sm-9">
								<input class="form-control" name="offerqty" id="offerqty" placeholder="0.0">
							</div>
						</div>
						<div class="form-group">
							<label for="askasset" class="col-sm-3 control-label">Ask asset:</label>
							<div class="col-sm-9">
							<select class="form-control" name="askasset" id="askasset">
<?
	foreach ($allassets as $asset) {
?>
								<option value="<?=html($asset)?>"><?=html($asset)?></option>
<?
	}
?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<label for="askqty" class="col-sm-3 control-label">Ask qty:</label>
							<div class="col-sm-9">
								<input class="form-control" name="askqty" id="askqty" placeholder="0.0">
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="createoffer" value="Create Offer">
							</div>
						</div>
					</form>

				</div>
			</div>