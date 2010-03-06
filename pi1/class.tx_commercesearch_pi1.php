<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Andreas Jonderko <typo3@alnovi.de>
 *  (c) 2009 Krunoslav Sever <ksever@alice-dsl.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once (PATH_tslib . 'class.tslib_pibase.php');

/*
 * Plugin 'Commerce Search' for the 'commerce_search' extension.
 *
 * @author	Andreas Jonderko <typo3@alnovi.de>
 * @package	TYPO3
 * @subpackage	tx_commercesearch
 */
class tx_commercesearch_pi1 extends tslib_pibase {
	/* standard extension variables */
	var $prefixId = 'tx_commercesearch_pi1';
	var $scriptRelPath = 'pi1/class.tx_commercesearch_pi1.php';
	var $extKey = 'commerce_search';
	/* parent extension data */
	var $commercePrefix = "tx_commerce_pi1";
	var $commerceUpload = "uploads/tx_commerce";
	/* data submitted via GET/POST */
	var $dataArr = array();
	/* database table names */
	var $_tableCategories = "tx_commerce_categories";
	var $_tableCategoriesParent = "tx_commerce_categories_parent_category_mm";
	var $_tableProducts = "tx_commerce_products";
	var $_tableArticles = "tx_commerce_articles";
	var $_tableProductsCategories = "tx_commerce_products_categories_mm";
	var $_tableProductsArticles = "tx_commerce_products_articles_mm";
	/* template variables */
	var $display = true;    // whether the extension is inserted through the backend, or as typoscript
	var $template = array(); // holds the hole template under ["total"] and the subparts under ["total]["subpart"]
	var $path = "";    // holds the root path to this extension (pi1)
	/* product attribute selction */
	var $product = array("uid", "pid", "title","subtitle","tstamp","teaserimages", "images");
	
	/*
	PRODUCT ATTRIBUTES

	uid
	pid
	tstamp
	crdate
	sorting
	cruser_id
	t3ver_oid
	t3ver_id
	t3ver_label
	sys_language_uid
	l18n_parent
	l18n_diffsource
	deleted
	hidden
	starttime
	endtime
	fe_group
	title
	subtitle
	navtitle
	keywords
	description
	teaser
	teaserimages
	images
	categories
	manufacturer_uid
	attributes
	articles
	attributesedit
	uname
	relatedpage
 
	*/

	/* article attribute selection */
	var $article = array("uid" => 1, "pid" => 1, "title" => 1,"subtitle" => 1,"tstamp" => 1, "images" => 1);
    
	/*
	 uid
	 pid
	 tstamp
	 crdate
	 sorting
	 cruser_id
	 t3ver_oid
	 t3ver_id
	 t3ver_label
	 sys_language_uid
	 l18n_parent
	 l18n_diffsource
	 deleted
	 hidden
	 starttime
	 endtime
	 fe_group
	 title
	 subtitle
	 navtitle
	 images
	 ordernumber
	 eancode
	 description_extra
	 plain_text
	 prices
	 tax
	 article_type_uid
	 supplier_uid
	 uid_product
	 article_attributes
	 attribute_hash
	 attributesedit
	 classname
	 relatedpage
	*/
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main ($content, $conf) {
		$this->init($conf);
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1; // Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
		$content = $this->renderFormular();
		if ($this->dataArr["submit"] && $this->display){
			$content .= $this->renderResult();
		}
		return $this->pi_wrapInBaseClass($content . $this->debug);
	}
    
   /*********************************************************
   *  CONFIGURATION
   *********************************************************/
	
	/**
	 * builds a unique name for some select or inputfieldss
	 *
	 * @param string $name
	 * @return string
	 */
	function addPrefix ($name) {
		return $this->prefixId . "[" . $name . "]";
	}

	/**
	 * init function.
	 * 
	 * @access public
	 * @param mixed $conf
	 * @return void
	 */
	function init ($conf) {
		/* init/store typo configuration */
		$this->conf = $conf;
		$this->conf["id"] = $this->conf["targetPage"] ? $this->conf["targetPage"] : $GLOBALS['TSFE']->id; // current page ID
		$this->conf["page"] = $GLOBALS['TSFE']->page; // current page informations
		/* get extension configuration */
		$this->confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		/* GET/POST data for commerce_search */
		$this->dataArr = t3lib_div::_GP($this->prefixId);
		/* GET/POST data for tx_commerce */
		$this->dataArrCommerce = t3lib_div::_GP($this->commercePrefix); // create Array from GET/POST Vars
		/* flexconf data for plugin */
		$this->pi_initPIflexForm();
		$piFlexForm = $this->cObj->data['pi_flexform'];
		if ($piFlexForm['data']){
			foreach ($piFlexForm['data'] as $sheet => $data){
				foreach ($data as $lang => $value){
					foreach ($value as $key => $val){
						$this->flexConf[$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
					}
				}
			}
		}	else {
			/* no flexconf data. typoscript call */
			$this->display = false;
		}
		/* root path to this directory */
		$this->path = getcwd()."/typo3conf/ext/".$this->extKey."/pi1";
	}
    
	/*********************************************************
	 *  RENDERING
   *********************************************************/
    
	/**
	 * initializes the Template with the right Marker
	 * @param string $mainMarker : Which Templatesection to initialize as "total"
	 */
	function initTemplate($mainMarker){
		$arrMarker = strtolower($mainMarker);
		$tmplMarker = "###".strtoupper($mainMarker)."###";
		if (!$this->template['total']){
			if ($this->flexConf["template"]){
				$file = PATH_site.'uploads/tx_commerce/'.$this->flexConf["template"];
				$this->template['total'] = $this->cObj->fileResource($file);    
			} else {
				$file = $this->path."/".$this->prefixId.".html";
				$this->template['total'] =  $this->loadFile($file);   
			}
		}
		$part = $this->cObj->getSubpart($this->template['total'],$tmplMarker);
		$this->template['subparts'][$arrMarker] = $part;
		return $this->template['subparts'][$arrMarker];
	}
	
	/**
	 * returns the content of a given file
	 *
	 * @param string $filename
	 * @return string
	 */
	function loadFile($filename) {
		return file_get_contents($filename);
	}
	
	/**
	 * renders the template with the given marker and subpartarray, uses the typo3 function
	 *
	 * @param string $template
	 * @param array $markerArray
	 * @param array $subpartArray
	 * @return string
	 */
	function renderTemplate($template, $markerArray, $subpartArray=array()) {
		$rendered = $this->cObj->substituteMarkerArrayCached($template,$markerArray, $subpartArray, array());        
		return $rendered;
	}

	/**
	 * retrieves the results and renders it
	 *
	 * @return string
	 */
	function renderResult () {
		$rendered = '';
		$categoryUID = $this->dataArr["categories"];
		$searchValue = $this->dataArr["searchValue"];
		$searchType = $this->dataArr["searchtype"];
		$pointer = $this->dataArr["pointer"];
		$childs = $this->getCategoryChilds($categoryUID);
		$childs[$categoryUID] = $categoryUID; // self too!
		$results = $this->getResults($searchType, $searchValue, $childs, $pointer);
		$markerArr = array();
		$subpartArr = array();
		if (sizeof($results["data"])>0) {
			$tmpl = $this->initTemplate("result");
			$list = $this->renderList($results["data"], $markerArr);
			$this->fillMarker($subpartArr, "result_single_even", $list);    // fill the subpart
			$this->fillMarker($subpartArr, "result_single_odd", "");    // empty the other, dont need it anymore
			$this->fillMarker($markerArr, "page_browser", '');	// reset marker
			// create the page browser
			if (($this->flexConf['showPageBrowser'] == 1) && ($results["total"] > $this->flexConf['maxRecords'])){
				$this->internal['res_count'] = $results["total"];
				$this->internal['results_at_a_time'] = $this->flexConf['maxRecords'];
				$this->internal['maxPages'] = $this->flexConf['maxPages'];
				$wrapArr = array();
				$tableParams = '';
				$this->internal['pagefloat'] = $this->piVars['pointer'];
				$this->internal['dontLinkActivePage'] = $this->flexConf['dontLinkActivePage'];
				$this->internal['showFirstLast'] = $this->flexConf['showFirstLast'];
				$this->internal['showRange'] = $this->flexConf['showRange'];
				$page_browser = $this->pi_list_browseresults($this->flexConf['showItemCount'], $tableParams, $wrapArr, 'pointer', $this->flexConf['hscText']);
				$this->fillMarker($markerArr, "page_browser", $page_browser);
			}
		}	else {
			// there is no result, take the right template and fill markers
			$tmpl = $this->initTemplate("no_result");
			$this->fillMarker($markerArr, "label_no_result",$this->pi_getLL("no_results"));
			return $this->renderTemplate($tmpl, $markerArr);
		}
		return $this->renderTemplate($tmpl, $markerArr, $subpartArr);
	}
	
	/* render a list of letters, that are linked to the search */    
	function renderLetterNavigation() {
		$tmpl = $this->initTemplate("letter_navigation");
		$markerArr = array();
		$letters = array( '0','1','2','3',
											'4','5','6','7',
											'8','9','a','b',
											'c','d','e','f',
											'g','h','i','j',
											'k','l','m','n',
											'o','p','q','r',
											's','t','u','v',
											'w','x','y','z',
											);
		$rendered = '';
		foreach ($letters as $key=>$value) {
			$key='letter';
			$this->fillMarker($markerArr, $key, $value);	// fill just the marker
			$key='letter_link';	
			$link = $this->link("search", $value, null);            
			$this->fillMarker($markerArr, $key, $link);
			$rendered .= $this->renderTemplate($tmpl, $markerArr);
		}
		return $rendered;    	
	}

	/**
	 * renders the given data array (results) in a list from the template file
	 *
	 * @param array $results
	 * @param array $parentMarker
	 * @return string
	 */
	function renderList ($results, &$parentMarker) {
		$rendered = '';
		$tmpl_even = $this->initTemplate("result_single_even"); 
		$tmpl_odd = $this->initTemplate("result_single_odd");
		if (sizeof($results)>0) {
			$num = 0;   
			foreach ($results as $uid => $result) {
				$markerArr = array();
				foreach ($result as $objectType=>$fields) {
					foreach ($fields as $field=>$value) {
						$this->fillMarker($markerArr, $objectType."_".$field, $value);
						// special field handling like image or link
						switch ($field) {
						case "uid":
							$link = $this->link($objectType, $value, $result);    // give the object type (e.g. "product") and his uid to create a link)        
							$this->fillMarker($markerArr, $objectType."_link", $link);                                    
							break;
						case "teaserimages":
						case "images":
							$file = $this->commerceUpload."/".$value;
							$title = $result[$objectType]["title"];
							$img = $this->renderImage($file, $title);    // give the object type (e.g. "product") and his uid to create a link)
							$this->fillMarker($markerArr, $objectType."_".$field, $img);
							break;
						}
					}
				}
				if ($num%2 == 0) {
					$rendered .= $this->renderTemplate($tmpl_even, $markerArr);        
				} else {
					$rendered .= $this->renderTemplate($tmpl_odd, $markerArr);
				}
				++$num;
			}
		}
		$this->fillMarker($parentMarker, "product_header", $this->pi_getLL("product"));
		$this->fillMarker($parentMarker, "category_header", $this->pi_getLL("category"));
		$this->fillMarker($parentMarker, "image_header", $this->pi_getLL("picture"));
		return $rendered;
	}

	/**
	 * renders the hole formular from the template file
	 *
	 * @return string
	 */
	function renderFormular () {
		$tmpl = ($this->display) ? $this->initTemplate("formular") : $this->initTemplate("formular_ts");
		$markerArray = array();
		$formlink = $this->pi_getPageLink($this->conf["id"]); 
		$this->fillMarker($markerArray, "form_begin", $this->renderTag("form", array("method" => "post" , "action" => $formlink , "class" => $this->addPrefix("form")), "", false));
		// search input with label        
		$label_search = $this->renderTag("label", array("for" => $this->addPrefix("searchValue")), $this->pi_getLL("search"));
		$this->fillMarker($markerArray, "label_search", $label_search);
		$input_search = $this->renderTag("input", array("type" => "text" , "name" => $this->addPrefix("searchValue") , "value" => $this->dataArr["searchValue"]));
		$this->fillMarker($markerArray, "input_search", $input_search); 
		// category select
		$label_categories = $this->renderTag("label", array("for" => $this->addPrefix("categories")), $this->pi_getLL("category"));
		$this->fillMarker($markerArray, "label_categories", $label_categories);
		$options = $this->getCategories();
		if ($this->dataArr["categories"]) {
			$options[$this->dataArr["categories"]]["selected"] = true;
		}
    $select_categories = $this->renderSelect($options, $this->addPrefix("categories"), 'navtitle');
		$this->fillMarker($markerArray, "select_categories", $select_categories);
		// search type selection
		$label_searchtype = $this->renderTag("label", array("for" => $this->addPrefix("searchtype")), $this->pi_getLL("searchtype_select"));
		$this->fillMarker($markerArray, "label_selectsearchtype", $label_searchtype);
		unset($options);	// reset selection
		$options = array(0 => array("content"=>$this->pi_getLL("searchtype_default")),
										 1 => array("content"=>$this->pi_getLL("searchtype_exact")),
										 2 => array("content"=>$this->pi_getLL("searchtype_startswith")),
										 );
		// mark as selected
		if ($this->dataArr["searchtype"]){
			$options[$this->dataArr["searchtype"]]["selected"] = true;
		}
		$select_searchtype = $this->renderSelect($options, $this->addPrefix("searchtype"));
		$this->fillMarker($markerArray, "select_searchtype", $select_searchtype);
		// submit button
		$this->fillMarker($markerArray, "input_submit", $this->renderTag("input", array("value" => $this->pi_getLL("search_submit"), "type" => "submit" , "name" => $this->addPrefix("submit"))));
		// end formular
		$this->fillMarker($markerArray, "form_end", '</form>');
		// letter navigation
		$letter_navigation = '';
		if ($this->flexConf["showLetterNavigation"] == 1 ){
			$letter_navigation = $this->renderLetterNavigation();
		}
		$this->fillMarker($markerArray, "letternavigation", $letter_navigation);
		return $this->renderTemplate($tmpl, $markerArray);
	}
    
	/**
	 * fills the given array-reference on a key (wrapped with #) with the given content
	 *
	 * @param array $markerArray
	 * @param string $pointer
	 * @param mixed $content
	 */
	function fillMarker(&$markerArray, $pointer, $content) {
		$markerArray["###".strtoupper($pointer)."###"] = $content;
	}
    
	/**
	 * renders a single tag with attributes and content in it
	 *
	 * @param string $tag
	 * @param array $attributes
	 * @param string $content
	 * @param boolean $close
	 * @return string
	 */
	 function renderTag ($tag, $attributes = array(), $content = "", $close = true) {
		 $tag = strtolower($tag);
		 $rendered = '';
		 $rendered .= '<' . $tag;
		 foreach ($attributes as $key => $value){
			 $rendered .= ' ' . $key . '="' . $value . '"';
		 }
		 switch ($tag) {
		 case "img":
		 case "input":
			 $rendered .= "/>";
			 break;
		 default:
			 $rendered .= '>';
			 if ($close) {
				 $rendered .= $content . '</' . $tag . '>';
			 }
			 break;
		 }
		 return $rendered;
	 }
    
	 /**
		* render a select with given options
		*
		* @param array $options
		* @param string $name
		* @param integer $size
		* @return string
		*/
	 function renderSelect ($options, $name, $titlefield = "content", $size = 1){
		 $rendered = '';
		 $rendered .= '<select name="' . $name . '" size="' . $size . '">';
		 foreach ($options as $key => $option) {
			 $attributes = array("value" => $key);
			 if ($option["selected"]) {
				 $attributes["selected"] = "selected";
			 }
			 $rendered .= $this->renderTag("option", $attributes, $option[$titlefield]);
		 }
		 $rendered .= '</select>';
		 return $rendered;
	 }

	 /**
		* creates a link to given category uid
		*
		* @param string $title
		* @param integer $catUid
		* @return string
		*/
	 function link($type, $uid, $result, $title=null) {
		 switch ($type){
		 case "product":
			 $typoLinkConf = array();
			 $typoLinkConf['parameter'] = $this->confArr["pid."]["productList"];
			 $typoLinkConf['useCacheHash'] = 1;
			 $typoLinkConf['additionalParams'] = '&' . $this->commercePrefix . '[showUid]=' . $uid;
			 $typoLinkConf['additionalParams'] .= '&' . $this->commercePrefix . '[catUid]=' . $result["category"]['uid'];
			 break;
		 case "article":
			 $typoLinkConf = array();
			 $typoLinkConf['parameter'] = $this->confArr["pid."]["ArticleDetail"];
			 $typoLinkConf['useCacheHash'] = 1;
			 $typoLinkConf['additionalParams'] = '&' . $this->commercePrefix . '[showUid]=' . $uid;
			 $typoLinkConf['additionalParams'] .= '&' . $this->commercePrefix . '[catUid]=' . $result["category"]['uid'];
			 break;
		 case "category":
			 $typoLinkConf = array();
			 $typoLinkConf['parameter'] = $this->confArr["pid."]["productList"];
			 $typoLinkConf['useCacheHash'] = 1;
			 $typoLinkConf['additionalParams'] = '&' . $this->commercePrefix . '[catUid]=' . $uid;
			 break;
		 case "search":
			 $typoLinkConf = array();
			 $typoLinkConf['parameter'] = $this->conf["id"];
			 $typoLinkConf['useCacheHash'] = 1;
			 $category = '&' . $this->prefixId . '[categories]='.$this->flexConf["startCategory"];
			 if (isset($this->dataArr['categories'])){
				 $category = '&' . $this->prefixId . '[categories]='.$this->dataArr['categories'];
			 } else if (isset($this->dataArrCommerce['catUid'])) {
				 $category = '&' . $this->prefixId . '[categories]='.$this->dataArrCommerce['catUid'];
			 }
			 $typoLinkConf['additionalParams'] = '&' . $this->prefixId . '[searchValue]=' . $uid.''. $category.									
				 '&' . $this->prefixId . '[searchtype]=2'.
				 '&' . $this->prefixId . '[submit]=go';
			 break;
		 }
		 if ($title) {
			 return $this->cObj->typoLink($title, $typoLinkConf);    
		 } else {
			 return htmlentities($this->cObj->typoLink_url($typoLinkConf));
		 }
	 }
    
	 /**
		* creates an image by given parameter
		*
		* @param string $file
		* @param string $altText
		* @param integer $maxW
		* @param integer $maxH
		* @return string
		*/
	 function renderImage($file,$altText="") {
		 $imgTSConfig= $this->conf['image.'];
		 $imgTSConfig['file'] = $file;
		 $imgTSConfig['altText'] = $altText;
		 $imgTSConfig['titleText'] = $altText;                    
		 $image = $this->cObj->IMAGE($imgTSConfig);
		 /*
			if ($altText == "") $altText = "image";
			$imgTSConfig = array();
			$imgTSConfig['file'] = $file;
			$imgTSConfig['altText'] = $altText;
			$imgTSConfig['titleText'] = $conf["altText"];
			$imgTSConfig['file.']['maxW'] = $conf["maxW"];
			$imgTSConfig['file.']['maxH'] = $conf["maxH"];
			$image = $this->cObj->IMAGE($imgTSConfig);
			$image = str_replace('"',"'",$image);
		 */
		 return $image;
	 }
	 
	 /*********************************************************
		*  DATA RETRIEVE
		*********************************************************/
	 
	 /**
		* retrives the results from the database by searchstring
		*
		* @param string $searchValue
		* @param integer/array $categoryUID
		* @param integer $pointer
		* @return array
		*/
	 function getResults ($searchType, $searchValue, $categoryUID, $pointer) {
		 $where = null;
		 $limit = null;
		 $groupBy = null;
		 $orderBy = null;
		 $results = array();
		 $results["total"] = 0;    // default value
		 if ($this->flexConf["showPageBrowser"] == 1){
			 $num = $this->flexConf["maxRecords"];
			 $offset = $this->flexConf["maxRecords"] * $pointer;
			 // GET THE NUMBER OF ALL HITS
			 $sql = 'SELECT ' . $this->makeSELECTclause(true);
       $sql .= chr(10) . 'FROM ' . $this->makeFROMclause();
			 $sql .= chr(10) . 'WHERE ' . $this->makeWHEREclause($searchValue, $searchType, $categoryUID);;
			 //$this->debug .= t3lib_div::view_array(array("sqlc" => $sql));
			 $res = mysql_query($sql);
			 if ($res) {
				 $row = mysql_fetch_assoc($res);
				 $results["total"] = $row["total"];
				 $limit = 'LIMIT ' . $offset . ',' . $num;
			 } else {
				 return $results;	// return here, if no hits!
			 }
		 }
		 $sql = 'SELECT ' . $this->makeSELECTclause(false);
		 $sql .= chr(10) . 'FROM ' . $this->makeFROMclause();
		 $sql .= chr(10) . 'WHERE ' . $this->makeWHEREclause($searchValue, $searchType, $categoryUID);
		 $sql .= chr(10) . 'GROUP BY p.title';
		 $sql .= chr(10) . $limit;
		 //$this->debug .= t3lib_div::view_array(array("sqls" => $sql));
		 $res = mysql_query($sql);
		 if ($res) {
			 while ($row = mysql_fetch_assoc($res)){
				 $element = array();
				 // reorder it
				 foreach ($row as $key=>$value){
					 $prefix = substr($key, 0, strpos($key, '_'));
					 $key = substr($key, strpos($key, '_')+1);
					 $name = 'unknown';
					 switch ($prefix){
					 case 'a':
						 $name = 'article';
						 break;
					 case 'c':
						 $name = 'category';
						 break;
					 case 'm':
						 $name = 'manufacturer';
						 break;
					 case 'p':
						 $name = 'product';
						 break;
					 case 'pc':
						 $name = 'productscategories';
						 break;
					 case 'price':
						 $name = 'article_price';
						 break;
					 }
					 $element[$name][$key] = $value;
				 }
				 $results["data"][$row["p_uid"]] = $element;
			 }
		 }
		 return $results;
	 }
    
	/*
	 * generate SELECT clause: one for counting, one for actual data retrieval
	 */
	 function makeSELECTclause($count = false) {
		 if ($count) {
			 $sel = chr(10) . 'COUNT(DISTINCT(a.uid)) as total';
		 } else {
			 $sel .= chr(10) . 'p.uid as p_uid,';
			 $sel .= chr(10) . 'p.title as p_title,';
			 $sel .= chr(10) . 'p.description as p_description,';
			 $sel .= chr(10) . 'p.teaserimages as p_teaserimages,';
			 $sel .= chr(10) . 'p.images as p_images,';
			 $sel .= chr(10) . 'a.title as a_title,';
			 $sel .= chr(10) . 'a.subtitle as a_subtitle,';
			 $sel .= chr(10) . 'pc.uid_foreign as c_uid,';
			 $sel .= chr(10) . 'c.title as c_title,';
			 $sel .= chr(10) . 'c.navtitle as c_navtitle';
		 }
		 return $sel;
	 }
	/*
	 * generate FROM clause: same for both counting and searching
	 */
	function makeFROMclause() {
		/* products table: p */
		$from = $this->_tableProducts . ' as p';
		/* articles: a, linked to p */
		$from .= chr(10) . 'LEFT JOIN ' . $this->_tableArticles . ' as a ON p.uid = a.uid_product';
		/* product categories: pc, linked to p */
		$from .= chr(10) . 'INNER JOIN ' . $this->_tableProductsCategories . ' as pc ON pc.uid_local = p.uid';
		/* general categories: c, linked to pc */
		$from .= chr(10) . 'INNER JOIN ' . $this->_tableCategories . ' as c ON pc.uid_foreign = c.uid';
		return $from;
	}

	/*
	 * generate WHERE clause
	 * searchValue: string to search for
	 * searchType: 0 = within string, 1 = exact match, 2 = at start of string
	 * categoryUID: categories to consider
	 */
	function makeWHEREclause($searchValue, $searchType, $categoryUID) {
		/* determine category restriction */
		$categoryWhere = 'not c.hidden AND not c.deleted';
		if (is_array($categoryUID)) {
			$ids = implode(',',array_keys($categoryUID));
			$categoryWhere .= ' AND c.uid IN('.$ids.')';
		} else if (isset($categoryUID) && is_numeric($categoryUID)) {
			$categoryWhere .= ' AND c.uid = ' . $categoryUID;
		}
		/* product-only restriction */
		$prodWhere = 'not p.hidden AND not p.deleted';
		/* article-only restriction */		
		$artWhere = 'not a.hidden AND not a.deleted';
		/* search restrictions */
		$searchWhere = '';
		switch ($searchType) {
		default:
		case  0: // default search, like %word%
			$signs = array(' ', '*', '/', '\\', '-','&', '_', ',', ';');
			$searchValue = str_replace("%", "", $searchValue);
			$searchValue = str_replace("*", "", $searchValue);
			$searchValue = str_replace($signs,'%',$searchValue);
			$like = 'LIKE "%' . mysql_real_escape_string($searchValue) . '%"';
			$searchWhere = '(p.title ' . $like . ' OR a.title ' . $like . ' OR a.subtitle ' . $like . ' OR a.plain_text ' . $like . ' )';
			break;
		 case 1: // exact search
			 $like = ' = "' . mysql_real_escape_string($searchValue).'"';
			 $searchWhere = '(p.title ' . $like . ' OR a.title ' . $like . ' OR a.subtitle ' . $like . ' OR a.plain_text ' . $like . ' )';
			 break;
		 case  2: // like word%
			 $searchValue = str_replace("%", "", $searchValue);
			 $searchValue = str_replace("*", "", $searchValue);
			 $like = 'LIKE "' . mysql_real_escape_string($searchValue) . '%"';
			 $searchWhere = '(p.title ' . $like . ' )';
			 break;	        
		 }
		$where = chr(10) . $prodWhere;
		$where .= chr(10) . ' AND ' . $artWhere;
		$where .= chr(10) . ' AND ' . $categoryWhere;
		$where .= chr(10) . ' AND ' . $searchWhere;
		return $where;
	}

	 /**
		* recursively retrieves the childs of a category
		*
		* @param integer $categoryUID
		* @return array
		*/
	 function getCategoryChilds ($categoryUID) {
		 $result = array();
		 $childs = $this->getDirectChilds($categoryUID);
		 foreach ($childs as $id=>$child){
			 $result[$id] = $child;
			 $childsOfChilds = $this->getCategoryChilds($child['uid_local']);
			 // merge
			 foreach ($childsOfChilds as $id=>$cc) {	
				 $result[$id] = $cc;
			 }
		 }
		 return $result;
	 }

	 /**
		* retrieves the direct childs from the database
		*
		* @param integer $categoryUID
		* @return array
		*/
	 function getDirectChilds ($categoryUID) {
		 $where = "c_mm.uid_foreign = " . intval($categoryUID)."";
		 //$where .= " AND NOT c_l.hidden AND NOT c_l.deleted ";
		 if ($this->conf["excludeList"]){
			 $where .= ' AND c_mm.uid_local NOT IN('.$this->conf["excludeList"].') ';
		 }
		 if ($this->conf["includeList"]) {
			 $where .= ' AND c_mm.uid_local IN('.$this->conf["includeList"].') ';
		 }
		 $limit = null;
		 $sql = '
    SELECT c_l.uid, c_l.sorting, c_l.hidden, c_l.deleted,
    c_mm.uid_local, c_mm.uid_foreign,
	c_f.uid as c_f_uid, c_f.sorting
    	FROM ' . $this->_tableCategoriesParent . ' as c_mm

    	LEFT JOIN ' . $this->_tableCategories . ' as c_l ON c_l.uid = c_mm.uid_local
    	LEFT JOIN ' . $this->_tableCategories . ' as c_f ON c_f.uid = c_mm.uid_foreign
        ' . $limit . '
		WHERE '.$where.'
		GROUP BY c_l.uid
		ORDER BY c_l.sorting
    ';
		 $res = mysql_query($sql);
		 $result = array();
		 if ($res) {
			 while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
				 $result[$row["uid_local"]] = $row;
			 }
		 }
		 return $result;
	 }
	 
	 /**
		* retrives the categories from commerce
		*
		* @return array
		*/
	 function getCategories () {
		 $where = null;
		 $limit = null;
		 $groupBy = null;
		 $orderBy = null;
		 $startCategory = $this->flexConf["startCategory"] ? $this->flexConf["startCategory"] : $this->conf["startCategory"];
		 // build where clause
		 $categories = $this->getCategoryChilds($startCategory);
		 $categories[$startCategory] = $startCategory; // self Category too (as first in the list)!
		 $num = 1;
		 $length = sizeof($categories);
		 foreach ($categories as $cUID => $c_mm) {
			 if ($c_mm['deleted']==1 || $c_mm['hidden']==1) continue;
			 if ($num==1) {
				 $where .= ' uid = ' . $cUID;
			 } else{
				 $where .= ' OR uid = ' . $cUID;
			 }
			 $num++;
		 }
		 $where .= ' AND not hidden AND not deleted';
		 $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, title, navtitle', $this->_tableCategories, $where, $groupBy, $orderBy, $limit);
		 $result = array();
		 if ($res) {
			 while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				 $result[$row["uid"]]["content"] = $row["title"];
				 $result[$row["uid"]]["navtitle"] = $row["navtitle"] ? $row["navtitle"] : $row["title"];
			 }
		 }
		 return $result;
	 }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce_search/pi1/class.tx_commercesearch_pi1.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/commerce_search/pi1/class.tx_commercesearch_pi1.php']);
}
?>
