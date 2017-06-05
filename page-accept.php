<?php
	if (@$_POST['unlockoutputs'])
		if (no_displayed_error_result($result, multichain('lockunspent', true)))
			output_success_text('All outputs successfully unlocked');
	
	$decoded=null;
	
	function ask_offer_to_assets($askoffer)
	{
		$assets=array();
		
		foreach ($askoffer['assets'] as $asset)
			$assets[$asset['name']]=$asset['qty'];
		
		if (!count($assets))
			$assets=0; // to prevent it being converted to empty JSON array instead of object
		
		return $assets;
	}
	
	if (@$_POST['decodeoffer'] || @$_POST['completeoffer']) {
		if (no_displayed_error_result($decoded, multichain('decoderawexchange', $_POST['hex']))) {

			if (@$_POST['completeoffer']) {
				if (no_displayed_error_result($prepare, multichain('preparelockunspentfrom', $_POST['from'], ask_offer_to_assets($decoded['ask'])))) {
						// output_success_text('Exchange successfully prepared using transaction '.$prepare['txid']);
			
					if (no_displayed_error_result($rawexchange, multichain('appendrawexchange', $_POST['hex'],
						$prepare['txid'], $prepare['vout'], ask_offer_to_assets($decoded['offer'])))) {
			
						if (no_displayed_error_result($sendtxid, multichain('sendrawtransaction', $rawexchange['hex'])))
							output_success_text('Exchange successfully completed in transaction '.$sendtxid);
					}
				}
			}

		}
	}
	
?>

			<div class="row">

				<div class="col-sm-5">
					<h3>Available Balances</h3>
			
<?php
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
						<table class="table table-bordered table-condensed table-break-words <?php echo ($address==@$getnewaddress) ? 'bg-success' : 'table-striped'?>">
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
								<th style="width:20%;">Address</th>
								<td class="td-break-words small"><?php echo html($address)?></td>
							</tr>
<?php
					foreach ($allbalances as $balance) {
						$unlockedqty=floatval($assetunlocked[$balance['name']]);
						$lockedqty=$balance['qty']-$unlockedqty;
						
						if ($lockedqty>0)
							$haslocked=true;
						if ($unlockedqty>0)
							$keyusableassets[$balance['name']]=true;
?>
							<tr>
								<th><?php echo html($balance['name'])?></th>
								<td><?php echo html($unlockedqty)?><?php echo ($lockedqty>0) ? (' ('.$lockedqty.' locked)') : ''?></td>
							</tr>
<?php
					}
?>
						</table>
<?php
				}
			}
		}
	}
	
	if ($haslocked) {
?>
				<form class="form-horizontal" method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
					<input class="btn btn-default" type="submit" name="unlockoutputs" value="Unlock all outputs">
				</form>
<?php
	}
?>
				</div>
				
<?php
	if (is_array($decoded)) {
?>

				<div class="col-sm-7">
					<h3>Complete Offer</h3>
					
					<form class="form-horizontal" method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
					
<?php
		foreach ($decoded['offer']['assets'] as $index => $offer) {
?>
						<div class="form-group">
							<label class="col-sm-3 control-label"><?php echo $index ? '' : 'Offer'?>:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?php echo html($offer['name'])?> &ndash; <?php echo html($offer['qty'])?></p>
							</div>
						</div>
<?php
		}
		
		foreach ($decoded['ask']['assets'] as $index => $ask) {
?>
						<div class="form-group">
							<label class="col-sm-3 control-label"><?php echo $index ? '' : 'Ask'?>:</label>
							<div class="col-sm-9">
								<p class="form-control-static"><?php echo html($ask['name'])?> &ndash; <?php echo html($ask['qty'])?></p>
							</div>
						</div>
<?php
		}
?>
					
						<div class="form-group">
							<label for="from" class="col-sm-3 control-label">Use address:</label>
							<div class="col-sm-9">
							<select class="form-control" name="from" id="from">
<?php
	foreach ($usableaddresses as $address) {
?>
								<option value="<?php echo html($address)?>"><?php echo format_address_html($address, true, $labels)?></option>
<?php
	}
?>						
							</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="completeoffer" value="Complete Offer">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">Offer hexadecimal:</label>
							<div class="col-sm-9">
								<textarea class="form-control" rows="5" name="hex" readonly><?php echo html($_POST['hex'])?></textarea>
							</div>
						</div>
					</form>
				</div>

<?php
	} else {
?>
				
				<div class="col-sm-7">
					<h3>Decode Offer</h3>
					
					<form class="form-horizontal" method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
						<div class="form-group">
							<label for="hex" class="col-sm-3 control-label">Offer hexadecimal:</label>
							<div class="col-sm-9">
								<textarea class="form-control" rows="10" name="hex" id="hex"></textarea>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<input class="btn btn-default" type="submit" name="decodeoffer" value="Decode Offer">
							</div>
						</div>
					</form>

				</div>
<?php
	}
?>
			</div>
			