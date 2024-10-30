<?php
/*
Plugin Name: Blog Juice Widget / Plugin
Plugin URI: http://www.mutube.com/projects/wordpress/blog-juice/?utm_source=plugin&utm_medium=admin
Description: Displays your site's rating as calculated by <a href="http://www.text-link-ads.com/blog_juice/">Blog Juice Calculator</a>.
Author: Martin Fitzpatrick
Version: 0.5
Author URI: http://www.mutube.com?utm_source=plugin&utm_medium=admin
*/

@define("BLOGJUICE_VERSION", "0.5");

/*  Copyright 2006  MARTIN FITZPATRICK  (email : martin.fitzpatrick@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*

   STANDARD OUTPUT FUNCTIONS
   These are out of the main function block below so they can be called
   from outside "widget-space".  This means we can re-use code for widget
   and non-widget versions

*/

function blogjuice()
{
	global $blogjuice;
	$blogjuice->display();
}

class blogjuice
{

	/* Fetch remote url, using cURL if available or fallback to file_get_contents */
	function fetch_url($url)
	{

		/* Use cURL if it is available, otherwise attempt fopen */
		if(function_exists('curl_init'))
		{ 
			/*	
				Request data using cURL library
				With thanks to Marcin Juszkiewicz
				http://www.hrw.one.pl/
			*/
						
			$ch = curl_init();

			// set URL and other appropriate options
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			
			// grab URL and pass it to the browser
			$data = @curl_exec($ch);
		
			if ((curl_errno($ch)) || ($data===true)) {
				$data=false;
			}

 			// close curl resource, and free up system resources
 			curl_close($ch);

		} else { $data=@file_get_contents ( $url ); } /* If cURL is not installed use file_get_contents */

 		return $data;
	}

	function update($options) {

		//Use blank category vs. All as this is smaller. Reduce overhead.
		//Has no effect on rating/results.
		$data=$this->fetch_url('http://www.text-link-ads.com/blog_juice/blog_juice_logic.php?type=report&cat=&url=' . urlencode($_SERVER['SERVER_NAME'])  );
			
		//Roughly chop the data into rows (first row & last will contain extra XML junk
		$rows=explode("</tr>",$data);

		//Iterate through the data to find our website
		foreach($rows as $row) {
			
			if(strpos($row,$_SERVER['SERVER_NAME'])!==false) {

				$cols=explode("</td>",$row);
				$options['stat-blogjuice']=strip_tags($cols[0]);
				$options['stat-bloglines']=strip_tags($cols[2]);
				$options['stat-alexa']=strip_tags($cols[3]);
				$options['stat-technorati']=strip_tags($cols[4]);
				$options['stat-technorati-links']=strip_tags($cols[5]);
				$options['last-updated']=time();
				return $options;

			}

		}
	}


	function display(){

		$options = get_option('widget_blogjuice');

		if ( !is_array($options))
		{
			$options = array('title'=>'Blog Juice');
			$options = $this->update($options);
			update_option('widget_blogjuice',$options);
		} else if ($options['last-updated']<time()-604800) {
			$options = $this->update($options);
			update_option('widget_blogjuice',$options);
		}

		?>
		<!-- Blog Juice v<?php echo BLOGJUICE_VERSION; ?> -->

		<table style="font-size:10px;font-weight:bold;">
		<tr><td colspan="2" style="text-align:center;padding-bottom:5px;">
		<a href="http://www.text-link-ads.com/blog_juice/index.php?url=<?php echo urlencode($_SERVER['SERVER_NAME']); ?>&cat=all&ref=55499"><img src="http://www.text-link-ads.com/blog_juice/badges/juice_badge_<?php echo($options['stat-blogjuice'])?>.png" border="0" alt="My Blog Juice" /></a>
		</td></tr>
		<tr><td style="text-align:right;">Alexa: </td><td><?php echo $options['stat-alexa'] ?></td></tr>
		<tr><td style="text-align:right;">Bloglines: </td><td><?php echo $options['stat-bloglines'] ?></td></tr>
		<tr><td style="text-align:right;">Technorati: </td><td><?php echo $options['stat-technorati'] ?></td></tr>
		<tr><td style="text-align:right;">Links: </td><td><?php echo $options['stat-technorati-links'] ?></td></tr>
		<tr style="padding-top:5px;"><td style="text-align:right;">Source: </td><td><a href="http://www.mutube.com/projects/wordpress/blog-juice/?utm_source=plugin&utm_medium=sidebar">Blog Juice</a></td></tr>
		</table>
		<!-- End Blog Juice code --><?php 

	}


	/*
         STANDARD ADMIN FORM
         This form used by both widget & non widget forms (non-widget requires wrapper elsewhere,
         the widget wrapper is provided by the system
	*/

	function widget_control_form() {

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_blogjuice');
		if ( !is_array($options) )
		{
			$options = array('title'=>'Blog Juice');
		}

		if ( $_POST['blogjuice-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['blogjuice-title']));
			update_option('widget_blogjuice', $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.

		?>
			<div style="width:50%;float:left;">
			<p style="text-align:right;"><label for="blogjuice-title">Title: <input style="width: 200px;" id="blogjuice-title" name="blogjuice-title" type="text" value="<?php echo $title;?>" /></label></p>
			</div>
			<input type="hidden" id="blogjuice-submit" name="blogjuice-submit" value="1" />
        	<?php
           }


	function widget_blogjuice($args) {

		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		$options = get_option('widget_blogjuice');

		// These lines generate our output. Widgets can be very complex
		// but as you can see here, they can also be very, very simple.
        echo $before_widget . $before_title . $options['title'] . $after_title;
                $this->display(); //main call to get blogjuice icon
		echo $after_widget;

	}

/*
           SWITCH: IS THE WIDGET PLUGIN LOADED?
           If it is, then we use the widget system for admin. If it isn't we use the old-style.
           Note, the "standard" output method is available regardless of where you're editing
           the admin options.
*/

	function init() {

		if (function_exists('register_sidebar_widget') ) {
			//Do Widget-specific code
			register_sidebar_widget('Blog Juice', array(&$this,'widget_blogjuice'));
			// This registers our optional widget control form. Because of this
			// our widget will have a button that reveals a 300x100 pixel form.
	   		register_widget_control('Blog Juice', array(&$this,'widget_control_form'), 300, 100);
		} else {
				//We're doing this non-widget stylee 
				// THERE ARE NO OPTIONS when NON-WIDGET
		}

	}

}

$blogjuice = new blogjuice();

// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', array(&$blogjuice,'init'));

?>
