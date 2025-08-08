<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    public function generate()
    {
        // Get captcha config
        $config = config('filament-captcha', [
            'width' => 180,
            'height' => 50,
            'background_color' => [255, 255, 255],
        ]);

        // Create image
        $image = imagecreate($config['width'], $config['height']);
        
        // Set background color
        $backgroundColor = imagecolorallocate(
            $image, 
            $config['background_color'][0], 
            $config['background_color'][1], 
            $config['background_color'][2]
        );
        
        // Generate random text
        $captchaText = $this->generateRandomText();
        
        // Store in session
        session(['captcha_text' => $captchaText]);
        
        // Add text to image
        $textColor = imagecolorallocate($image, 0, 0, 0); // Black text
        
        // Add some noise lines
        for ($i = 0; $i < 5; $i++) {
            $lineColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imageline(
                $image, 
                rand(0, $config['width']), 
                rand(0, $config['height']), 
                rand(0, $config['width']), 
                rand(0, $config['height']), 
                $lineColor
            );
        }
        
        // Add text
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($captchaText);
        $textHeight = imagefontheight($fontSize);
        
        $x = ($config['width'] - $textWidth) / 2;
        $y = ($config['height'] - $textHeight) / 2;
        
        imagestring($image, $fontSize, $x, $y, $captchaText, $textColor);
        
        // Add some noise dots
        for ($i = 0; $i < 50; $i++) {
            $dotColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
            imagesetpixel($image, rand(0, $config['width']), rand(0, $config['height']), $dotColor);
        }
        
        // Output image
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        
        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    
    private function generateRandomText(): string
    {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Exclude similar characters
        $length = 5;
        $text = '';
        
        for ($i = 0; $i < $length; $i++) {
            $text .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $text;
    }
    
    public function refresh()
    {
        return $this->generate();
    }
}