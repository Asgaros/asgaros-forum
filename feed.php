<?php
global $wpdb, $mingleforum;

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once($root . '/wp-load.php');

if ($mingleforum->options['forum_use_rss'])
{
  $mingleforum->setup_links();

  if (is_numeric($_GET['topic'])) //is_numeric will prevent SQL injections
    $topic = $_GET['topic'];
  else
    $topic = 'all';

  if ($topic == "all")
  {
    $posts = $wpdb->get_results("SELECT * FROM {$mingleforum->t_posts} ORDER BY `date` DESC LIMIT 20");
    $title = get_bloginfo('name') . " " . __("Forum Feed", "mingleforum") . "";
    $description = __("Forum Feed", "mingleforum");
  }
  else
  {
    $posts = $wpdb->get_results("SELECT * FROM $mingleforum->t_posts WHERE parent_id = $topic ORDER BY `date` DESC LIMIT 20");
    $description = __("Forum Topic:", "mingleforum") . " - " . $mingleforum->get_subject($topic);
    $title = get_bloginfo('name') . " " . __("Forum", "mingleforum") . " - " . __("Topic: ", "mingleforum") . " " . $mingleforum->get_subject($topic);
  }
  $link = $mingleforum->home_url;

  header("Content-type: application/rss+xml");

  echo ("<?xml version=\"1.0\" encoding=\"" . get_bloginfo('charset') . "\"?>\n");
  ?>
  <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
      <title><?php echo $title; ?></title>
      <description><?php
        bloginfo('name');
        echo " $description";
        ?></description>
      <link><?php echo $link; ?></link>
      <language><?php bloginfo('language'); ?></language>
      <?php
      foreach ($posts as $post)
      {
        $catid = $mingleforum->forum_get_group_from_post($post->parent_id);
        $groups = maybe_unserialize($wpdb->get_var("select usergroups from {$mingleforum->t_groups} where id = {$catid}"));
        if (empty($groups)) //don't show protected group posts in the feed
        {
          $link = $mingleforum->get_threadlink($post->parent_id);
          $title = $post->subject;

          echo "<item>\n
			<title>" . htmlspecialchars($title) . "</title>\n
			<description>" . htmlspecialchars($mingleforum->output_filter($post->text, ENT_NOQUOTES)) . "</description>\n
			<link>" . htmlspecialchars($link) . "</link>\n
			<author>feeds@r.us</author>\n
			<pubDate>" . date("r", strtotime($post->date)) . "</pubDate>\n
			<guid>" . htmlspecialchars($link . "&guid=$post->id") . "</guid>
			</item>\n\n";
        }
      }
      echo "</channel>
	</rss>";
}
else
  echo "<html><body>" . __("Feeds are disabled", "mingleforum") . "</body></html>";
?>