<?php

function unmask($text) {
    $length = @ord($text[1]) & 127;
    if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8); 
	}
    elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14); 
	}
    else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6); 
	}
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i % 4];    
	}
    return $text;
}

function pack_data($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if($length <= 125) {
		$header = pack('CC', $b1, $length);
	}
        
    elseif($length > 125 && $length < 65536) {
		$header = pack('CCn', $b1, 126, $length);
	}
        
    elseif($length >= 65536) {
		$header = pack('CCNN', $b1, 127, $length);
	}
        
    return $header.$text;
}

function handshake($request_header,$sock, $host_name, $port) {
	$headers = array();
	$lines = preg_split("/\r\n/", $request_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)){
			$headers[$matches[1]] = $matches[2];
		}
	}

	$sec_key = $headers['Sec-WebSocket-Key'];
	$sec_accept = base64_encode(pack('H*', sha1($sec_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	$response_header  = "HTTP/1.1 101 Switching Protocols\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"Sec-WebSocket-Accept:$sec_accept\r\n\r\n";
	socket_write($sock,$response_header,strlen($response_header));
}