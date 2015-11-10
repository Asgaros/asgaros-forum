<?php

/*
 * Class for parsing BBCode
 * @author - Paul Carter, http://cartpauj.com
 * Ui = 1 line
 * Uis = Multiple Lines
 */
if (!class_exists('cartpaujBBCodeParser'))
{

  class cartpaujBBCodeParser
  {

    var $patterns = array(
        '/\[list\](.+)\[\/list\]/Uis',
        '/\[\*\](.+)\\n/Ui',
        '/\[spoil\](.+)\[\/spoil\]/Uis',
        '/\[b\](.+)\[\/b\]/Uis',
        '/\[quotetitle\](.+)\[\/quotetitle\]/Uis',
        '/\[i\](.+)\[\/i\]/Uis',
        '/\[u\](.+)\[\/u\]/Uis',
        '/\[font size=(.+)\](.+)\[\/font\]/Uis',
        '/\[s\](.+)\[\/s\]/Uis',
        '/\[center\](.+)\[\/center\]/Uis',
        '/\[left\](.+)\[\/left\]/Uis',
        '/\[right\](.+)\[\/right\]/Uis',
        '/\[url=(.+)\](.+)\[\/url\]/Ui',
        '/\[url](.+)\[\/url\]/Ui',
        '/\[map](.+)\[\/map\]/Ui',
        '/\[yt](.+)\[\/yt\]/Ui',
        '/\[embed](.+)\[\/embed\]/Ui',
        '/\[email](.+)\[\/email\]/Ui',
        '/\[email=(.+)\](.+)\[\/email\]/Ui',
        '/\[img\](.+)\[\/img\]/Ui',
        '/\[img=(.+)\](.+)\[\/img\]/Ui',
        '/\[color=(\#[0-9a-f]{6}|[a-z]+)\](.+)\[\/color\]/Ui',
        '/\[color=(\#[0-9a-f]{6}|[a-z]+)\](.+)\[\/color\]/Uis'
    );
    var $replacements = array(
        '<ul>\1</ul>',
        '<li>\1</li>',
        '<span style="color:transparent;">\1</span>',
        '<b>\1</b>',
        '<div class="quotetitle">\1</div>',
        '<i>\1</i>',
        '<u>\1</u>',
        '<span style="font-size:\1px;">\2</span>',
        '<s>\1</s>',
        '<div style="text-align:center;">\1</div>',
        '<div style="text-align:left;">\1</div>',
        '<div style="text-align:right;">\1</div>',
        '<a href="\1" target="_blank">\2</a>',
        '<a href="\1" target="_blank">\1</a>',
        '<iframe width="400" height="325" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="\1&output=embed">Your browser does not support iFrames</iframe>',
        '\1',
        '\1',
        '<a href="mailto:\1">\1</a>',
        '<a href="mailto:\1">\2</a>',
        '<a href="\1"><img src="\1" alt="Image" /></a>',
        '<a href="\1"><img src="\1" alt="\2" /></a>',
        '<span style="color: \1;">\2</span>',
        '<div style="color: \1;">\2</div>'
    );

    function bbc2html($subject)
    {
      $codes = array(array(), array());
      preg_match_all('/\[code\](.+)\[\/code\]/Uis', $subject, $codes);

      foreach ($codes[0] as $num => $code)
        $subject = str_replace($code, "[code{$num}]", $subject);

      $subject = preg_replace($this->patterns, $this->replacements, $subject);

      $findQ = array("[quote]", "[/quote]", "[QUOTE]", "[/QUOTE]");
      $replaceQ = array("<blockquote>", "</blockquote>", "<blockquote>", "</blockquote>");
      $subject = str_replace($findQ, $replaceQ, $subject);

      $subject = convert_smilies($subject);

      foreach ($codes[1] as $num => $code)
        $subject = str_replace("[code{$num}]", '<pre class="code">' . $code . '</pre>', $subject);

      return $subject;
    }

  }

}
?>
