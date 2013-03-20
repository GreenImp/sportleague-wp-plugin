<?php
/**
 * Author: lee
 * Date Created: 28/08/2012 16:17
 */

class Team{
	private $id = 0;
	private $name = '';
	private $logo = '';

	public function __construct(array $attributes){
		foreach($attributes as $key => $val){
			if(isset($this->{$key})){
				$this->{$key} = $val;
			}
		}
	}

	/**
	 * Returns the teams's ID
	 *
	 * @return int
	 */
	public function getID(){
		return $this->id;
	}

	/**
	 * Returns the team's name
	 *
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * Returns the Team's logo.
	 * By default, it returns the entire
	 * HTML output, including the <img> tag.
	 * If you only want the URL, set $img to
	 * boolean false.
	 *
	 * @param int|string $size
	 * @param bool $img
	 * @return string
	 */
	public function getLogo($size = 0, $img = true){
		$logos = $this->logo;
		$logo = '';
		if(($logos != '') && is_array($logos = json_decode($logos)) && !empty($logos)){
			// the logo is set

			// set the requested size
			$size = (is_numeric($size) && ($size > 0)) ? $size : (($size === 'small') ? 'small' : 0);

			// loop through each logo and get the one closes to the required size
			$cLogo = null;
			foreach($logos as $logo){
				if($cLogo == null){
					// no logo set yet
					$cLogo = $logo;
				}elseif($size === 'small'){
					// looking for smallest logo
					if($logo->size < $cLogo->size){
						// logo is smaller than current one
						$cLogo = $logo;
					}
				}elseif($size == 0){
					// looking for largest logo
					if($logo->size > $cLogo->size){
						// logo is larger than the current one
						$cLogo = $logo;
					}
				}elseif(abs($size - $cLogo->size) > abs($logo->size - $size)){
					// this logo is closest to the defined size
					$cLogo = $logo;
				}
			}

			if($img){
				// We are returning an img tag - define the HTML
				$logo = '<img src="' . $cLogo->file . '" alt="' . $this->getName() . '"';
				if(is_numeric($size) && ($size > 0)){
					// a size has been defined - set it
					$logo .= ' width="' . $size . '" height="' . $size . '"';
				}
				$logo .= '>';
			}else{
				$logo = $cLogo->file;
			}
		}

		// return the logo
		return $logo;
	}
}
?>