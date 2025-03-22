<?php

// Function to create and save an image
function createImage($width, $height, $filename, $type = 'default') {
    $image = imagecreatetruecolor($width, $height);
    
    // Define colors
    $pink = imagecolorallocate($image, 255, 107, 107);
    $turquoise = imagecolorallocate($image, 78, 205, 196);
    $yellow = imagecolorallocate($image, 255, 230, 109);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 45, 52, 54);
    
    // Fill background
    if ($type === 'hero') {
        // Create gradient background for hero
        for ($i = 0; $i < $height; $i++) {
            $ratio = $i / $height;
            $r = 255 - ($ratio * (255 - 78));
            $g = 107 + ($ratio * (205 - 107));
            $b = 107 + ($ratio * (196 - 107));
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $i, $width, $i, $color);
        }
        
        // Add some decorative circles
        for ($i = 0; $i < 20; $i++) {
            $x = rand(0, $width);
            $y = rand(0, $height);
            $size = rand(20, 100);
            imagefilledellipse($image, $x, $y, $size, $size, $yellow);
        }
    } else {
        // Default ice cream image
        imagefill($image, 0, 0, $white);
        
        // Draw ice cream cone
        $points = [
            $width/2, $height*0.8,  // Tip of cone
            $width*0.3, $height*0.5, // Left edge
            $width*0.7, $height*0.5  // Right edge
        ];
        imagefilledpolygon($image, $points, 3, $yellow);
        
        // Draw ice cream scoop
        imagefilledellipse($image, $width/2, $height*0.4, $width*0.5, $width*0.5, $pink);
        
        // Add highlight
        imagefilledellipse($image, $width*0.4, $height*0.35, $width*0.15, $width*0.15, $white);
    }
    
    // Add text
    $text = $type === 'hero' ? 'Sweet Scoops' : 'Ice Cream';
    $font_size = $type === 'hero' ? 5 : 3;
    $text_color = $type === 'hero' ? $white : $black;
    
    // Use built-in font instead of TTF
    $font = $font_size;
    if ($type === 'hero') {
        imagestring($image, $font, $width/2 - 100, $height/2, $text, $text_color);
    } else {
        imagestring($image, $font, $width/2 - 50, $height*0.9, $text, $text_color);
    }
    
    // Save image
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
}

// Create images directory if it doesn't exist
if (!file_exists('images')) {
    mkdir('images');
}
if (!file_exists('images/products')) {
    mkdir('images/products');
}

// Generate hero background
createImage(1920, 1080, 'images/hero-bg.jpg', 'hero');

// Generate default ice cream image
createImage(800, 800, 'images/default-ice-cream.jpg', 'default');

// Generate product images with different colors
$flavors = [
    'vanilla-bean' => [255, 250, 240],       // Cream color
    'chocolate-fudge' => [139, 69, 19],      // Brown
    'strawberry-delight' => [255, 182, 193], // Pink
    'mango-tango' => [255, 204, 0],         // Yellow
    'butterscotch-bliss' => [218, 160, 109], // Light brown
    'pistachio-dream' => [147, 197, 114],    // Light green
    'coffee-caramel' => [141, 85, 36],      // Dark brown
    'mint-chip' => [152, 255, 152],         // Mint green
    'cookies-cream' => [238, 238, 238],     // Light gray
    'blueberry-cheesecake' => [138, 43, 226] // Purple
];

foreach ($flavors as $flavor => $color) {
    $image = imagecreatetruecolor(800, 800);
    
    // Colors
    $bg_color = imagecolorallocate($image, 255, 255, 255);
    $scoop_color = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    $cone_color = imagecolorallocate($image, 255, 230, 109);
    $text_color = imagecolorallocate($image, 45, 52, 54);
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Draw cone
    $points = [
        400, 640,  // Tip
        240, 400,  // Left edge
        560, 400   // Right edge
    ];
    imagefilledpolygon($image, $points, 3, $cone_color);
    
    // Draw scoop
    imagefilledellipse($image, 400, 320, 400, 400, $scoop_color);
    
    // Add highlight
    imagefilledellipse($image, 320, 280, 120, 120, $bg_color);
    
    // Add flavor name
    $flavor_name = ucwords(str_replace('-', ' ', $flavor));
    imagestring($image, 5, 300, 700, $flavor_name, $text_color);
    
    // Save image
    imagejpeg($image, "images/products/{$flavor}.jpg", 90);
    imagedestroy($image);
}

echo "Images generated successfully!\n";
?> 