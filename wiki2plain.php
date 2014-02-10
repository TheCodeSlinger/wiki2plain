#!/usr/bin/php
<?

//////////////////////////////////////////
if( count( $argv ) != 3 ) {
	echo "usage: wiki2plain input-xml out-txt\n";
	die;
}

//////////////////////////////////////////
# start
wiki2plain( $argv[1], $argv[2] );
//////////////////////////////////////////





//////////////////////////////////////////
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

			if( $page_cnt == 4 ) {
			fputs( $txt_fp, $plain_str );
			break;
			}

			$page_cnt++;
		}

		if( ($page_cnt) % 100000 == 0 )	echo " $page_cnt pages ($line_cnt lines)\n";
		else if( ($page_cnt) % 10000 == 0 )	echo ".";

	}
	$page_cnt--;

	echo "\n";
	echo "total: $page_cnt pages\n";
	return;
}

function plain( $str ) {
	$str = strip_head( $str );
	$str = strip_brace( $str );
	$str = strip_wikitag( $str );
	$str = strip_htmltag( $str );
	return trim( $str );
}


function strip_head( $str ) {
#	preg_replace("/( www.)([\w\.-]+)/e", "'<a href=\"http://\\0\" target=\"_blank\">\\0</a>'", $str);
	$pattern = array();
	$replace = array();

	$pattern[] = "/(\s+)<ns>(.*)<\/ns>/e"; $replace[] = "";
	$pattern[] = "/(\s+)<id>(.*)<\/id>/e"; $replace[] = "";
	$pattern[] = "/(\s+)<sha1>(.*)<\/sha1>/e"; $replace[] = "";
	$pattern[] = "/(\s+)<model>(.*)<\/model>/e"; $replace[] = "";
	$pattern[] = "/(\s+)<format>(.*)<\/format>/e"; $replace[] = "";
	$pattern[] = "/(\s+)<parentid>(.*)<\/parentid>/e"; $replace[] = "";
	$pattern[] = "/(\s+)<timestamp>(.*)<\/timestamp>/e"; $replace[] = "";
	$pattern[] = "/(\s+)<contributor>(.*)<\/contributor>/es"; $replace[] = "";
	$pattern[] = "/(\s+)<comment>(.*)<\/comment>/es"; $replace[] = "";

	$pattern[] = "/(\s+)<title>(.*)<\/title>/s"; $replace[] = "$2";
	$pattern[] = "/(\s+)<revision>(.*)<\/revision>/s"; $replace[] = "$2";
	$pattern[] = "/(\s+)<text (.*)>(.*)<\/text>/s"; $replace[] = "$3";
	//$pattern[] = "/(\s+)<revision>(.*)<\/revision>/e"; $replace[] = "";

	$str = preg_replace( $pattern, $replace, $str );
	return $str;
}

function strip_brace( $str ) {
	while( true ) {
		$pos = strpos( $str, "}}" );
		if( $pos === false ) break;

		$pos_left = false;
		for( $i=$pos; $i>=1; $i-- ) {
			if( $str[$i] == "{" && $str[$i-1] == "{" ) {
				$pos_left = $i-1;
				break;
			}
		}
		if( $pos_left === false )	break;

		$str = substr( $str, 0, $pos_left ) .  substr( $str, $pos+2 );
	}

	while( true ) {
		$pos = strpos( $str, "]]" );
		if( $pos === false ) break;

		$pos_left = false;
		for( $i=$pos; $i>=1; $i-- ) {
			if( $str[$i] == "[" && $str[$i-1] == "[" ) {
				$pos_left = $i-1;
				break;
			}
		}
		if( $pos_left === false )	break;

		$mark_str = substr( $str, $pos_left+2, $pos-$pos_left-2 );
		if( strpos( $mark_str, "분류:" ) === 0 ) {
			$mark_str = "";
		}
		else {
			$ipos = strrpos( $mark_str, "|" );
			if( $ipos !== false ) {
				$mark_str = substr( $mark_str, $ipos+1 );
			}
		}


		$str = substr( $str, 0, $pos_left ) . $mark_str . substr( $str, $pos+2 );
	}
	return $str;
}



function strip_wikitag( $str ) {
	$pattern = array();
	$replace = array();

	$pattern[] = "/==== (.*) ====/"; $replace[] = "$1";
	$pattern[] = "/=== (.*) ===/"; $replace[] = "$1";
	$pattern[] = "/== (.*) ==/"; $replace[] = "$1";
	$pattern[] = "/'''/"; $replace[] = "";

//	$pattern[] = "/\[\[(.*)\]\]/"; $replace[] = "($1)";
	$str = preg_replace( $pattern, $replace, $str );
	return $str;
}

function strip_htmltag( $str ) {
	$pattern = array();
	$replace = array();

	$pattern[] = "/&quot;/"; $replace[] = "\"";
	$pattern[] = "/&lt;references\/&gt;/"; $replace[] = "";
	$pattern[] = "/&lt;ref&gt;&lt;\/ref&gt;/"; $replace[] = "";

//	$pattern[] = "/\[\[(.*)\]\]/"; $replace[] = "($1)";
	$str = preg_replace( $pattern, $replace, $str );


	while( true ) {
		$pos = strpos( $str, "&lt;ref&gt;[" );
		if( $pos === false )	break;
		$pos2 = strpos( $str, "]&lt;/ref&gt;", $pos+3 );
		if( $pos2 === false )	break;

		$str = substr( $str, 0, $pos ) . " " . substr( $str, $pos2+13 );
	}

	$str = str_replace( "\n\n", "\n", $str );

	return $str;
}


?>
