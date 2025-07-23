<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DefaultDoctorImageSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating default doctor image...');

        // Pastikan directory ada
        $publicPath = public_path('assets/img');
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0755, true);
        }

        // Buat default doctor image sederhana (SVG)
        $defaultDoctorSvg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
  <!-- Background Circle -->
  <circle cx="150" cy="150" r="150" fill="#f8f9fa"/>
  
  <!-- Doctor Background -->
  <circle cx="150" cy="150" r="120" fill="#e9ecef"/>
  
  <!-- Doctor Icon -->
  <g transform="translate(150, 150)">
    <!-- Head -->
    <circle cx="0" cy="-30" r="25" fill="#6c757d"/>
    
    <!-- Body -->
    <rect x="-20" y="-5" width="40" height="60" rx="20" fill="#6c757d"/>
    
    <!-- Stethoscope -->
    <path d="M-15,-10 Q-20,-5 -15,0 Q-10,5 0,0 Q10,5 15,0 Q20,-5 15,-10" 
          stroke="#dc3545" stroke-width="3" fill="none"/>
    <circle cx="-15" cy="-10" r="3" fill="#dc3545"/>
    <circle cx="15" cy="-10" r="3" fill="#dc3545"/>
    
    <!-- Medical Cross -->
    <rect x="-3" y="20" width="6" height="15" fill="#ffffff"/>
    <rect x="-7" y="25" width="14" height="6" fill="#ffffff"/>
  </g>
  
  <!-- Text -->
  <text x="150" y="260" text-anchor="middle" font-family="Arial, sans-serif" 
        font-size="14" fill="#6c757d">Default Doctor</text>
</svg>';

        // Simpan file
        $defaultImagePath = $publicPath . '/default-doctor.png';
        
        // Convert SVG to PNG jika diperlukan atau langsung simpan sebagai SVG
        file_put_contents(public_path('assets/img/default-doctor.svg'), $defaultDoctorSvg);
        
        // Untuk PNG, buat placeholder sederhana
        // Ini akan memerlukan GD extension, jadi kita buat file placeholder
        $placeholder = imagecreate(300, 300);
        $bg = imagecolorallocate($placeholder, 248, 249, 250);
        $fg = imagecolorallocate($placeholder, 108, 117, 125);
        
        imagefill($placeholder, 0, 0, $bg);
        
        // Gambar circle untuk head
        imagefilledellipse($placeholder, 150, 120, 50, 50, $fg);
        
        // Gambar body
        imagefilledrectangle($placeholder, 130, 145, 170, 205, $fg);
        
        // Simpan sebagai PNG
        if (function_exists('imagepng')) {
            imagepng($placeholder, $defaultImagePath);
            imagedestroy($placeholder);
            $this->command->info('✅ Default doctor image created: ' . $defaultImagePath);
        } else {
            $this->command->warn('⚠️ GD extension not available, using SVG only');
        }

        $this->command->info('✅ Default doctor images created successfully!');
    }
}