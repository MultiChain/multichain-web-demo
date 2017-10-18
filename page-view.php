<?php
	define('const_max_retrieve_items', 1000);
	
	$labels=multichain_labels();

	no_displayed_error_result($liststreams, multichain('liststreams', '*', true));
	no_displayed_error_result($getinfo, multichain('getinfo'));

	$subscribed=false;
	$viewstream=null;

	$_GET['stream'] = isset($_GET['stream']) ? $_GET['stream'] : "";
	
	foreach ($liststreams as $stream) {
		if (isset($_POST['subscribe_'.$stream['createtxid']]))
			if (no_displayed_error_result($result, multichain('subscribe', $stream['createtxid']))) {
				output_success_text('Successfully subscribed to stream: '.$stream['name']);
				$subscribed=true;
			}
			
		if ($_GET['stream']==$stream['createtxid'])
			$viewstream=$stream;
	}			
			
	if ($subscribed) // reload streams list
		no_displayed_error_result($liststreams, multichain('liststreams', '*', true));

?>

			<div class="row">

				<div class="col-sm-4"><form method="post" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
				
<?php
	for ($subscribed=1; $subscribed>=0; $subscribed--) {
?>

					<h3><?php echo $subscribed ? 'Subscribed streams' : 'Other streams'?></h3>
			
<?php
		foreach ($liststreams as $stream)
			if ($stream['subscribed']==$subscribed) {
?>
						<table class="table table-bordered table-condensed table-break-words table-striped">
							<tr>
								<th style="width:30%;">Name</th>
<?php
				if ($subscribed) {
?>	
								<td><a href="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>&stream=<?php echo html($stream['createtxid'])?>"><?php echo html($stream['name'])?></a></td>
<?php
				} else {
					$parts=explode('-', $stream['streamref']);
					if (is_numeric($parts[0]))
						$suffix=' ('.($getinfo['blocks']-$parts[0]+1).' blocks)';
					else
						$suffix='';
?>	
								<td><?php echo html($stream['name'])?> &nbsp; <input class="btn btn-default btn-xs" type="submit" name="subscribe_<?php echo html($stream['createtxid'])?>" value="Subscribe<?php echo $suffix?>"></td>
<?php
				}
?>
							</tr>
							<tr>
								<th>Created by</th>
								<td class="td-break-words small"><?php echo format_address_html($stream['creators'][0], false, $labels)?></td>
							</tr>
<?php
				if ($subscribed) {
?>
							<tr>
								<th>Items</th>
								<td><?php echo $stream['items']?></td>
							</tr>
							<tr>
								<th>Publishers</th>
								<td><?php echo $stream['publishers']?></td>
							</tr>
<?php
				}
?>
						</table>
<?php
		}
	}
?>
				</form></div>
				
<?php
	
	if (isset($viewstream)) {
		if (isset($_GET['key'])) {
			$success=no_displayed_error_result($items, multichain('liststreamkeyitems', $viewstream['createtxid'], $_GET['key'], true, const_max_retrieve_items));
			$success=$success && no_displayed_error_result($keysinfo, multichain('liststreamkeys', $viewstream['createtxid'], $_GET['key']));
			$countitems=$keysinfo[0]['items'];
			$suffix=' with key: '.$_GET['key'];
			
		} elseif (isset($_GET['publisher'])) {
			$success=no_displayed_error_result($items, multichain('liststreampublisheritems', $viewstream['createtxid'], $_GET['publisher'], true, const_max_retrieve_items));
			$success=$success && no_displayed_error_result($publishersinfo, multichain('liststreampublishers', $viewstream['createtxid'], $_GET['publisher']));
			$countitems=$publishersinfo[0]['items'];
			$suffix=' with publisher: '.$_GET['publisher'];
		
		} else {
			$success=no_displayed_error_result($items, multichain('liststreamitems', $viewstream['createtxid'], true, const_max_retrieve_items));
			$countitems=$viewstream['items'];
			$suffix='';
		}
			
		if ($success) {		
?>
				
				<div class="col-sm-8">
					<h3>Stream: <?php echo html($viewstream['name'])?> &ndash; <?php echo count($items)?> of <?php echo $countitems?> <?php echo ($countitems==1) ? 'item' : 'items'?><?php echo html($suffix)?></h3>
<?php
			$oneoutput=false;
			$items=array_reverse($items); // show most recent first
			
			foreach ($items as $item) {
				$oneoutput=true;
?>
					<table class="table table-bordered table-condensed table-striped table-break-words">
						<tr>
							<th style="width:15%;">Publishers</th>
							<td><?php
							
				foreach ($item['publishers'] as $publisher) {
					$link='./?chain='.$_GET['chain'].'&page='.$_GET['page'].'&stream='.$viewstream['createtxid'].'&publisher='.$publisher;
					
							?><?php echo format_address_html($publisher, false, $labels, $link)?><?php
							
				}
							
							?></td>
						</tr>
						<tr>
							<th>Key</th>
							<td><a href="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>&stream=<?php echo html($viewstream['createtxid'])?>&key=<?php echo html($item['key'])?>"><?php echo html($item['key'])?></a></td>
						</tr>
						<tr>
							<th>Data</th>
							<td><?php
				
				if (is_array($item['data'])) { // long data item
					if (no_displayed_error_result($txoutdata, multichain('gettxoutdata', $item['data']['txid'], $item['data']['vout'], 1024))) // get prefix only for file name
						$binary=pack('H*', $txoutdata);
					else
						$binary='';
						
					$size=$item['data']['size'];
				
				} else {
					$binary=pack('H*', $item['data']);
					$size=strlen($binary);
				}
				
				$file=txout_bin_to_file($binary);
					
				if (is_array($file))
					echo '<a href="./download-file.php?chain='.html($_GET['chain']).'&txid='.html($item['txid']).'&vout='.html($item['vout']).'">'.
							(strlen($file['filename']) ? html($file['filename']) : 'Download').
							'</a>'.' ('.number_format(ceil($size/1024)).' KB)'; // ignore first few bytes of size
				else
					echo html($binary);
					
							?></td>
						</tr>
						<tr>
							<th>Added</th>
							<td><?php echo gmdate('Y-m-d H:i:s', isset($item['blocktime']) ? $item['blocktime'] : $item['time'])?> GMT<?php echo isset($item['blocktime']) ? ' (confirmed)' : ''?></td>
						</tr>
					</table>
<?php
				}
				
			if (!$oneoutput)
				echo '<p>No items in stream</p>';
?>				
				</div>
				
<?php
		}
	}
?>
