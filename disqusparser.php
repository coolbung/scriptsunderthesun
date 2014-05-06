<?php

class DisqusParser {

	function parsefile_txt($disqus_file, $unique=true) {

		// Load the Disqus file
		if (!$disqus_file) {
			throw new Exception('Please specify export file');
		}
		$contents = file_get_contents($disqus_file);
		$links = explode("\n", $contents);

		if (!$unique) return $links;
		
		$unique_links = array_unique($links);
		
		return $unique_links;
	}
	
	function parsefile_xml($disqus_file, $unique=true) {

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
			$link = trim($link, '/');
			
			if ($limit > 0 && $c == $limit) {
				break;
			}
			
			echo "Testing {$link}";
			$op = $this->curl_get_status($link);
			
			$checked_links[] = array_merge(array(trim($link)), $op);
			
			echo "\n\n";
			
			$c++;
		}

		$this->writecsv($checked_links);
	}

	function curl_get_status($url) {
		
		if (!$url) return false;
		
		$op = array();
		
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$result = curl_exec($ch);
		$op['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($op['status'] == 301 || $op['status'] == 302) {
			
			$hdrs = explode("\n", $result);
			foreach($hdrs as $hdr) {
				if (strstr($hdr, 'Location')) {
					$op['location'] = $this->getlocation($hdr);
				}
			}
			echo "New Location : " . $op['location'] . "\n";
			
		} else {
			$op['location'] = 'NoChange';
		}
		
		return $op;
	}
	
	function getlocation($hdr) {
		$pcs = explode(':', $hdr);
		array_shift($pcs);
		return trim(implode(':', $pcs));
	}

	function writecsv($list, $filename='tested_links.csv') {

		$fp = fopen($filename, 'w');

		foreach ($list as $fields) {
			fputcsv($fp, $fields);
		}

		fclose($fp);
	}
	
	function parse($file, $unique, $limit) {
		$links = $this->parsefile_txt($file, $unique);
		$this->testlinks($links, $limit);
	}
}

$limit = isset($argv[2]) ? intval($argv[2]) : 0;
$disqus = new DisqusParser;
$disqus->parse($argv[1], true, $limit);
