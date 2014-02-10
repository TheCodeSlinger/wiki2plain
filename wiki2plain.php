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

	//		if( $page_cnt == 6700 ) {
			if( strpos( $str, "백남준" ) !== false ) {
			$plain_str = plain( $str );
			fputs( $txt_fp, "#### $line_cnt ####\n" );
			fputs( $txt_fp, $plain_str );
			fputs( $txt_fp, "\n\n" );
			break; }

			$page_cnt++;
		}

		if( ($page_cnt) % 1000 == 0 )	echo " $page_cnt pages ($line_cnt lines)\n";
		else if( ($page_cnt) % 100 == 0 )	echo ".";

	}
	$page_cnt--;

	echo "\n";
	echo "total: $page_cnt pages\n";
	return;
}

function plain( $str ) {
//	return $str;
	$str = strip_preproc( $str );
	$str = strip_head( $str );
//	return $str;
	$str = strip_brace( $str );
	$str = strip_wikitag( $str );
	$str = strip_htmltag( $str );
	$str = strip_postproc( $str );
	return trim( $str );
}

function remove_bound( $str, $begin, $end )
{
	while( true ) {
		$pos = strpos( $str, $begin ); if( $pos === false )	break;
		$pos2 = strpos( $str, $end, $pos+strlen($begin) ); if( $pos2 === false )	break;
		//$str = substr( $str, 0, $pos ) . substr( $str, $pos+strlen($begin), $pos2-($pos+strlen($begin) ) ) . substr( $str, $pos2+strlen($end) );
		$str = substr( $str, 0, $pos ) . substr( $str, $pos2+strlen($end) );
		//echo "[". substr( $str, $pos+strlen($begin), $pos2-($pos+strlen($begin) ) ) ."]\n";
	}
	return $str;
}

function strip_preproc( $str ) {
	$str = str_replace( "\r", "",$str );
	$str = remove_bound( $str, "&lt;math&gt;", "&lt;/math&gt;" );


	return $str;
}
function strip_postproc( $str ) {
	$str = str_replace( "#REDIRECT ", "\n",$str );
	$str = str_replace( "#REDIRECT:", "\n",$str );

	$arr = explode( "\n", $str );
	$str = "";
	for( $i=0; $i<count( $arr ); $i++ ) {
		$tmp = trim( $arr[$i] );
		if( strlen($tmp) == 0  )	continue;
		if( $tmp == "*" )	continue;
		if( $tmp == "**" )	continue;
		if( $tmp == "***" )	continue;
		if( $tmp == ":" )	continue;
		else $str .= $tmp . "\n";
	}

	return $str;
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
	$pattern[] = "/(\s+)<minor \/>/e"; $replace[] = "";
	$pattern[] = "/(\s+)<contributor>(.*)<\/contributor>/es"; $replace[] = "";
	$pattern[] = "/(\s+)<comment>(.*)<\/comment>/e"; $replace[] = "";

	$pattern[] = "/(\s+)<title>(.*)<\/title>/s"; $replace[] = "$2\n";
	$pattern[] = "/(\s+)<redirect title=\"(.*)\" \/>/s"; $replace[] = "";
	$pattern[] = "/(\s+)<revision>(.*)<\/revision>/s"; $replace[] = "$2";
	$pattern[] = "/<text (.*)>(.*)<\/text>/s"; $replace[] = "$2";
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
		if( strpos( $mark_str, ":" ) !== false ) { $mark_str = ""; }
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

	$pattern[] = "/====(.*)====/"; $replace[] = "$1";
	$pattern[] = "/===(.*)===/"; $replace[] = "$1";
	$pattern[] = "/==(.*)==/"; $replace[] = "$1";
	$pattern[] = "/=(.*)=/"; $replace[] = "$1";
	$pattern[] = "/'''/"; $replace[] = "";
	$pattern[] = "/''/"; $replace[] = "";

//	$pattern[] = "/\[\[(.*)\]\]/"; $replace[] = "($1)";
	$str = preg_replace( $pattern, $replace, $str );

	$str = remove_bound( $str, "{|", "|}" );

	return $str;
}

function strip_htmltag( $str ) {
	$pattern = array();
	$replace = array();

	$pattern[] = "/&quot;/"; $replace[] = "\"";
	$pattern[] = "/&lt;/"; $replace[] = "<";
	$pattern[] = "/&gt;/"; $replace[] = ">";
	$pattern[] = "/<references(.*)\/>/"; $replace[] = "";
	$pattern[] = "/<br \/>/"; $replace[] = "\n";
	$pattern[] = "/<br>/"; $replace[] = "\n";
	$pattern[] = "/<sup>/"; $replace[] = "";
	$pattern[] = "/<\/sup>/"; $replace[] = "";
	$pattern[] = "/<sub>/"; $replace[] = "";
	$pattern[] = "/<\/sub>/"; $replace[] = "";
	$pattern[] = "/<small>/"; $replace[] = "";
	$pattern[] = "/<\/small>/"; $replace[] = "";
	$pattern[] = "/<onlyinclude>/"; $replace[] = "";
	$pattern[] = "/<\/onlyinclude>/"; $replace[] = "";

	$pattern[] = "/(\s+):<math>(.*)<\/math>/s"; $replace[] = "";
	$pattern[] = "/(\s+)<gallery(.*)<\/gallery>/s"; $replace[] = "";
	$pattern[] = "/(\s+)<center>(.*)<\/center>/s"; $replace[] = "";
	$str = preg_replace( $pattern, $replace, $str );

	$str = remove_bound( $str, "<ref ", "/>" );
	$str = remove_bound( $str, "<ref>", "</ref>" );
	$str = remove_bound( $str, "<ref ", "</ref>" );
	$str = remove_bound( $str, "<!--", "-->" );

	while( true ) {
		$pos = strpos( $str, "[http" ); if( $pos === false )	break;
		$pos2 = strpos( $str, " ", $pos+3 ); if( $pos2 === false )	break;
		$pos3 = strpos( $str, "]", $pos2+1 ); if( $pos3 === false )	break;
		$str = substr( $str, 0, $pos ) . substr( $str, $pos2+1, $pos3-$pos2-1 ) . substr( $str, $pos3+1 );
	}


	$str = strip_tags( $str );
	$str = str_replace( "\n\n", "\n", $str );

	return $str;
}


?>
