<?
	define('const_max_retrieve_items', 1000);
	
	$labels=multichain_labels();

	no_displayed_error_result($liststreams, multichain('liststreams', '*', true));
	no_displayed_error_result($getinfo, multichain('getinfo'));

	$subscribed=false;
	$viewstream=null;
	
	foreach ($liststreams as $stream) {
		if ($_POST['subscribe_'.$stream['streamref']])
			if (no_displayed_error_result($result, multichain('subscribe', $stream['streamref']))) {
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

				<div class="col-sm-4"><form method="post" action="./?chain=<?=html($_GET['chain'])?>&page=<?=html($_GET['page'])?>">
				
<?
	for ($subscribed=1; $subscribed>=0; $subscribed--) {
?>

					<h3><?=$subscribed ? 'Subscribed streams' : 'Other streams'?></h3>
			
<?
		foreach ($liststreams as $stream)
			if ($stream['subscribed']==$subscribed) {
?>
						<table class="table table-bordered table-condensed table-break-words table-striped">
							<tr>
								<th style="width:30%;">Name</th>
<?
				if ($subscribed) {
?>	
								<td><a href="./?chain=<?=html($_GET['chain'])?>&page=<?=html($_GET['page'])?>&stream=<?=html($stream['createtxid'])?>"><?=html($stream['name'])?></a></td>
<?
				} else {
					$parts=explode('-', $stream['streamref']);
					if (is_numeric($parts[0]))
						$suffix=' ('.($getinfo['blocks']-$parts[0]+1).' blocks)';
					else
						$suffix='';
?>	
								<td><?=html($stream['name'])?> &nbsp; <input class="btn btn-default btn-xs" type="submit" name="subscribe_<?=html($stream['streamref'])?>" value="Subscribe<?=$suffix?>"></td>
<?
				}
?>
							</tr>
							<tr>
								<th>Created by</th>
								<td class="td-break-words small"><?=format_address_html($stream['creators'][0], false, $labels)?></td>
							</tr>
<?
				if ($subscribed) {
?>
							<tr>
								<th>Items</th>
								<td><?=$stream['items']?></td>
							</tr>
							<tr>
								<th>Publishers</th>
								<td><?=$stream['publishers']?></td>
							</tr>
<?
				}
?>
						</table>
<?
		}
	}
?>
				</form></div>
				
<?
	
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
					<h3>Stream: <?=html($viewstream['name'])?> &ndash; <?=count($items)?> of <?=$countitems?> <?=($countitems==1) ? 'item' : 'items'?><?=html($suffix)?></h3>
<?
			$oneoutput=false;
			$items=array_reverse($items); // show most recent first
			
			foreach ($items as $item) {
				$oneoutput=true;
?>
					<table class="table table-bordered table-condensed table-striped table-break-words">
						<tr>
							<th style="width:15%;">Publishers</th>
							<td><?
							
				foreach ($item['publishers'] as $publisher) {
					$link='./?chain='.$_GET['chain'].'&page='.$_GET['page'].'&stream='.$viewstream['createtxid'].'&publisher='.$publisher;
					
							?><?=format_address_html($publisher, false, $labels, $link)?><?
							
				}
							
							?></td>
						</tr>
						<tr>
							<th>Key</td>
							<td><a href="./?chain=<?=html($_GET['chain'])?>&page=<?=html($_GET['page'])?>&stream=<?=html($viewstream['createtxid'])?>&key=<?=html($item['key'])?>"><?=html($item['key'])?></a></td>
						</tr>
						<tr>
							<th>Data</td>
							<td><?
				
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
							<th>Added</td>
							<td><?=gmdate('Y-m-d H:i:s', isset($item['blocktime']) ? $item['blocktime'] : $item['time'])?> GMT<?=isset($item['blocktime']) ? ' (confirmed)' : ''?></td>
						</tr>
					</table>
<?
				}
				
			if (!$oneoutput)
				echo '<p>No items in stream</p>';
?>				
				</div>
				
<?
		}
	}
?>