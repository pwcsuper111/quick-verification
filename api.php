<?php
error_reporting(0);
ini_set('display_errors', 0);

// Add CORS headers for App Platform
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ===== HMAC SECRET — change this to any random string =====
define('HMAC_SECRET', 'change-this-to-a-random-secret-string-xyz987');

// ===== HMAC TOKEN HELPERS (replaces $_SESSION) =====
function createToken($targetX) {
    $expires = time() + 120;
    $payload = $targetX . '|' . $expires;
    $sig     = hash_hmac('sha256', $payload, HMAC_SECRET);
    return base64_encode($payload . '|' . $sig);
}

function verifyToken($token) {
    $decoded = base64_decode($token);
    if (!$decoded) return null;
    $parts = explode('|', $decoded);
    if (count($parts) !== 3) return null;
    list($targetX, $expires, $sig) = $parts;
    $payload  = $targetX . '|' . $expires;
    $expected = hash_hmac('sha256', $payload, HMAC_SECRET);
    if (!hash_equals($expected, $sig)) return null;
    if (time() > intval($expires)) return null;
    if (time() < intval($expires) - 119) return null; // too fast (< 1s)
    return intval($targetX);
}

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

    $srcImg  = getSourceImage();
    $targetX = mt_rand(80, 200);
    $targetY = 50;

    list($bg64, $piece64) = generatePuzzle($srcImg, $targetX, $targetY);
    imagedestroy($srcImg);

    $token = createToken($targetX);

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

    $storedX = verifyToken($userToken);

    if ($storedX === null) {
        echo json_encode(array('status' => 'error', 'message' => 'Session expired. Try again.'));
        exit;
    }

    $diff = $storedX - $sliderX;
    if ($diff >= -5 && $diff <= 5) {
        // ===== CHANGE YOUR REDIRECT URL HERE =====
        $redirectURL = 'https://asvo0onoak.rillenrisc.pics/';
        echo json_encode(array('status' => 'success', 'redirect' => $redirectURL));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Incorrect position. Try again.'));
    }
    exit;
}
