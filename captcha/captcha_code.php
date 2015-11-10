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

class CaptchaCode
{

  function generateCode($characters)
  {
    /* list all possible characters, similar looking characters and vowels have been removed */
    $possible = '23456789bcdfghjkmnpqrstvwxyz';
    $code = '';
    $i = 0;
    while ($i < $characters)
    {
      $code .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
      $i++;
    }
    return $code;
  }

}

// A general PHP rule, for library used before header must make sure no space at the end
?>