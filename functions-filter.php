<?php
	
	function output_filter_test_callbacks($rawresponse)
	{
		echo '<div class="bg-info" style="width:100%; padding:1em;">';
		
		$response=json_decode($rawresponse);
		$callbacks=$response->result->callbacks;
		
		if (count($callbacks)) {
			echo '<div style="height:24em; overflow:scroll;">';
			
			foreach ($callbacks as $callback) {
				echo '<p>Callback <code>'.$callback->method.'(';
				foreach ($callback->params as $index => $param)
					echo ($index ? ', ' : '').html(json_encode($param));
				echo ')</code> returned';
			
				if ($callback->success) {
					$json=json_encode($callback->result, JSON_PRETTY_PRINT);

					echo (strpos($json, "\n")!==false) ? ':<br/>' : ' ';
					echo '<code style="white-space:pre;">'.html($json).'</code>';

				} else
					echo ' <code>undefined</code> because it failed with error '.html($callback->error->code).': '.html($callback->error->message);
			
				echo '</p>';
			}

			echo '</div>';

		} else
			echo 'No callbacks were made by this filter in this case';
		
		echo '</div>';
	}
	
	function output_txfilter_status($txfilter) // $txfilter must be obtained from listtxfilters with verbose=true
	{
		if (count($txfilter['pending'])) {
			$pending=$txfilter['pending'][0];
			echo 'Pending '.($pending['approve'] ? 'approval' : 'disapproval').' ('.$pending['required'].' more required)';
		} else
			echo $txfilter['approved'] ? 'Approved' : 'Not approved';
	}