<?php

$data = file_get_contents('default.bin');

function getArrayTheme($data)
{
    $format = "a4head/Iversion";
    $r = unpack($format, $data);

    $format = "Iparams/Ibuttons/Ibitmaps";
    $pos = unpack($format, $data, 8);

    if ($r['head'] != 'SKIN') {
        return false;
    }

    $format = "sright/sleft/sbottom/stop";
    $r['margin'] = unpack($format, $data, $pos['params'] + 4);

    $format = "H6inner/xx/H6outer/xx/H6frame";
    $r['active'] = unpack($format, $data, $pos['params'] + 12);

    $r['inactive'] = unpack($format, $data, $pos['params'] + 24);

    $format = "Isize/H6taskbar/xx/H6taskbar_text/xx/H6work_dark/xx/H6work_light/xx/H6window_title/xx/H6work/xx/H6work_button/xx/H6work_button_text/xx/H6work_text/xx/H6work_graph";
    $r['dtp'] = unpack($format, $data, $pos['params'] + 36);

    $buttonStartPosition = $pos['buttons'];
    $button = [];
    while (unpack('Lchk', $data, $buttonStartPosition)['chk']) {
        $format = "Ltype/sleft/stop/swidth/sheight";
        $button[] = unpack($format, $data, $buttonStartPosition);
        $buttonStartPosition += 12;
    }
    $r['button'] = $button;

    $bitmapStartPosition = $pos['bitmaps'];
    $bitmap = [];
    while (unpack('Lchk', $data, $bitmapStartPosition)['chk']) {
        $format = "Skind/Stype";
        $bit = unpack($format, $data, $bitmapStartPosition);

        $format = "Lposition";
        $posbm = unpack($format, $data, $bitmapStartPosition + 4);

        $format = "Lwidth/Lheight";
        $bit += unpack($format, $data, $posbm['position']);

        $size = $bit['width'] * $bit['height'] * 3;
        $format = "C{$size}";
        $bits = unpack($format, $data, $posbm['position'] + 8);

        $w = $bit['width'];
        $h = $bit['height'];
        $img = imagecreatetruecolor($w, $h);

        // Итерация по пикселям
        $i = 1;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $bb = $bits[$i++];
                $gg = $bits[$i++];
                $rr = $bits[$i++];
                imagesetpixel($img, $x, $y, ($rr << 16 | $gg << 8 | $bb));
            }
        }
        ob_start();
        imagepng($img);
        $bit['base64'] = base64_encode(ob_get_contents());
        ob_end_clean();
        imagedestroy($img);
        $bitmap[] = $bit;
        $bitmapStartPosition += 8;
    }
    $r['bitmap'] = $bitmap;

    return $r;
}

print_r(getArrayTheme($data));
