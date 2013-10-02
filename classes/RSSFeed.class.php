<?php
/**
 * Author: lee
 * Date Created: 26/09/2012 14:56
 */

class RSSFeed{
	const NEWLINE = PHP_EOL;
	const TAB = "\t";

	protected $encoding = 'UTF-8';	// current encoding type for the feed
	protected $output = '';			// current stored XML output

	protected function getDate($dateStr, $format = 'r'){
		$tz = get_option('timezone_string');
		if($tz){
			date_default_timezone_set($tz);
		}

		$dateStr = date($format, (strtolower($dateStr) == 'now') ? time() : strtotime($dateStr));

		date_default_timezone_set('UTC');

		return $dateStr;
	}

	/**
	 * Outputs the HTTP header
	 */
	protected function outputHeader(){
		// only set headers if they have not already been set
		if(!headers_sent()){
			header('Content-Type:application/xml; charset=' . $this->encoding);
		}
	}

	/**
	 * Builds the XML output for the feed and returns it
	 * 
	 * @param string $title
	 * @param string $url
	 * @param string $description
	 * @param array $items
	 * @param string $encoding
	 * @return string
	 */
	public function build($title, $url, $description, array $items, $encoding = 'UTF-8'){
		$this->encoding = strtoupper($encoding);

		$this->output = '';

		// calculate the last modified date
		$buildDate = '';
		foreach($items as $item){
			$thisDate = $this->getDate($item['date']);
			$buildDate = (strtotime($thisDate) > strtotime($buildDate)) ? $thisDate : $buildDate;
		}
		$buildDate = ($buildDate == '') ? $this->getDate('now') : $buildDate;


		// start building the XML
		$output = '';
		// output the XML doctype declaration
		$output .= '<?xml version="1.0" encoding="' . $this->encoding . '"?>' . self::NEWLINE;

		// define the RSS tag and the channel information
		$output .= '<rss version="2.0">' . self::NEWLINE .
						self::TAB . '<channel>' . self::NEWLINE .
							self::TAB . self::TAB . '<title>' . $title . '</title>' . self::NEWLINE .
							self::TAB . self::TAB . '<link>' . $url . '</link>' . self::NEWLINE .
							self::TAB . self::TAB . '<description>' . $description . '</description>' . self::NEWLINE .
							self::TAB . self::TAB . '<lastBuildDate>' . $buildDate . '</lastBuildDate>' . self::NEWLINE .
							self::TAB . self::TAB . '<language>en-gb</language>' . self::NEWLINE;

		// now loop through each item and output the info
		if(!empty($items)){
			$output .= self::NEWLINE;
			
			foreach($items as $item){
				$output .= self::TAB . self::TAB . '<item>' . self::NEWLINE .
								self::TAB . self::TAB . self::TAB . '<title>' . $item['title'] . '</title>' . self::NEWLINE .
								self::TAB . self::TAB . self::TAB . '<link>' . $item['url'] . '</link>' . self::NEWLINE .
								self::TAB . self::TAB . self::TAB . '<guid>' . $item['url'] . '</guid>' . self::NEWLINE .
								self::TAB . self::TAB . self::TAB . '<pubDate>' . $this->getDate($item['date']) . '</pubDate>' . self::NEWLINE .
								self::TAB . self::TAB . self::TAB . '<description><![CDATA[' . $item['description'] . ']]></description>' . self::NEWLINE;
				// check if the item has a thumbnail associated with it
				if(isset($item['thumb']) && ($item['thumb'] != '')){
					$output .=	self::TAB . self::TAB . self::TAB . '<media:thumbnail xmlns:media="http://search.yahoo.com/mrss/" url="' . $item['thumb'] . '" />' . self::NEWLINE;
				}
				$output .=	self::TAB . self::TAB . '</item>' . self::NEWLINE;
			}
		}

		// close the channel and rss tags
		$output .=		self::TAB . '</channel>' . self::NEWLINE .
					'</rss>';

		$this->output = $output;

		// return the output
		return $this->output;
	}

	/**
	 * Returns the current XML output as a string.
	 * Unlike the function output(), this does NOT
	 * set any headers.
	 *
	 * @return string
	 */
	public function getOutput(){
		return $this->output;
	}

	/**
	 * Outputs the XML and any appropriate headers
	 */
	public function output(){
		$this->outputHeader();		// output the header
		echo $this->getOutput();	// output the XML
		exit;
	}

	/**
	 * Saves the current XML feed to
	 * the given file
	 *
	 * @param string $file
	 * @return bool
	 */
	public function save($file){
		return false;
	}
}

/*class ChiefsFixturesRssPlugin extends Plugin
{
	// Fixtures link.
	var $details = 'http://www.exeterchiefs.co.uk/fixtures?fixture=';

	// The title that will appear on the RSS feed
	var $feed_title = 'Exeter Chiefs Fixtures';

	// The main URL associated with this feed
	var $feed_url = 'http://www.exeterchiefs.co.uk/';

	// Description of the RSS feed
	var $feed_description = 'The latest fixtures from Exeter Chiefs.';

	function service($parameters){
		$result = $this->db->query("SELECT fixtures.fixture_id AS id, DATE_FORMAT(fix_date, '%a, %d %b %Y') AS published_date, home_team, home_badge, away_team, away_badge, fix_info, details
					FROM fixtures
					LEFT JOIN results ON fixtures.fixture_id = results.fixture_id
					WHERE fix_date >= CURDATE() AND home_score IS NULL AND away_score IS NULL ORDER BY fix_date ASC LIMIT 10");

		$items = array();
		$last_changed = null;

		// Not really sure that we need this.
		while ($row = $result->fetch_assoc())
		{
			$items[] = $row;

			if (is_null($last_changed))
			{
				$last_changed = $row['published_date'];
			}
		}

		$output = '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n";
		$output .= '<rss version="2.0">' . "\n";
		$output .= '<channel>' . "\n";

		$output .= '<title> ' . $this->feed_title . '</title>' . "\n";
		$output .= '<link>' . $this->feed_url . '</link>' . "\n";
		$output .= '<description> ' . $this->feed_description . '</description>' . "\n";
		$output .= '<lastBuildDate>' . $last_changed . '</lastBuildDate>' . "\n"; // Mon, 12 Sep 2005 18:37:00 GMT
		$output .= '<language>en-gb</language>' . "\n";

		foreach ($items as $item)
		{
			//$url = $this->categories[$item['category_id']] . $item['id'];

			if ($item['home_badge'] != 1155 && isset($item['home_badge']) && $item['home_badge'] != '')
				$thumbnail_id = $item['home_badge'];
			else if ($item['away_badge'] != 1155 && isset($item['away_badge']) && $item['away_badge'] != '')
				$thumbnail_id = $item['away_badge'];

			$thumb_img = "";
			if (isset($thumbnail_id) && $thumbnail_id != '') {
				$thumbRes = $this->db->query("SELECT name, container_id, mime_type, size FROM assets WHERE id = ".$thumbnail_id);
				if ($thumbRes->num_rows > 0) {
					list($thumb_name, $thumb_container, $thumb_type, $thumb_len) = $thumbRes->fetch_row();
					// Assume default location for now.
					$thumb_url = 'http://www.exeterchiefs.co.uk/assets/Images/Badges/' . $thumb_name;
					$thumb_img = '<img src="' . $thumb_url . '" />';
				}
			}

			$url = $this->details . $item['id'];

			$output .= '<item>' . "\n";
			$output .= '<title>' . $item['home_team'] . ' v ' . $item['away_team'] . ' (' . $item['published_date'] . ')</title>' . "\n";
			$output .= '<link>' . $url . '</link>' . "\n";
			$output .= '<guid>' . $url . '</guid>' . "\n";
			$output .= '<pubDate>' . date('D, d M Y H:i:s') . ' ' . date('T') . '</pubDate>' . "\n"; // Mon, 12 Sep 2005 18:37:00 GMT
			$output .= '<description><![CDATA[ ' . str_replace('="assets', '="http://www.exeterchiefs.co.uk/assets', $item['fix_info']) . ' ]]></description>' . "\n";
			//if (isset($thumb_url) && $thumb_url != "") $output .= '<enclosure url="' . $thumb_url . '" type="' . $thumb_type . '" length="' . $thumb_len . '" />' . "\n";
			if (isset($thumb_url) && $thumb_url != "") $output .= '<media:thumbnail xmlns:media="http://search.yahoo.com/mrss/" url="' . $thumb_url . '" />' . "\n";

			$output .= '</item>' . "\n";
		}

		$output .= '</channel>' . "\n";
		$output .= '</rss>' . "\n";

		header("Content-Type: application/xml; charset=ISO-8859-1");
		echo $output;
	}
}*/
?>