
After the installation, set the Page ID (pid) of the product list plugin 
of commerce (pi1). Just put the extension on a site of your choice and 
select a category, where the search should start.

The pagebrowser is also configurable through the flex forms.

The localization of the pagebrowser can be made through the 
_LOCAL_LANG.[your language key] directive in the typoscript configuration. 
(See below for an example)

You can also activate the plugin through typoscript:

lib.mycontent = COA
lib.mycontent{
  10 < plugin.tx_commercesearch_pi1
}

after that, there is an other subpart in the templatefile (formular_ts), so 
a different display is possible.


EXAMPLE TypoScript configuration
--------------------------------

plugin.tx_commercesearch_pi1{
 #size from teaser and images of the product
 file{
 	maxH = 40
 	maxW = 40
 }

 # this is need if you want to display the formular on other page as the plugin is placed.
 # specify where the output of the result should be 
 targetPage = 48
 
 # startcategory for searching
 # selects also the subcategories and itself
 startCategory = 2

 # LOCALIZATION
 _LOCAL_LANG.de{
    pi_list_browseresults_first = erste
    pi_list_browseresults_prev = << vorige
    pi_list_browseresults_page = Seite
    pi_list_browseresults_next = nächste >>
    pi_list_browseresults_last = letzte
    pi_list_browseresults_displays = Zeige Einträge %s bis %s von insgesamt %s
 }
}


EXAMPLE CSS Configuration
-------------------------

/*commerce search*/
.tx-commercesearch-pi1{text-align:left;}
.tx-commercesearch-pi1 a{color:black;}

.tx-commercesearch-pi1-browsebox p{}


.tx_commercesearch_pi1_letternavigation{background:url(../gfx/bg_table_header.gif); height: 22px; text-align: center;}
.tx_commercesearch_pi1_letternavigation span{padding-left: 3px; padding-right: 3px;}
.tx_commercesearch_pi1_letternavigation a{color: white; text-transform: uppercase; font-weight: bold;}


table.tx_commercesearch_pi1_result_table{
	border-collapse:collapse;
	border-spacing:0px;
	margin-bottom:8px;
	width:100%;
}

table.tx_commercesearch_pi1_result_table thead{
  background-image:url(../gfx/bg_table_header.gif);
	height: 22px;
}

table.tx_commercesearch_pi1_result_table th {color: white;}
table.tx_commercesearch_pi1_result_table th.image{width: 40px;}
table.tx_commercesearch_pi1_result_table td {}
table.tx_commercesearch_pi1_result_table tr.even {
  background-image:url(../gfx/bg_table_even.gif);
	height: 22px;
}

table.tx_commercesearch_pi1_result_table tr.odd {
  color: #6c2925 !important;
  background-image:url(../gfx/bg_table_odd.gif);
	height: 22px;
}


.tx-commercesearch-pi1{}
.tx-commercesearch-pi1 table a:link, .tx-commercesearch-pi1 table a:active, .tx-commercesearch-pi1 table a:visited {
  text-decoration: none; /*color: #602020*/ color: black;}
  
.tx-commercesearch-pi1 table a:hover {text-decoration: underline; font-weight:bold; color: black;}

.tx-commercesearch-pi1 table th{background-image: none;}
.tx-commercesearch-pi1 table.tx_commercesearch_pi1_formular_table label{}
.tx-commercesearch-pi1 table.tx_commercesearch_pi1_formular_table input{width: 140px;}
.tx-commercesearch-pi1 table.tx_commercesearch_pi1_formular_table select{width: 144px;}

#tx_commercesearch_pi1_formular_ts form{margin: 0; padding: 0;}
.tx-commercesearch-pi1 table.tx_commercesearch_pi1_formular_table_small{margin: 0px; padding:0px;}
.tx-commercesearch-pi1 table.tx_commercesearch_pi1_formular_table_small label{font-weight: normal;}
.tx-commercesearch-pi1 table.tx_commercesearch_pi1_formular_table_small input{width: 105px;}
.tx-commercesearch-pi1 table.tx_commercesearch_pi1_formular_table_small select{width: 100%;}
