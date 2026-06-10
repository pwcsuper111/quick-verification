<?php
error_reporting(0);
ini_set('display_errors', 0);
@session_start();

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ===== JIGSAW SHAPE MATH =====
function isJigsawPixel($px, $py) {
    $r = 7;
    $core = $px >= 10 && $px <= 40 && $py >= 10 && $py <= 40;
    $inCircle = function($cx, $cy) use ($px, $py, $r) {
        return ($px-$cx)*($px-$cx) + ($py-$cy)*($py-$cy) <= $r*$r;
    };
    $topNub    = $inCircle(25, 10);
    $rightNub  = $inCircle(40, 25);
    $leftHole  = $inCircle(10, 25);
    $bottomHole = $inCircle(25, 40);
    return ($core || $topNub || $rightNub) && !$leftHole && !$bottomHole;
}

function isBorderPixel($px, $py) {
    if (!isJigsawPixel($px, $py)) return false;
    return !isJigsawPixel($px-1,$py) || !isJigsawPixel($px+1,$py) ||
           !isJigsawPixel($px,$py-1) || !isJigsawPixel($px,$py+1);
}

// ===== GENERATE PUZZLE IMAGES =====
function generatePuzzle($srcImg, $targetX, $targetY) {
    $bgW = 300; $bgH = 150; $ps = 50;

    $bg = imagecreatetruecolor($bgW, $bgH);
    imagecopy($bg, $srcImg, 0, 0, 0, 0, $bgW, $bgH);

    $piece = imagecreatetruecolor($ps, $ps);
    imagesavealpha($piece, true);
    imagefill($piece, 0, 0, imagecolorallocatealpha($piece, 0, 0, 0, 127));

    for ($y = 0; $y < $ps; $y++) {
        for ($x = 0; $x < $ps; $x++) {
            $bx = $targetX + $x;
            $by = $targetY + $y;
            if ($bx >= $bgW || $by >= $bgH) continue;

            if (isBorderPixel($x, $y)) {
                imagesetpixel($piece, $x, $y, imagecolorallocate($piece, 255, 255, 255));
                $rgb = imagecolorat($bg, $bx, $by);
                $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
                imagesetpixel($bg, $bx, $by, imagecolorallocate($bg, (int)($r/4), (int)($g/4), (int)($b/4)));
            } else if (isJigsawPixel($x, $y)) {
                imagesetpixel($piece, $x, $y, imagecolorat($bg, $bx, $by));
                $rgb = imagecolorat($bg, $bx, $by);
                $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;
                imagesetpixel($bg, $bx, $by, imagecolorallocate($bg, (int)($r/3), (int)($g/3), (int)($b/3)));
            }
        }
    }

    ob_start(); imagepng($bg); $bgData = ob_get_clean(); imagedestroy($bg);
    ob_start(); imagepng($piece); $pieceData = ob_get_clean(); imagedestroy($piece);

    return array(
        'data:image/png;base64,' . base64_encode($bgData),
        'data:image/png;base64,' . base64_encode($pieceData)
    );
}

// ===== FETCH SOURCE IMAGE =====
function getSourceImage() {
    $ids = array("1015","1018","1019","1032","1040","1043","1069");
    $id = $ids[array_rand($ids)];
    $url = "https://picsum.photos/id/{$id}/300/150";

    if (function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code == 200 && $data) {
            $img = @imagecreatefromstring($data);
            if ($img !== false) return $img;
        }
    }

    // Fallback gradient
    $img = imagecreatetruecolor(300, 150);
    for ($y = 0; $y < 150; $y++) {
        for ($x = 0; $x < 300; $x++) {
            imagesetpixel($img, $x, $y, imagecolorallocate($img,
                (int)($x * 255 / 300),
                (int)($y * 255 / 150),
                200
            ));
        }
    }
    return $img;
}

// ==========================================
// CHALLENGE ENDPOINT
// ==========================================
if ($action === 'challenge') {
    header('Content-Type: application/json');

    if (!function_exists('imagecreatetruecolor')) {
        echo json_encode(array('error' => 'GD extension is not enabled on this server.'));
        exit;
    }

    $srcImg = getSourceImage();
    $targetX = mt_rand(80, 200);
    $targetY = 50;

    list($bg64, $piece64) = generatePuzzle($srcImg, $targetX, $targetY);
    imagedestroy($srcImg);

    $token = md5(mt_rand() . time() . mt_rand());
    $_SESSION['puzzle_targetX'] = $targetX;
    $_SESSION['puzzle_token']   = $token;
    $_SESSION['puzzle_time']    = time();

    echo json_encode(array(
        'bg_image'    => $bg64,
        'piece_image' => $piece64,
        'piece_y'     => $targetY,
        'token'       => $token
    ));
    exit;
}

// ==========================================
// VERIFY ENDPOINT
// ==========================================
if ($action === 'verify') {
    header('Content-Type: application/json');

    $input = file_get_contents('php://input');
    $data  = json_decode($input, true);

    $sliderX   = isset($data['slider_x']) ? intval($data['slider_x']) : -9999;
    $userToken = isset($data['token'])    ? $data['token']            : '';

    $storedX     = isset($_SESSION['puzzle_targetX']) ? $_SESSION['puzzle_targetX'] : null;
    $storedToken = isset($_SESSION['puzzle_token'])   ? $_SESSION['puzzle_token']   : '';
    $storedTime  = isset($_SESSION['puzzle_time'])    ? $_SESSION['puzzle_time']    : 0;

    // One-time use
    unset($_SESSION['puzzle_targetX'], $_SESSION['puzzle_token'], $_SESSION['puzzle_time']);

    if ($storedToken === '' || $userToken !== $storedToken) {
        echo json_encode(array('status' => 'error', 'message' => 'Session expired. Try again.'));
        exit;
    }
    if (time() - $storedTime < 1) {
        echo json_encode(array('status' => 'error', 'message' => 'Too fast. Try again.'));
        exit;
    }
    if (time() - $storedTime > 120) {
        echo json_encode(array('status' => 'error', 'message' => 'Challenge expired. Try again.'));
        exit;
    }

    $diff = $storedX - $sliderX;
    if ($diff >= -5 && $diff <= 5) {
        // ===== CHANGE YOUR REDIRECT URL HERE =====
        $redirectURL = 'https://youtube.com';
        echo json_encode(array('status' => 'success', 'redirect' => $redirectURL));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Incorrect position. Try again.'));
    }
    exit;
}
