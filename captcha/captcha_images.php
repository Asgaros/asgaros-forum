<?php

/*
 * Original Author: Simon Jarvis
 * Copyright: 2006 Simon Jarvis
 * Date: 03/08/06
 * Updated: 07/02/07
 * Requirements: PHP 4/5 with GD and FreeType libraries
 * Link: http://www.white-hat-web-design.co.uk/articles/php-captcha.php
 *
 *
 * Modified by: Mythos and Rini
 * Date: 2007-09-10
 * Description: Modified the code and remove use of Session
 *
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * http://www.gnu.org/licenses/gpl.html
 *
 *
 */

include("shared.php");

class CaptchaSecurityImages
{

  var $font = 'monofont.ttf';

  function GenerateImage($width = '120', $height = '40', $code = '')
  {

    /* font size will be 75% of the image height */
    $font_size = $height * 0.75;
    $image = @imagecreate($width, $height) or die('Cannot initialize new GD image stream');
    /* set the colours */
    imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 20, 40, 100);
    $noise_color = imagecolorallocate($image, 100, 120, 180);
    /* generate random dots in background */
    for ($i = 0; $i < ($width * $height) / 3; $i++)
    {
      imagefilledellipse($image, mt_rand(0, $width), mt_rand(0, $height), 1, 1, $noise_color);
    }
    /* generate random lines in background */
    for ($i = 0; $i < ($width * $height) / 150; $i++)
    {
      imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $noise_color);
    }
    /* create textbox and add text */
    $textbox = imagettfbbox($font_size, 0, $this->font, $code) or die('Error in imagettfbbox function');
    $x = ($width - $textbox[4]) / 2;
    $y = ($height - $textbox[5]) / 2;
    imagettftext($image, $font_size, 0, $x, $y, $text_color, $this->font, $code) or die('Error in imagettftext function');
    /* output captcha image to browser */
    header('Content-Type: image/jpeg');
    imagejpeg($image);
    imagedestroy($image);
  }

}

$width = isset($_GET['width']) ? $_GET['width'] : '120';
$height = isset($_GET['height']) ? $_GET['height'] : '40';
$code = isset($_GET['code']) ? $_GET['code'] : '';

if (isset($_GET['code']))
{
  $captcha = new CaptchaSecurityImages();
  $captcha->GenerateImage($width, $height, wpf_str_decrypt($code));
}
?>