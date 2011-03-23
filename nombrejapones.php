<?
/*
	Tu nombre en japonés
	Plugin para Botize.com
	Por Alejandro Cremades Rocamora (@karawapo)
	Información: http://www.pepinismo.net/nombrejapones/
*/
// Poner a 1 para ver las diferentes fases del parseo
// Poner a 2 para además ver las conversiones de sílabas
$debug = 0;
mb_internal_encoding("UTF-8");
$entrada = trim($_POST['tweet']);
if (isset($_POST['account'])){
	$botname = trim($_POST['account']);
} else {
	$botname = "nombrejapones";
}
//$nombre = file_get_contents("php://stdin");
//$entrada = "@nombrejapones Ungüento Paragüístico";
$pattern = '/^@'.$botname.'/'; 
$pattern2 = '/@'.$botname.'/'; 
$usage = 0;

// Solo convertiremos a katakana los tweets que tengan el nombre 
// del bot al principio,
// o que no lo tengan en ninguna parte (los DM)
if (!preg_match($pattern, strtolower($entrada))
		&& preg_match($pattern2, strtolower($entrada))){
	$usage = 1;
}

// Quitamos el nombre del bot del mensaje
$pattern = '@'.$botname; 
$tweet = trim(mb_eregi_replace($pattern, '', $entrada));

// Solo queremos mensajes a los que les quede algo 
// después de quitar el nombre del bot
$pattern = '/[a-zA-ZñÑá-úüÁ-ÚÜ]+/';
if (!preg_match($pattern, $tweet)){
	$usage = 1;
}

// Soltando un tweet de "Usage:" si la pregunta no cumple los requisitos
if ($usage == 1){
	echo "Para saber cómo se escribe tu nombre en japonés envía: @" . $botname . " Nombre Apellido";
	exit;
}

if ($debug >= 1) echo "<pre>\n";
if ($debug >= 1) echo "Entrada: ".$entrada."\n";
if ($debug >= 1) echo "Quitando el nombre del bot: ".$tweet."\n";

// Quitando smileys tipo :D, XD, etc.
$tweet = mb_eregi_replace('[:;x=]d+', ' ', $tweet);
if ($debug >= 1) echo "Quitando smileys: ".$tweet."\n";

// Quitando signos de puntuación, etc.
$separadores = '[ \t\.,;:\/\\\^_\*\-\+=\(\)\[\]¡!¿\?`´¨ˆ]+';
$tweet = mb_eregi_replace($separadores, ' ', $tweet);
$tweet = mb_eregi_replace('/ +/', ' ', $tweet);
if ($debug >= 1) echo "Quitando separadores: ".$tweet."\n";

// Convirtiendo caracteres con acentos y cosas raras
$nombre = trim(strtolower(quitar_acentos($tweet)));
if ($debug >= 1) echo "Pasando a minúsculas: ".$nombre."\n";

// Convirtiendo la cadena a array de caracteres
$letras = str_split($nombre);
if ($debug >= 1) echo "Separando las letras:\n";
if ($debug >= 1) print_r($letras);

// Pasamos letra por letra convirtiendo las sílabas a katakana
$namae="";
for($i=0; $i<sizeof($letras); $i++){
	$double = false;
	if (preg_match('/ /', $letras[$i])){
		$silaba = '・';
		$namae .= $silaba;

	} elseif (preg_match('/[h]/', $letras[$i])){
		if ($debug >= 2) echo "H: muda\n";

	} elseif (preg_match('/[aiueo]/', $letras[$i])){
		$silaba = $letras[$i];
		if ($debug >= 2) echo "Vocal suelta: ".$silaba."\n";
		$namae .= katakana($silaba);

	} elseif (preg_match('/[bv]/', $letras[$i])){
		if ( preg_match('/[aiueoyw]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueoyw]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'b'.$letras[$i+1];
			if ($debug >= 2) echo "B + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[bv]/', $letras[$i+1])){
			if ($debug >= 2) echo "B geminada: no\n";
		} else {
			$silaba = 'bu';
			if ($debug >= 2) echo "B suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[c]/', $letras[$i])){
		if (preg_match('/[auwo]/', $letras[$i+1])){
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'k'.$letras[$i+1];
			if ($debug >= 2) echo "C + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[iye]/', $letras[$i+1])){
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			$silaba = 's'.$letras[$i+1];
			if ($debug >= 2) echo "C + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[c]/', $letras[$i+1])
				&& preg_match('/[eiy]/', $letras[$i+2])){
			$silaba = 'ku';
			if ($debug >= 2) echo "C geminada + E I: ".$silaba."\n";
			$namae .= katakana($silaba);
		} elseif (preg_match('/[ck]/', $letras[$i+1])){
			$silaba = 'xtu';
			if ($debug >= 2) echo "C geminada + A O U: ".$silaba."\n";
			$namae .= katakana($silaba);
		}else if ( preg_match('/[c]/', $letras[$i])
				&& preg_match('/[h]/', $letras[$i+1])
				&& preg_match('/[aiywueo]/', $letras[$i+2]) ){
			if (preg_match('/[y]/', $letras[$i+2])){
				$letras[$i+2] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+2])){
				$letras[$i+2] = 'u';
			}
			$silaba = 'ch'.$letras[$i+2];
			if ($debug >= 2) echo "CH + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$double = true;
			$i++; if($double) $i++;
		}else if ( preg_match('/[c]/', $letras[$i])
				&& preg_match('/[h]/', $letras[$i+1])
				&& preg_match('/[a-z]/', $letras[$i+2]) ){
			$silaba = 'chi';
			if ($debug >= 2) echo "CH suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		}else if ( preg_match('/[c]/', $letras[$i])
				&& preg_match('/[h]/', $letras[$i+1])
				&& !preg_match('/[aeiyouw]/', $letras[$i-1])){
			$silaba = 'chi';
			if ($debug >= 2) echo "CH final post-vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
		}else if ( preg_match('/[c]/', $letras[$i])
				&& preg_match('/[h]/', $letras[$i+1]) ){
			$silaba = 'xtuchi';
			if ($debug >= 2) echo "CH final: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiyouw]/', $letras[$i-1])){
			$silaba = 'ku';
			if ($debug >= 2) echo "C suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtuku';
			if ($debug >= 2) echo "C final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[d]/', $letras[$i])){
		if (preg_match('/[aeo]/', $letras[$i+1])
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aeo]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "D + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[iy]/', $letras[$i+1])
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[iy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			$silaba = 'dexi';
			if ($debug >= 2) echo "D + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[d]/', $letras[$i+1])){
			if ($debug >= 2) echo "D geminada: no\n";
		} elseif (preg_match('/[uw]/', $letras[$i+1])
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[uw]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'doxu';
			if ($debug >= 2) echo "D + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiyouw]/', $letras[$i-1])){
			$silaba = 'do';
			if ($debug >= 2) echo "D suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtudo';
			if ($debug >= 2) echo "D final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[f]/', $letras[$i])){
		if (preg_match('/[aiyeo]/', $letras[$i+1])
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiyeo]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			$silaba = 'fux'.$letras[$i+1];
			if ($debug >= 2) echo "F + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[uw]/', $letras[$i+1])
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[uw]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "F + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[f]/', $letras[$i+1])){
			if ($debug >= 2) echo "F geminada: no\n";
		} else {
			$silaba = 'fu';
			if ($debug >= 2) echo "F suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[g]/', $letras[$i])){
		if ( preg_match('/[g]/', $letras[$i])
				&& preg_match('/[u]/', $letras[$i+1])
				&& preg_match('/[ie]/', $letras[$i+2]) ){
			$silaba = 'g'.$letras[$i+2];
			if ($debug >= 2) echo "GU + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$double = true;
			$i++; if($double) $i++;
		} elseif (preg_match('/[iye]/', $letras[$i+1])){
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			$silaba = 'h'.$letras[$i+1];
			if ($debug >= 2) echo "G + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[aiyuweo]/', $letras[$i+1])
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiyuweo]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'g'.$letras[$i+1];
			if ($debug >= 2) echo "G + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[g]/', $letras[$i+1])){
			if ($debug >= 2) echo "G geminada: no\n";
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiouyw]/', $letras[$i-1])){
			$silaba = 'gu';
			if ($debug >= 2) echo "G suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtugu';
			if ($debug >= 2) echo "G final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[j]/', $letras[$i])){
		if ( preg_match('/[aiueoyw]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'h'.$letras[$i+1];
			if ($debug >= 2) echo "J + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[j]/', $letras[$i+1])){
			if ($debug >= 2) echo "J geminada: no\n";
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiouwy]/', $letras[$i-1])){
			$silaba = 'hu';
			if ($debug >= 2) echo "J suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtuho';
			if ($debug >= 2) echo "J final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[k]/', $letras[$i])){
		if ( preg_match('/[aiueoyw]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "K + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[k]/', $letras[$i+1])){
			$silaba = 'xtu';
			if ($debug >= 2) echo "K geminada: ".$silaba."\n";
			$namae .= katakana($silaba);
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiouwy]/', $letras[$i-1])){
			$silaba = 'ku';
			if ($debug >= 2) echo "K suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtuku';
			if ($debug >= 2) echo "K final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[lr]/', $letras[$i])){
		if ( preg_match('/[aiueowy]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'r'.$letras[$i+1];
			if ($debug >= 2) echo "L/R + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif ( preg_match('/[l]/', $letras[$i])
				&& preg_match('/[l]/', $letras[$i+1])
				&& preg_match('/[auwo]/', $letras[$i+2]) ){
			if (preg_match('/[w]/', $letras[$i+2])){
				$letras[$i+2] = 'u';
			}
			$silaba = 'y'.$letras[$i+2];
			if ($debug >= 2) echo "LL + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$double = true;
			$i++; if($double) $i++;
		} elseif ( preg_match('/[l]/', $letras[$i])
				&& preg_match('/[l]/', $letras[$i+1])
				&& preg_match('/[iye]/', $letras[$i+2]) ){
			if (preg_match('/[iy]/', $letras[$i+2]))
				$silaba = 'zi';
			if (preg_match('/[e]/', $letras[$i+2]))
				$silaba = 'zixe';
			if ($debug >= 2) echo "LL + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$double = true;
			$i++; if($double) $i++;
		} elseif (preg_match('/[r]/', $letras[$i])
				&& preg_match('/[r]/', $letras[$i+1])){
			if ($debug >= 2) echo "R geminada: no\n";
		} elseif (preg_match('/[l]/', $letras[$i])
				&& preg_match('/[l]/', $letras[$i+1])){
			if ($debug >= 2) echo "L geminada: no\n";
		} else {
			$silaba = 'ru';
			if ($debug >= 2) echo "L/R suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[m]/', $letras[$i])){
		if ( preg_match('/[aiueowy]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "M + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[m]/', $letras[$i+1])){
			$silaba = 'n';
			if ($debug >= 2) echo "M geminada: ".$silaba."\n";
			$namae .= katakana($silaba);
		} elseif (preg_match('/[bp]/', $letras[$i+1])){
			$silaba = 'n';
			if ($debug >= 2) echo "M antes de B o P: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'mu';
			if ($debug >= 2) echo "M suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[n]/', $letras[$i])){
		if ( preg_match('/[n]/', $letras[$i])
				&& preg_match('/[y]/', $letras[$i+1])
				&& preg_match('/[aiwueo]/', $letras[$i+2]) ){
			if (preg_match('/[i]/', $letras[$i+2]))
				$silaba = 'ni';
			elseif (preg_match('/[e]/', $letras[$i+2]))
				$silaba = 'nix'.$letras[$i+2];
			elseif (preg_match('/[aouw]/', $letras[$i+2]))
				if (preg_match('/[w]/', $letras[$i+1])){
					$letras[$i+1] = 'u';
				}
				$silaba = 'nixy'.$letras[$i+2];
			if ($debug >= 2) echo "NY + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$double = true;
			$i++; if($double) $i++;
		} elseif (preg_match('/[aiueowy]/', $letras[$i+1])
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "N + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} else {
			$silaba = $letras[$i];
			if ($debug >= 2) echo "N suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[p]/', $letras[$i])){
		if ( preg_match('/[aiueowy]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueoyw]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "P + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[p]/', $letras[$i+1])){
			if ($debug >= 2) echo "P geminada: no\n";
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiouwy]/', $letras[$i-1])){
			$silaba = 'pu';
			if ($debug >= 2) echo "P suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtupu';
			if ($debug >= 2) echo "P final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[q]/', $letras[$i])){
		if ( preg_match('/[q]/', $letras[$i])
				&& preg_match('/[u]/', $letras[$i+1])
				&& preg_match('/[ie]/', $letras[$i+2]) ){
			$silaba = 'k'.$letras[$i+2];
			if ($debug >= 2) echo "QU + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$double = true;
			$i++; if($double) $i++;
		} elseif ( preg_match('/[aiueowy]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'k'.$letras[$i+1];
			if ($debug >= 2) echo "Q + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[q]/', $letras[$i+1])){
			$silaba = 'xtu';
			if ($debug >= 2) echo "K geminada: ".$silaba."\n";
			$namae .= katakana($silaba);
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiouwy]/', $letras[$i-1])){
			$silaba = 'ku';
			if ($debug >= 2) echo "K suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtuku';
			if ($debug >= 2) echo "K final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[r]/', $letras[$i])){
		if ( preg_match('/[aiueowy]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "R + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[r]/', $letras[$i+1])){
			if ($debug >= 2) echo "R geminada: no\n";
		} else {
			$silaba = 'ru';
			if ($debug >= 2) echo "R suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[sz]/', $letras[$i])){
		if ( preg_match('/[aiueowy]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
				$sh = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			if($sh && preg_match('/[s]/', $letras[$i])) {
				$silaba = 'sh'.$letras[$i+1];
			} else {
				$silaba = 's'.$letras[$i+1];
			}
			if ($debug >= 2) echo "S + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[sz]/', $letras[$i+1])){
			if ($debug >= 2) echo "S geminada: no\n";
		} elseif(preg_match('/[h]/', $letras[$i+1])) {
			$silaba = 'xtushu';
			if ($debug >= 2) echo "SH suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'su';
			if ($debug >= 2) echo "S suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[t]/', $letras[$i])){
		if ( preg_match('/[aeo]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aeo]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "T + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif ( preg_match('/[iy]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[iy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			$silaba = 'texi';
			if ($debug >= 2) echo "T + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif ( preg_match('/[uw]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[uw]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'toxu';
			if ($debug >= 2) echo "T + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif ( preg_match('/[t]/', $letras[$i])
				&& preg_match('/[s]/', $letras[$i+1])
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if (preg_match('/[uw]/', $letras[$i+2])) {
				if (preg_match('/[w]/', $letras[$i+2])){
					$letras[$i+2] = 'u';
				}
				$silaba = 'tu';
			}
			elseif (preg_match('/[aiyeo]/', $letras[$i+2])){
				if (preg_match('/[y]/', $letras[$i+2])){
					$letras[$i+2] = 'i';
				}
				$silaba = 'tux'.$letras[$i+2];
			}
			if ($debug >= 2) echo "TS + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$double = true;
			$i++; if($double) $i++;
		} elseif (preg_match('/[t]/', $letras[$i+1])){
			if ($debug >= 2) echo "T geminada: no\n";
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiouwy]/', $letras[$i-1])){
			$silaba = 'to';
			if ($debug >= 2) echo "T suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtuto';
			if ($debug >= 2) echo "T final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[w]/', $letras[$i])){
		if ( preg_match('/[u]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[u]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			$silaba = 'u-';
			if ($debug >= 2) echo "W + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		}elseif ( preg_match('/[aiyeo]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiyeo]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			$silaba = 'ux'.$letras[$i+1];
			if ($debug >= 2) echo "W + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[w]/', $letras[$i+1])){
			if ($debug >= 2) echo "W geminada: no\n";
		} else {
			$silaba = 'u';
			if ($debug >= 2) echo "W suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[x]/', $letras[$i])){
		if ( preg_match('/[aiueoyw]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[aiueowy]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[y]/', $letras[$i+1])){
				$letras[$i+1] = 'i';
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = 'kus'.$letras[$i+1];
			if ($debug >= 2) echo "X + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[x]/', $letras[$i+1])){
			$silaba = 'xtu';
			if ($debug >= 2) echo "X geminada: ".$silaba."\n";
			$namae .= katakana($silaba);
		} elseif (preg_match('/[a-z]/', $letras[$i+1])
				|| !preg_match('/[aeiouyw]/', $letras[$i-1])){
			$silaba = 'kusu';
			if ($debug >= 2) echo "X suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		} else {
			$silaba = 'xtukusu';
			if ($debug >= 2) echo "X final: ".$silaba."\n";
			$namae .= katakana($silaba);
		}

	} elseif (preg_match('/[y]/', $letras[$i])){
		if ( preg_match('/[auwo]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[auwo]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			if (preg_match('/[w]/', $letras[$i+1])){
				$letras[$i+1] = 'u';
			}
			$silaba = $letras[$i].$letras[$i+1];
			if ($debug >= 2) echo "Y + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif ( preg_match('/[e]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[e]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			$silaba = 'zixe';
			if ($debug >= 2) echo "Y + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif ( preg_match('/[i]/', $letras[$i+1]) 
				|| preg_match('/[h]/', $letras[$i+1]) 
				&& preg_match('/[i]/', $letras[$i+2]) ){
			if(preg_match('/[h]/', $letras[$i+1])) {
				$letras[$i+1] = $letras[$i+2];
				$double = true;
			}
			$silaba = 'zi';
			if ($debug >= 2) echo "Y + vocal: ".$silaba."\n";
			$namae .= katakana($silaba);
			$i++; if($double) $i++;
		} elseif (preg_match('/[y]/', $letras[$i+1])){
			if ($debug >= 2) echo "Y geminada: no\n";
		} else {
			$silaba = 'i';
			if ($debug >= 2) echo "Y suelta: ".$silaba."\n";
			$namae .= katakana($silaba);
		}
	}
}

function katakana($string) {
	$antes = array (
		'xtu', 'xya', 'xyu', 'xyo', 'fu', '-',
		'xa', 'xi', 'xu', 'xe', 'xo',
		'ka', 'ki', 'ku', 'ke', 'ko',
		'kya', 'kyu', 'kye', 'kyo',
		'ga', 'gi', 'gu', 'ge', 'go',
		'gya', 'gyu', 'gye', 'gyo',
		'sa', 'si', 'su', 'se', 'so',
		'sha', 'shi', 'shu', 'she', 'sho',
		'za', 'zi', 'zu', 'ze', 'zo',
		'ta', 'ti', 'tu', 'te', 'to',
		'da', 'di', 'du', 'de', 'do',
		'cha', 'chi', 'chu', 'che', 'cho',
		'na', 'ni', 'nu', 'ne', 'no',
		'nya', 'nyi', 'nyu', 'nye', 'nyo',
		'ha', 'hi', 'hu', 'he', 'ho',
		'ba', 'bi', 'bu', 'be', 'bo',
		'pa', 'pi', 'pu', 'pe', 'po',
		'ma', 'mi', 'mu', 'me', 'mo',
		'ya', 'yu', 'yo', 'wa', 'wo', 
		'ra', 'ri', 'ru', 're', 'ro',
		'a', 'i', 'u', 'e', 'o', 'n');
	$despues = array (
		'ッ', 'ャ', 'ュ', 'ョ', 'フ', 'ー',
		'ァ', 'ィ', 'ゥ', 'ェ', 'ォ',
		'カ', 'キ', 'ク', 'ケ', 'コ',
		'キャ', 'キュ', 'キェ', 'キョ',
		'ガ', 'ギ', 'グ', 'ゲ', 'ゴ',
		'ギャ', 'ギュ', 'ギェ', 'ギョ',
		'サ', 'シ', 'ス', 'セ', 'ソ',
		'シャ', 'シ', 'シュ', 'シェ', 'ショ',
		'ザ', 'ジ', 'ズ', 'ゼ', 'ゾ',
		'タ', 'チ', 'ツ', 'テ', 'ト',
		'ダ', 'ヂ', 'ヅ', 'デ', 'ド',
		'チャ', 'チ', 'チュ', 'チェ', 'チョ',
		'ナ', 'ニ', 'ヌ', 'ネ', 'ノ',
		'ニャ', 'ニ', 'ニュ', 'ニェ', 'ニョ',
		'ハ', 'ヒ', 'フ', 'ヘ', 'ホ',
		'バ', 'ビ', 'ブ', 'ベ', 'ボ',
		'パ', 'ピ', 'プ', 'ペ', 'ポ',
		'マ', 'ミ', 'ム', 'メ', 'モ',
		'ヤ', 'ユ', 'ヨ', 'ワ', 'ヲ',
		'ラ', 'リ', 'ル', 'レ', 'ロ',
		'ア', 'イ', 'ウ', 'エ', 'オ', 'ン');
	return str_replace($antes, $despues, $string);
}
function quitar_acentos($string) {
	$antes = array('qüe','güe', 'qüi','güi', 'qüé','güé', 'qüí','güí', 'À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ÿ','Ā','ā','Ă','ă','Ą','ą','Ć','ć','Ĉ','ĉ','Ċ','ċ','Č','č','Ď','ď','Đ','đ','Ē','ē','Ĕ','ĕ','Ė','ė','Ę','ę','Ě','ě','Ĝ','ĝ','Ğ','ğ','Ġ','ġ','Ģ','ģ','Ĥ','ĥ','Ħ','ħ','Ĩ','ĩ','Ī','ī','Ĭ','ĭ','Į','į','İ','ı','Ĳ','ĳ','Ĵ','ĵ','Ķ','ķ','Ĺ','ĺ','Ļ','ļ','Ľ','ľ','Ŀ','ŀ','Ł','ł','Ń','ń','Ņ','ņ','Ň','ň','ŉ','Ō','ō','Ŏ','ŏ','Ő','ő','Œ','œ','Ŕ','ŕ','Ŗ','ŗ','Ř','ř','Ś','ś','Ŝ','ŝ','Ş','ş','Š','š','Ţ','ţ','Ť','ť','Ŧ','ŧ','Ũ','ũ','Ū','ū','Ŭ','ŭ','Ů','ů','Ű','ű','Ų','ų','Ŵ','ŵ','Ŷ','ŷ','Ÿ','Ź','ź','Ż','ż','Ž','ž','ſ','ƒ','Ơ','ơ','Ư','ư','Ǎ','ǎ','Ǐ','ǐ','Ǒ','ǒ','Ǔ','ǔ','Ǖ','ǖ','Ǘ','ǘ','Ǚ','ǚ','Ǜ','ǜ','Ǻ','ǻ','Ǽ','ǽ','Ǿ','ǿ');
	$despues = array('kue','ghue', 'kui','ghui', 'kue','ghue', 'kui','ghui', 'A','A','A','A','A','A','AE','C','E','E','E','E','I','I','I','I','D','NY','O','O','O','O','O','O','U','U','U','u','Y','s','a','a','a','a','a','a','ae','c','e','e','e','e','i','i','i','i','ny','o','o','o','o','o','o','u','u','u','u','y','y','A','a','A','a','A','a','C','c','C','c','C','c','C','c','D','d','D','d','E','e','E','e','E','e','E','e','E','e','G','g','G','g','G','g','G','g','H','h','H','h','I','i','I','i','I','i','I','i','I','i','IJ','ij','J','j','K','k','L','l','L','l','L','l','L','l','l','l','N','n','N','n','N','n','n','O','o','O','o','O','o','OE','oe','R','r','R','r','R','r','S','s','S','s','S','s','S','s','T','t','T','t','T','t','U','u','U','u','U','u','U','u','U','u','U','u','W','w','Y','y','Y','Z','z','Z','z','Z','z','s','f','O','o','U','u','A','a','I','i','O','o','U','u','U','u','U','u','U','u','U','u','A','a','AE','ae','O','o');
	return str_replace($antes, $despues, $string);
}

// Arreglando posibles caracteres repetidos
$namae = mb_eregi_replace('ッッ', 'ッ', $namae);
$namae = mb_eregi_replace('・・', '・', $namae); 
$namae = mb_eregi_replace('^・', '', $namae); 
$namae = mb_eregi_replace('・$', '', $namae); 

$screen_name = $_POST['screen_name'];
$recortar =  mb_strlen($screen_name) + 1;

// Construyendo el tweet de respuesta
$output = $tweet." se escribe: " .$namae;
// Si el mensaje original contiene "gracias", metemos "de nada" al final
if (preg_match('/g+r+a+c+i+a+s+/', $nombre)){
	$output .= ' ¡De nada! :)';
}
// Si entra en 140 caracteres, se envía
if(mb_strlen($output) <= 140 - $recortar) {
	echo $output;
	exit;
}
// Si no, contestamos de forma simplificada
$output = "Se escribe: " .$namae;
if(mb_strlen($output) <= 140 - $recortar) {
	echo $output;
	exit;
}
// Si no, contestamos de forma aún más simplificada
$output = $namae;
if(mb_strlen($output) <= 140 - $recortar) {
	echo $output;
	exit;
}
// Si ni por esas, recortamos la respuesta
$output = mb_substr($namae, 0, 136 - $recortar) . " (…)";
echo $output;