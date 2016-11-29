<?
	$getinfo=multichain_getinfo();
	$labels=multichain_labels();

	if (no_displayed_error_result($blockhash, multichain('getblockhash', 0))) {
		if (no_displayed_error_result($block, multichain('getblock', $blockhash))) {
			$address=$block['miner'];
			
			if (isset($labels[$address]))
				output_success_text('Genesis miner already has a label');

			elseif (no_displayed_error_result($labeltxid, multichain(
				'sendwithmetadatafrom', $address, $getinfo['burnaddress'], 0, bin2hex($address.'=Admin')
			)))
				output_success_text('Added genesis miner label in '.$labeltxid);
		}
	}
?>
