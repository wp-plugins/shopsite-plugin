<?php
/**
 * @package ShopSite
 * @version 1.1
 */
/*
Plugin Name: ShopSite
Plugin URI: http://shopsite.com/
Description: ShopSite plugin to put products into your WordPress blog
Author: ShopSite
Version: 1.1
Author URI: http://shopsite.com/
*/
if (isset($_REQUEST['ss_action'])) {
  //taken from http://plugins.svn.wordpress.org/limelight-networks/trunk/limelight_popup.php
  if ( !defined('WP_LOAD_PATH') ) {
    $classic_root = dirname( dirname( dirname( dirname(__FILE__) ) ) ) . '/' ;
    if (file_exists( $classic_root . 'wp-load.php') )
      define( 'WP_LOAD_PATH' , $classic_root );
    else
      exit( 'Could not find wp-load.php' );
  }
  require_once(WP_LOAD_PATH.'wp-load.php');
  
  //echo "<script>alert('".$_REQUEST['shopsite_url']."')</script>";
  ///debug_print("Called with action ".$_REQUEST['ss_action']);
  if ($_REQUEST['ss_action'] == 'insert')
    show_search_form();
  if ($_REQUEST['ss_action'] == 'search_products')
    get_product_list();
  if ($_REQUEST['ss_action'] == 'get_data')
    get_product_data(urldecode($_REQUEST['id_list']));
  exit(0);
}

$product_list;
$wp_id;


add_action( 'wp_enqueue_scripts', 'add_scripts' );
add_action( 'wp_head', 'init_product_list' );
add_action( 'wp_footer', 'dispatch_product_list' );
add_action('admin_menu', 'add_shopsite_menu');
add_action('init', 'shopsite_addbuttons');
add_shortcode( 'product', 'product_handler' );

function add_shopsite_menu() {
	add_submenu_page( "options-general.php", "ShopSite", "ShopSite", "manage_options", "shopsite_menu", "show_shopsite_menu" );
}

function show_shopsite_menu() {
	global $wpdb;

	add_option('shopsite_url');
  add_option('clientid');
  add_option('secretkey');
  add_option('code');
  add_option('authorizationurl');
  
  add_option('identifier');
  
  add_option('remember_search');
  add_option('remembered_search_string');
  add_option('remembered_search_on');
	
	if (isset($_REQUEST['shopsite_url'])) update_option('shopsite_url', $_REQUEST['shopsite_url']);
  if (isset($_REQUEST['clientid'])) update_option('clientid', $_REQUEST['clientid']);
  if (isset($_REQUEST['secretkey'])) update_option('secretkey', $_REQUEST['secretkey']);
  if (isset($_REQUEST['code'])) update_option('code', $_REQUEST['code']);
  if (isset($_REQUEST['authorizationurl'])) update_option('authorizationurl', $_REQUEST['authorizationurl']);
  
  if (isset($_REQUEST['identifier'])) update_option('identifier', $_REQUEST['identifier']);
	
	$shopsite_url = get_option('shopsite_url');
  $clientid = get_option('clientid');
  $secretkey = get_option('secretkey');
  $code = get_option('code');
  $authorizationurl = get_option('authorizationurl');
  
  $identifier = get_option('identifier');
  
  $SKU_selected = $GUID_selected = "";
  if ($identifier == 'SKU')
    $SKU_selected = "checked";
  else
    $GUID_selected = "checked";

	echo 	"<h1>ShopSite configuration</h1>
		<form method=post ss_action=plugins.php?page=shopsite_menu >
    <table>
    <tr><th colspan=2>Application settings</th></tr>
    <tr><td>Client ID:</td><td><input type=text name=clientid value='$clientid' size=100></td></tr>
    <tr><td>Secret Key for Signing:</td><td><input type=text name=secretkey value='$secretkey' size=100></td></tr>
    <tr><td>Authorization Code:</td><td><input type=text name=code value='$code' size=100></td></tr>
    <tr><td>Authorization URL:</td><td><input type=text name=authorizationurl value='$authorizationurl' size=100></td></tr>
    <tr><th colspan=2>Other settings</th></tr>
    <tr><td>ShopSite callback URL:</td><td><input type=text name=shopsite_url value='$shopsite_url' size=100></td></tr>
    <tr><td>Unique product identifier:</td>
    <td>
    <input type=radio name=identifier value='GUID' $GUID_selected/>Global unique ID<br/>
    <input type=radio name=identifier value='SKU' $SKU_selected/>SKU</td></tr>
    </table>
		<br/><input type=submit value='Save settings'></form>";
}








function shopsite_addbuttons() {
   // Don't bother doing this stuff if the current user lacks permissions
   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
     return;
 
   // Add only in Rich Editor mode
   if ( get_user_option('rich_editing') == 'true') {
     add_filter("mce_external_plugins", "add_shopsite_tinymce_plugin");
     add_filter('mce_buttons', 'register_shopsite_button');
   }
}
 
function register_shopsite_button($buttons) {
   array_push($buttons, "separator", "shopsite");
   return $buttons;
}
 
// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_shopsite_tinymce_plugin($plugin_array) {
  
  /*var shopsite_url='".get_option('shopsite_url')."'; 
  var includes_url='".includes_url()."';
  var clientid='".get_option('clientid')."'; 
  var secretkey='".get_option('secretkey')."'; 
  var code='".get_option('code')."'; 
  var authorizationurl='".get_option('authorizationurl')."'; */
  
  /*  var includes_path='".addslashes(ABSPATH.WPINC)."';
  var includes_url='".includes_url()."';
  var wp_path='$path';
  alert('$path');
*/
  echo 
  "<script>
  var path='".plugin_dir_url(__FILE__)."'; 
  </script>";
  
  $path = plugin_dir_url(__FILE__).'editor_plugin.js';
  $plugin_array['shopsite'] = $path;
  return $plugin_array;
}
 





function show_search_form() {

  $tinymce_url = includes_url()."/js/tinymce/tiny_mce_popup.js";
  $jquery_url = includes_url()."/js/jquery/jquery.js";
  
  $search_string = $default_search_string = "I am looking for...";
  
  $remember_search = "";
  $selected_name = "";
  $selected_sku = "";
  if (get_option('remember_search') == 'true') {
      $remember_search = "checked";
      
    if (strlen(get_option('remembered_search_string')))
      $search_string = get_option('remembered_search_string');
      
    if (strlen(get_option('remembered_search_on'))) {
      $search_on = get_option('remembered_search_on');
      if ($search_on == 'name')
        $selected_name = "selected";
      if ($search_on == 'sku')
        $selected_sku = "selected";
    }
  }
  
  echo "
  <html>
  <head>
  <script type='text/javascript' src='".$jquery_url."'></script>
  <script type='text/javascript' src='".$tinymce_url."'></script>
  <script type='text/javascript' src='".plugin_dir_url(__FILE__)."search_products.js?".time()."'></script>
  <script type='text/javascript'> var default_search_string = '".$default_search_string."';</script>
  <style type='text/css'>
  #search_string {
    width: 100%;
  }
  #search_form, #search_results {
    float: left;
  }
  #search_results {
    padding-left: 20px;
  }
  #please_wait, #no_products {
    margin-top: 50px;
    
  }
  </style>
  </head>
  <body>";
  
  echo "
  <table id=search_form>
  <tr>
  <td>Search products by:</td>
  <td><select id=search_on name=search_on>
  <option value=name $selected_name>Name</option>
  <option value=sku $selected_sku>SKU</option>
  </select></td>
  </tr>
  
  <tr>
  <td colspan=2><input id=search_string name=search_string type=text value ='$search_string'></td>
  </tr>
  
  <tr>
  <td>Remember search</td>
  <td><input type=checkbox $remember_search name=remember_search id=remember_search></td>
  </tr>
  
  <tr>
  <td colspan=2><input type=button value='Search' id=search_button></td>
  </tr>
  
  </table>";
  
  echo "<div id=search_results></div>";
  
  echo "
  </body>
  </html>";
}



function get_product_list() {
  include_once "oauth.php";
  $search_string = $_REQUEST['search_string'];
  $search_on = $_REQUEST['search_on'];
  
  $remember_search = $_REQUEST['remember_search'];
  
  ///debug_print("Updating remember_search to $remember_search");
  update_option('remember_search', $remember_search);
  
  if ($remember_search == 'true') {
    update_option('remembered_search_string', $search_string);
    update_option('remembered_search_on', $search_on);
  }

  
  //if ($url == "")
    $shopsite_url = get_option('shopsite_url');
  /*else
    $shopsite_url = $url;*/
  //echo $shopsite_url;
	
  //$handle = fopen($shopsite_url."&operation=get_product_list",'rb');
	//$contents = stream_get_contents($handle);
  
  
  $products_xml = oauth(
    get_option('clientid'), get_option('secretkey'), get_option('code'), get_option('authorizationurl'), 
    DOWNLOAD, 
    array('clientApp'=>'1', 'dbname'=>'products', 'version'=>'11.2', 'fields'=>'|Product GUID|Name|SKU|', 'search_term'=>$search_string, 'search_on'=>$search_on, 'search_filter'=>'contains', 'limit'=>1000)
  );
  
  if (!$products_xml['success']) {
    echo $products_xml['error'];
    exit(1);
  }
  
  //debug_print($products_xml['data']);
  
  /*echo $products_xml['data'];
  exit(0);
  
	$product_ar = explode("\6", $contents);*/
  
  $tinymce_url = includes_url()."/js/tinymce/tiny_mce_popup.js";
  $jquery_url = includes_url()."/js/jquery/jquery.js";

  /*echo "<html>";
  echo "<head>";
  echo "<script language=\"javascript\" type=\"text/javascript\" src=\"$tinymce_url\"></script>";
  //echo "<script language=\"javascript\" type=\"text/javascript\" src=\"$jquery_url\"></script>";
  echo "</head>";
    
  
  echo "<body>";*/
  
  
  
  $products = new SimpleXMLElement($products_xml['data']);
  $products_ar = array();
  
  
  foreach ($products->Products->Product as $product) {
    $products_ar[addslashes($product->Name)] = array($product->ProductGUID, $product->SKU);
  }
    
  if (count($products_ar) > 0) 
  {  
    ksort($products_ar);
    
    
    //debug_print("products_ar: ".print_r($products_ar, true));
    
    echo "Select a product:";
    echo "<form>";
    echo "<select id=product name=product size=20>";
    
    foreach ($products_ar as $Name => $ids) {
      $Name = stripslashes($Name);
      $GUID = $ids[0];
      $SKU = $ids[1];
      echo "<option value=\"";
      if (strlen($GUID) > 0)
        echo $GUID;
      echo "|";
      if (strlen($SKU) > 0)
        echo $SKU;
      echo "\">$Name";
      echo "</option>\n";
    }
    
    echo "</select>";
    echo "</form>";
    echo "<br/>";
    
    /*echo "
      <script language=\"javascript\" type=\"text/javascript\">
      $('#product').select(function() {
        var id = $('#product option:selected').val();
        tinyMCEPopup.execCommand('mceInsertContent', false, '[product id=$id]'); 
        tinyMCEPopup.close();
      });
      </script>
    ";*/
    
    echo "
      <script language=\"javascript\" type=\"text/javascript\">
      function insert_product() {
        var p_id_sku = document.forms[0].product.value;
        var pair = p_id_sku.split('|');
        var p_id = pair[0];
        var sku = pair[1];
        var p_index = document.forms[0].product.selectedIndex;
        var p_name = document.forms[0].product[p_index].innerHTML;
        var p_id_string = '';
        if (p_id.length > 0)
          p_id_string = 'id='+p_id;
        var sku_string = '';
        if (sku.length > 0)
          sku_string = 'sku='+sku;
        var shortcode = '<p>[product '+p_id_string+' '+sku_string+']'+p_name+'[/product]</p>';
        
        tinyMCEPopup.execCommand('mceInsertContent', false, shortcode); 
      }
      </script>
    ";
    
    echo "<a href=# onclick=\"insert_product();\">Insert product</a>";
    echo "<br/><a href=# onclick=\"tinyMCEPopup.close();\">Close this popup</a>";
  } else {
    echo "<div id=no_products>No matching products.</div>";
  }
  
  
  
  
  //echo "</form>";
  /*
  echo "</body>";
  
  echo"</html>";*/
  
}


function product_handler( $atts, $content=null, $code="" ) {
  global $product_list, $wp_id;
  ///debug_print("product_handler entered");
	//return "<iframe src='$content' width='100%' height='300px' ><p>IFRAME FAIL!</p></iframe>";
 
  extract( shortcode_atts( array(
		'id' => '',
    'sku'=>''
	), $atts ) );
  
  if (get_option('identifier') == 'SKU')
    $identifier = $sku;
  else
    $identifier = $id;
  
  if ($identifier == '')
    return "";
    
    
  /*$shopsite_url = get_option('shopsite_url');    
  $handle = fopen($shopsite_url."&operation=get_product&id=$id",'r');
	$contents = stream_get_contents($handle);*/
  
  $wp_id++;
  
    
  if (!isset($product_list[$identifier])) {
    ///debug_print("sticking stuff into product_list for $identifier");
    $product_list[$identifier] = array();
  }
  ///debug_print("sticking stuff into product_list 2");
  array_push($product_list[$identifier], $wp_id);
  
  //$product_list["wp".$wp_id] = $id;
  return "<div class=ss_product id=product_".$wp_id."></div>";
  
  //return $contents;
}

function add_scripts() {
  wp_enqueue_script("jquery");
}

function init_product_list() {
  global $product_list;
  
  echo "<script type='text/javascript' src=".plugin_dir_url(__FILE__)."populate_products.js></script>";
  $product_list = array();
  $wp_id = 0;
}

function dispatch_product_list() {
  global $product_list;
  ///debug_print("Xi2");
  ///debug_print("product_list:".print_r($product_list, true));
  $product_map = json_encode($product_list);
  ///debug_print("product_map:".print_r($product_map, true));
  $identifier = get_option('identifier');
  $id_list = implode(",",array_unique(array_keys($product_list))); 
  
  
  
  echo 
    "<script> var path='".plugin_dir_url(__FILE__)."'; 
    var identifier = \"$identifier\"; 
    var product_map = $product_map; 
    var id_list = \"$id_list\"; 
    var shopsite_url=\"".get_option('shopsite_url')."\";</script>";
  //echo "<script> var product_mapping = ".json_encode($product_list).";</script>";
}

function get_product_data($id_list) {
  $clientid = get_option('clientid');
  $secretkey = get_option('secretkey');
  $data = "$clientid:$id_list";
  $hmachash = hash_hmac("sha1", $data, $secretkey, true);
  $signature = rawurlencode(base64_encode($hmachash));
  
  $identifier = get_option('identifier');
  
  //$shopsite_url = $_REQUEST['shopsite_url'];   
  $shopsite_url = get_option('shopsite_url');
  //$id_list = "\"".str_replace(",","\",\"",$id_list)."\"";
  $url = $shopsite_url."&operation=get_products&".$identifier."_list=".$id_list."&signature=".$signature;
  ///debug_print($url);
  $url_openable = ini_get('allow_url_fopen');
  ini_set('allow_url_fopen', true);
  $handle = fopen($url,'r');
  ini_set('allow_url_fopen', $url_openable);
	print(stream_get_contents($handle));
}

function debug_print($text) {
  file_put_contents("log.txt", $text."\n", FILE_APPEND);
}

?>