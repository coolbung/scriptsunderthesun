<?php

class DisqusParser {

	function parsefile($disqus_file, $unique=true) {

		// Load the Disqus file
		if (!$disqus_file) {
			throw new Exception('Please specify export file');
		}
		$xml = simplexml_load_file($disqus_file);

		// Parse XML to get links
		foreach ($xml as $node) {
			if ($node->link) { $links[] = $node->link; }
		}
		
		if (!$unique) return $links;
		
		$unique_links = array_unique($links);
		
		return $unique_links;
	}
	
	function testlinks($linkarray, $limit) {

		// Test links for 301/404 errors
		$c=0;
		foreach ($linkarray as $link) {
			
			if ($limit > 0 && $c == $limit) {
				break;
			}
			
			echo "Testing {$link} \n";
			echo $status = $this->curl_get_status($link);
			
			$checked_links[] = array($link, $status);
			
			echo "\n\n";
			
			$c++;
		}

		$this->writecsv($checked_links);
	}

	function curl_get_status($url) {
		
		if (!$url) return false;
		
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		$result = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $http_status;
	}

	function writecsv($list, $filename='tested_links.csv') {

		$fp = fopen($filename, 'w');

		foreach ($list as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);
	}
	
	function parse($file, $unique, $limit) {
		$links = $this->parsefile($file, $unique);
		$this->testlinks($links, $limit);
	}
}

$disqus = new DisqusParser;
$disqus->parse($argv[1], true, $argv[2]);
