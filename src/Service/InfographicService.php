<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;

class InfographicService
{
    public function generateBase64(User $user): string
    {
        $width = 800;
        $height = 400;
        $img = imagecreatetruecolor($width, $height);
        
        $bg = imagecolorallocate($img, 30, 41, 59); // slate-900
        imagefill($img, 0, 0, $bg);
        
        $white = imagecolorallocate($img, 255, 255, 255);
        imagestring($img, 5, 50, 150, "Apprentissage de " . $user->getFullname(), $white);
        imagestring($img, 5, 50, 200, "XP Gagnés : " . $user->getXp(), $white);
        
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);
        
        return base64_encode($data);
    }
}
