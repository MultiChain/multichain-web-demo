<?php
	require_once 'functions.php';
	
	$config=read_config();
	set_multichain_chain($config[$_GET['chain']]);

	if (no_displayed_error_result($data, multichain('gettxoutdata', $_GET['txid'], (int)$_GET['vout']))) {
		$file=txout_bin_to_file(pack('H*', $data));
		
		if (is_array($file)) {

			if (strlen($file['mimetype']))
				header('Content-Type: '.$file['mimetype']);
			
			if (strlen($file['filename'])) {
				// for compatibility with HTTP headers and all browsers
				$filename=preg_replace('/[^A-Za-z0-9 \\._-]+/', '', $file['filename']);
				header('Content-Disposition: inline; filename="'.$filename.'"');
			}
			
			echo $file['content'];
		
		} else
			echo 'File not formatted as expected';
	}