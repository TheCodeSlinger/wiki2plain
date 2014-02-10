#!/usr/bin/php
<?
	if( count( $argv ) != 3 ) {
		echo "usage: wiki2plain input-xml out-txt\n";
		die;
	}
	wiki2plain( $argv[1], $argv[2] );

	function wiki2plain( $xml_fn, $txt_fn ) {

		echo $xml_fn . "... ";
		$xml_fp = fopen( $xml_fn, "r" );
		if( $xml_fp === false  ) {
			echo "fail to open\n";
			die;
		}
		echo "ok\n";

		echo $txt_fn . "\n";
		$txt_fp = fopen( $txt_fn, "w" );
		if( $txt_fp === false  ) {
			echo "fail to open\n";
			die;
		}
		echo "ok\n";

		$line_cnt = 0;
		$page_cnt = 1;
		while( true ) {
			$tmp = fgets( $xml_fp );
			if( $tmp === false )	break;
			$line_cnt++;

			// read each page
			$str = "";
			if( strpos( $tmp, "<page>" ) !== false ) {
				while( true ) {
					$tmp2 = fgets( $xml_fp );
					if( $tmp2 === false ) {
						echo "invalid page format\n";
						die;
					}
					$line_cnt++;
					if( strpos( $tmp2, "</page>" ) !== false ) {
						break;
					}
					$str .= $tmp2;
				}
				$plain_str = plain( $str );
				fputs( $txt_fp, $plain_str );
				$page_cnt++;
			}

			if( ($page_cnt) % 100000 == 0 )	echo " $page_cnt pages ($line_cnt lines)\n";
			else if( ($page_cnt) % 10000 == 0 )	echo ".";

			if( $page_cnt >= 10 )	break;
		}
		$page_cnt--;

		echo "\n";
		echo "total: $page_cnt pages\n";
		return;
	}

	function plain( $str ) {
		return $str;
	}

?>
