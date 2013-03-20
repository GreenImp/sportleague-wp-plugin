<?php
/**
 * Author: lee
 * Date Created: 02/10/2012 13:18
 */

class Pagination{
	private $allowPost = false;		// if true, we also check post variables for page number/limit (get take priority)

	private $limit = 0;				// amount of results per page
	private $currentPage = 1;		// the current page number
	private $siblings = 3;			// number of pages to show either side of the current page
	private $ends = 1;				// the number of 'end' pages to show
	private $parameter_page = 'p';	// GET parameter for setting page
	private $parameter_limit = 'l';	// GET parameter for setting limit

	public function __construct($paramP = 'p', $paramL = 'l', $allowPost = false){
		$this->parameter_page = urlencode($paramP);
		$this->parameter_limit = urlencode($paramL);

		// set whether post variables are allowed
		$this->allowPost = !!$allowPost;

		// set the limit
		if(isset($_GET[$this->parameter_limit])){
			$this->limit($_GET[$this->parameter_limit]);
		}elseif($this->allowPost && isset($_POST[$this->parameter_limit])){
			$this->limit($_POST[$this->parameter_limit]);
		}

		// set the current page
		if(isset($_GET[$this->parameter_page]) && is_numeric($_GET[$this->parameter_page]) && ($_GET[$this->parameter_page] > 0)){
			$this->currentPage = $_GET[$this->parameter_page];
		}elseif($this->allowPost && isset($_POST[$this->parameter_page]) && is_numeric($_POST[$this->parameter_page]) && ($_POST[$this->parameter_page] > 0)){
			$this->currentPage = $_POST[$this->parameter_page];
		}
	}

	/**
	 * Returns the URL for a specific page
	 *
	 * @param $pageNum
	 * @return string
	 */
	private function getLink($pageNum){
		// get the URL without any GET variables
		$url = strtok($_SERVER['REQUEST_URI'], '?') . '?';

		// remove the page and limit from the variables
		unset($_GET[$this->parameter_page]);
		unset($_GET[$this->parameter_limit]);

		if(count($_GET) > 0){
			$url .=  http_build_query($_GET, '', '&amp;') . '&amp;';
		}
		$url .= $this->parameter_page . '=%d&amp;' . $this->parameter_limit . '=' . $this->limit();

		return sprintf($url, $pageNum);
	}

	/**
	 * Returns the currently viewed page number
	 *
	 * @return int
	 */
	public function currentPage(){
		return $this->currentPage;
	}

	/**
	 * Sets or returns the limit (amount per page).
	 * If $limit is defined, then it will be set and
	 * the Pagination object returned.
	 * If $limit is not set, the current limit will
	 * be returned.
	 *
	 * @param int $limit
	 * @return Pagination|string
	 */
	public function limit($limit = -1){
		if(($limit == -1) || !is_numeric($limit)){
			return $this->limit;
		}

		if($limit > 0){
			$this->limit = $limit;
		}

		return $this;
	}

	/**
	 * Sets or returns the siblings (number of pages to
	 * show either side of the current page).
	 * If $siblings is defined, then it will be set and
	 * the Pagination object returned.
	 * If $siblings is not set, the current number will
	 * be returned.
	 *
	 * @param int $siblings
	 * @return Pagination|string
	 */
	public function siblings($siblings = -1){
		if($siblings == -1){
			return $this->siblings;
		}

		if(is_numeric($siblings) && ($siblings >= 0)){
			$this->siblings = $siblings;
		}

		return $this;
	}

	/**
	 * Sets or returns the maximum number of pages that will be
	 * displayed next to the prev/next buttons.
	 * If $ends is defined, then it will be set and
	 * the Pagination object returned.
	 * If $ends is not set, the current number will
	 * be returned.
	 *
	 * @param $ends
	 * @return int|Pagination
	 */
	public function ends($ends = -1){
		if($ends == -1){
			return $this->ends;
		}

		if(is_numeric($ends) && ($ends >= 0)){
			$this->ends = $ends;
		}

		return $this;
	}

	/**
	 * Calculates the offset for results,
	 * depending on the current page number
	 * and limit
	 *
	 * @return int
	 */
	public function offset(){
		return $this->limit() * ($this->currentPage()-1);
	}

	/**
	 * Builds the pagination output and returns it
	 *
	 * @param $resultCount
	 * @return string
	 */
	public function output($resultCount){
		$output = '';

		if(($resultCount > 0) && (($limit = $this->limit()) > 0)){
			// calculate the amount of pages
			$resultCount = is_array($resultCount) ? count($resultCount) : $resultCount;	// amount of results
			$pageCount = ceil($resultCount / $limit);									// amount of pages

			// get the current page number
			$currentPage = $this->currentPage();
			$currentPage = ($currentPage > $pageCount) ? $pageCount : $currentPage;

			// get the pagination page counts
			$siblingPageCount = $this->siblings();
			$endPageCount = $this->ends();

			// output the previous buttons
			if($currentPage > 1){
				// output the previous button
				$output .= '<a href="' . $this->getLink($currentPage-1) . '" title="previous" class="pev">Previous</a>';

				// output the end pages
				for($i = 1; ($i <= $endPageCount) && ($i < $currentPage-$siblingPageCount); $i++){
					$output .= '<a href="' . $this->getLink($i) . '" title="page ' . $i . '" class="end">' . $i . '</a>';
				}

				// output the sibling pages
				$s = $currentPage-$siblingPageCount;
				if($i < $s){
					$output .= '<span class="spacer">...</span>';
				}
				for($i = ($s < 1) ? 1 : $s; $i < $currentPage; $i++){
					$output .= '<a href="' . $this->getLink($i) . '" title="page ' . $i . '" class="sibling">' . $i . '</a>';
				}
			}

			// output the current page
			$output .= '<span title="page ' . $currentPage . '" class="current">' . $currentPage . '</span>';

			// output the next buttons
			if($currentPage < $pageCount){
				// output the sibling pages
				for($i = $currentPage+1; ($i <= $currentPage+$siblingPageCount) && ($i <= $pageCount); $i++){
					$output .= '<a href="' . $this->getLink($i) . '" title="page ' . $i . '" class="sibling">' . $i . '</a>';
				}

				// output the end pages
				$s = ($pageCount-$endPageCount)+1;
				$s = ($s <= $i-1) ? $i : $s;
				if($s > $i){
					$output .= '<span class="spacer">...</span>';
				}
				for($i = $s; $i <= $pageCount; $i++){
					$output .= '<a href="' . $this->getLink($i) . '" title="page ' . $i . '" class="end">' . $i . '</a>';
				}

				// output the next button
				$output .= '<a href="' . $this->getLink($currentPage+1) . '" title="next" class="next">Next</a>';
			}
		}

		return $output;
	}
}
?>