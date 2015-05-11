<?php
/**
 * Plugin Name: DTN AgHost Integration
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: Allows DTN AgHost weather content to be displayed easily with short codes.
 * Version: 1.0.0
 * Author: Jacob L Sommerville
 * Author URI: http://URI_Of_The_Plugin_Author
 * License: GPL2
 */
 
/*  This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA */

if (!class_exists("DTN_AgHost")) {
	class DTN_AgHost {
		
		
		
		private $username; //your username
		private $password; //your password
		private $zip;
		private $ll;
		private $weathertoken;
		private $futurestoken;
		private $error;
		private $options;
		private $customclass;
		private $radarpopup = true;
		
		
		
		
					
		function DTN_AgHost() { //constructor
		
			if (!defined('WEATHER_TOKEN_METHOD')) {
				define('WEATHER_TOKEN_METHOD', 'http://api.aghost.net/api/weather/?method=getToken');
			}
			if (!defined('WEATHER_DATA_METHOD')) {
				define('WEATHER_DATA_METHOD', 'http://api.aghost.net/api/weather/?method=getStationWeather');
			}
			if (!defined('TOKEN_XML_PATH')) {
				define('TOKEN_XML_PATH', '/tns:RequestAndResponse/response/tns:AccountToken/URLEncodedToken');
			}
			if (!defined('HOURLY_XML_PATH')) {
				define('HOURLY_XML_PATH', '/tns:RequestAndResponse/response/tns:StationWeather/HourlyObservationData/HourlyObservation//*');
			}
			if (!defined('FORECAST_XML_PATH')) {
				define('FORECAST_XML_PATH', '/tns:RequestAndResponse/response/tns:StationWeather/DailyForecastData/DailyForecast');
			}
			
		
			add_action('wp_head', array(&$this, 'add_css'), 100);
			
			add_shortcode('DTN_Current', array(&$this, 'show_current_conditions'));
			add_shortcode('DTN_Radar', array(&$this, 'show_radar'));
			add_shortcode('DTN_Future', array(&$this, 'show_futures'));
			add_shortcode('DTN_Forecast', array(&$this, 'show_forecast'));
			
			include 'dtn-aghost-options.php';
			$this->options = get_option( 'dtn_option_name' ); 
			//setup defaults for variables
			$this->username = isset( $this->options['dtn_user'] ) ? $this->options['dtn_user'] : null;
			$this->password = isset( $this->options['dtn_password'] ) ? $this->options['dtn_password'] : null;
			$this->zip = isset( $this->options['dtn_zip'] ) ? $this->options['dtn_zip'] : '48413';
			if (isset($this->options['dtn_lat']) && isset($this->options['dtn_long'])){
				$this->ll = $this->options['dtn_lat'] . ',' . $this->options['dtn_long'];
			}
			$this->customclass = isset( $this->options['customclass']) ? sanitize_html_class($this-options['customclass']) : 
				sanitize_html_class('');
			$this->radarpopup = isset( $this->options['dtn_radarpopup']) ? true : false;
			
			
			
		}
		
 		function check_options() {
			$error = null;
			if (empty($this->username)) {$error .= 'Username is not set for DTN. Please set it in the admin plugin settings page.<br>';}
			if (empty($this->password)) {$error .= 'Password is not set for DTN. Please set it in the admin plugin settings page.<br>';}
			return $error;
		}
		
		function show_current_conditions() {
			
			$error = $this->check_options();
			if($error){ 
				return $error;
			}
			
			$show_all_nodes = false; //For Debug Purposes
			
			$this->get_token("weather");
			
			$xml = $this->get_xml("current", 1);
			$DTNxml = $xml->xpath(HOURLY_XML_PATH);
			
			
			$Temp = number_format(floatval($DTNxml[5])); //Remove Decimals from temperature
			$Feels = number_format(floatval($DTNxml[6]));
			$windsp = number_format(floatval($DTNxml[13]));
			$humidity = number_format(floatval($DTNxml[8]));
			$soil = number_format(floatval($DTNxml[15]));
			
			//begin Output buffering
			ob_start(); 
			?> 
				<div class="dtn-weather <?php echo $this->customclass?>">
					<div>
						<div class="dtn-temp">
							<div class="dtn-temp-digit"> <?php echo $Temp ?> </div>
							<div class="dtn-temp-scale">&#176;F</div>
						</div>
						<div class="dtn-conditions-right">
							<div class="dtn-conditions-desc">
								Wind: <?php echo $DTNxml[11] ?> at <?php echo $windsp ?> MPH
								<br />
								Humidity: <?php echo $humidity ?> &#37;
								<br />
								Soil Temperature: <?php echo $soil ?>&#176; F
							</div>
						</div>		
					</div>
					
					<div style="clear:both"></div>
					
					<div>
						<div class="dtn-conditions-left">
								<img src="<?php echo WP_PLUGIN_URL;?>/dtn-aghost/icons/<?php echo $DTNxml[4] ?>.png" />
								<div class="dtn-conditions-desc">
									<?php echo $DTNxml[3] ?> 
									<br />
									<?php echo $DTNxml[2] ?> Skies
									<br />
									Feels like <?php echo $Feels ?>&#176; F								
								</div>	
						</div>
						<div class="dtn-conditions-radar">
							
							<?php if ($this->radarpopup) { ?>
							<a class="cond-radar-img cbox-image" title="Local Radar by DTN" href="
								<?php 
								$radar_options = array('width'=> 800,'height'=> 600,'zoom'=> 5,'animate'=> true);
								echo $this->get_radar_url($radar_options);
								?>" >
								
								<img src="
									<?php 
									$radar_options = array('width'=> 250,'height'=> 90,'zoom'=> 3,'animate'=> true);
									echo $this->get_radar_url($radar_options);
									?>" />
								
							</a>
							<?php } ?>
								
								
						</div>
					</div>
					
					<div style="clear:both"></div>
					
				</div>
			<?php
			
			
			if ($show_all_nodes) {
				foreach ($DTNxml as $index=>$node) {
					echo '<br />' . $index . ' = ' . $node;
				}
			}
					
			$buffer .= ob_get_contents(); // place buffered content into a variable
			ob_end_clean(); // Cleanup output buffer
			return $buffer;
		}
		
		
		function show_forecast($atts) {
			
			$error = $this->check_options();
			if($error){ 
				return $error;
			}
			
			$show_all_nodes = false; //For Debug Purposes
			
			$atts = shortcode_atts( array(
				'days'		=> 3,
				), $atts, 'DTN_Forecast');			
			
			
			$this->get_token("weather"); //Get token if it doesn't exist;
			
			$xml = $this->get_xml("forecast", $atts['days']);
			
			for ($i = 1; $i <= $atts['days']; $i++){ //Make an array of forcast arrays
				$DTNxml[$i - 1] = $xml->xpath(FORECAST_XML_PATH . '[' . $i . ']//*'); 
			}
			
						
			
			ob_start(); //begin Output buffering
			echo '<div>';
			
			if ($show_all_nodes) { // For Debug
				foreach ($DTNxml[0] as $index=>$node) {
					echo '<br />' . $index . ' = ' . $node;
				}
			}
					
					
						
			for ($i = 0; $i < $atts['days']; $i++) {
				$rawdatetime = $DTNxml[$i][1];
				$dt = explode('T', $rawdatetime); // Break the raw date and time into array of Date and Time
				$timevar = strtotime($dt[0]); //Convert Date into Time type
				$weekday = date("D", $timevar); //Get day of the week from date
				
				$condition = $DTNxml[$i][4];
				$icon = $DTNxml[$i][5];
				$high = $DTNxml[$i][6];
				$low = $DTNxml[$i][7];
				$precip = $DTNxml[$i][11];
				$GDD = $DTNxml[$i][25];
				$CHU = $DTNxml[$i][26];
				
				
				
				
				?>

				<div class="dtn-forcast-single">
					<p>
					<?php echo $weekday; ?>
					<br>
					<img src="<?php echo WP_PLUGIN_URL;?>/dtn-aghost/icons/FC/<?php echo $icon ?>.png" />
					<br>
					<?php echo $condition; ?>
					<br>
					High <?php echo $high; ?> &deg; F
					<br>
					Low <?php echo $low; ?> &deg; F
					<br>
					Chance of
					<br>
					Precip <?php echo $precip; ?>&#37;
					<br>
					<abbr title="&quot;Growing degree days (GDDs) are used to estimate the growth and development of plants and insects during the growing season&quot;">GDD</abbr> <?php echo $GDD; ?>
					<br>
					<abbr title="&quot;Crop heat units (CHU) are based on a similar principle to growing degree days&quot;">CHU</abbr> <?php echo $CHU; ?>
					<br>
					</p>
				</div>
				
				<?php
			}
			
			
			echo '</div>';
			
			$buffer .= ob_get_contents(); // place buffered content into a variable
			
			ob_end_clean(); // Cleanup output buffer
			return $buffer;
		}
		
		function show_radar($atts) {
			
			$error = $this->check_options();
			if($error){ 
				return $error;
			}
			
			$this->get_token("weather"); //get weather token, if it doesn't already exist
			
			$atts = shortcode_atts( array(
				'width'		=> 640,
				'height'	=> 480,
				'zoom'		=> 3,
				'animate'	=> false
				), $atts, 'DTN_Radar');
				
			
			
			ob_start(); //start output buffer
			?>
				<div class="dtn-radar">
					<img src="<?php echo $this->get_radar_url($atts); ?>"/>
				</div>
			<?php
			$buffer = ob_get_contents(); //store output buffer
			ob_end_clean(); //clean up output buffer
			return $buffer;
		}
		
		function get_radar_url($atts) {
			return 'http://agwx.dtn.com/RegionalRadar.cfm?ll=' . $this->ll . '&key=' . $this->weathertoken . '&level=' . 
			$atts['zoom'] . '&animate=' . $atts['animate'] . '&width=' . $atts['width'] . '&height=' . $atts['height'];
		}
		
		function get_token($type) { //Get authentication token from aghost site
			//only have weather type token right now			
			
			switch ($type) {
				case "weather":
					if (!$this->weathertoken) {
						$url = WEATHER_TOKEN_METHOD . '&username='. $this->username . '&password=' . $this->password;
						$xmlfile = simplexml_load_file($url);
						$this->weathertoken = $xmlfile->xpath(TOKEN_XML_PATH)[0];
					}
					break;
			}
			
			return;		
			
		}
		
		function get_xml($type, $observations) {
			switch ($type) {
				case "current":
					$url = WEATHER_DATA_METHOD . '&token=' . $this->weathertoken . '&zip=' . $this->zip . '&HRLYOBS=' . $observations;
					break;
				case "forecast":
					$url = WEATHER_DATA_METHOD . '&token=' . $this->weathertoken . '&zip=' . $this->zip . '&DAILYFC=' . $observations;
					break;
				case "daily":
					$url = WEATHER_DATA_METHOD . '&token=' . $this->weathertoken . '&zip=' . $this->zip . '&DAILYOBS=' . $observations;
					break;
			}
			return simplexml_load_file($url);
		}
		
		function add_css() { 
			?>			
			<link rel="stylesheet" href="<?php echo WP_PLUGIN_URL;?>/dtn-aghost/css/dtn-style.css" type="text/css" />			
			<?php
		}
		
		function show_futures($atts) {
			
			$error = $this->check_options();
			if($error){ 
				return $error;
			}
			
			$atts = shortcode_atts( array(
				'service'		=> 'table'
				), $atts, 'DTN_Future'); 
				
						
			$postfields = array(
				'username'	=> $this->username,
				'password'	=> $this->password,
				'service'	=> $atts['service'],
				'noCss'		=> 0
			);
			
			$results = '<div class="dtn-future">';
			
			$handle = curl_init(); //Start Curl
			curl_setopt ($handle, CURLOPT_SSL_VERIFYPEER, 0);  //prevent certificate checking
			curl_setopt ($handle, CURLOPT_URL,"https://api.aghost.net/api/futures/index.cfm/"); //API URL 
			curl_setopt ($handle, CURLOPT_POSTFIELDS, $postfields); //parameters
			curl_setopt ($handle, CURLOPT_RETURNTRANSFER, TRUE); //return the data instead of a "succeeded" message
			curl_setopt ($handle, CURLOPT_FRESH_CONNECT, TRUE); //prevent use of cache
			curl_setopt ($handle, CURLOPT_VERBOSE, TRUE);
			$results .= curl_exec ($handle); //execute 
			
			$results .= '<//div>';
			
			curl_close($handle); 
			
			return $results;
		}
			
		
	}
}

if (class_exists("DTN_AgHost")) {
	$DTN = new DTN_AgHost();
}
 
 ?>