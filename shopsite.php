<?php
/**
 * @package ShopSite
 * @version 1.4
 */
/*
Plugin Name: ShopSite
Plugin URI: http://shopsite.com/
Description: ShopSite plugin to put products into your WordPress blog
Author: ShopSite
Version: 1.4
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
    get_product_data(($_REQUEST['id_list']));
  exit(0);
}



$product_list;
$wp_id;

register_activation_hook( __FILE__, 'on_activate' );

function on_activate() {
  add_option('Activated_Plugin','Plugin-Slug');
}

function load_plugin() {
    if ( is_admin() && get_option( 'Activated_Plugin' ) == 'Plugin-Slug' ) {
        delete_option( 'Activated_Plugin' );
        add_action( 'admin_head', 'start_tutorial');
    }
}

function start_tutorial() {
  echo "<script>start_tutorial();</script>";  
}

/*function link_jquery() {
  $jquery_url = includes_url()."js/jquery/jquery.js";
  //echo "<script type='text/javascript' src='".$jquery_url."'></script>";
  echo "<script> 
    if (typeof $ !== \"undefined\")  jQuery = $; 
    if (typeof jQuery !== \"undefined\")  $ = jQuery; 
    </script>";
}*/

function link_tutorial() {
  echo "<script> $ = jQuery;</script>";
  echo "<script type='text/javascript' src=".plugin_dir_url(__FILE__)."jquery.tutorial.js></script>";
  echo "<script type='text/javascript' src=".plugin_dir_url(__FILE__)."tutorial_driver.js></script>";
  echo "<link rel='stylesheet' href=".plugin_dir_url(__FILE__)."shopsite.css type='text/css' />";
  
}


//add_action( 'admin_enqueue_scripts', 'link_jquery' );
add_action( 'admin_head', 'link_tutorial' );
add_action( 'admin_init', 'load_plugin' );
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
  include_once "oauth.php";
  $testing = false;
  $state = 'new_config';
  add_option('config_dump');
  
  add_option('config_type');

	add_option('shopsite_url');
  add_option('clientid');
  add_option('secretkey');
  add_option('code');
  add_option('authorizationurl');
  
  add_option('identifier');
  
  add_option('remember_search');
  add_option('remembered_search_string');
  add_option('remembered_search_on');
  
  if (isset($_REQUEST['config_type'])) {
  
    $config_type = trim($_REQUEST['config_type']);
    //echo "<script>alert('$config_type');</script>";

    update_option('config_type', $config_type);
    $state = 'settings_saved';
    
    if ($config_type == 'ss_12') {
      update_option('config_dump', trim($_REQUEST['config_dump']));
      
      
      $decoded = base64_decode(trim($_REQUEST['config_dump']));
      $decoded = explode('^',$decoded);
      
      update_option('clientid', trim($decoded[0]));
      update_option('secretkey', trim($decoded[1]));
      update_option('code', trim($decoded[2]));
      update_option('authorizationurl', trim($decoded[3]));
      update_option('shopsite_url', trim($decoded[4]));
    } else {
      
      $clientid = trim($_REQUEST['clientid']); update_option('clientid', $clientid);
      $secretkey = trim($_REQUEST['secretkey']); update_option('secretkey', $secretkey);
      $code = trim($_REQUEST['code']); update_option('code', $code);
      $authorizationurl = trim($_REQUEST['authorizationurl']); update_option('authorizationurl', $authorizationurl);
      $shopsite_url = trim($_REQUEST['shopsite_url']); update_option('shopsite_url', $shopsite_url);
      $config_dump = base64_encode("$clientid^$secretkey^$code^$authorizationurl^$shopsite_url");
      update_option('config_dump', $config_dump);
    }
  }
	
	/*if (isset($_REQUEST['shopsite_url'])) { update_option('shopsite_url', trim($_REQUEST['shopsite_url'])); $state = 'settings_saved';}
  if (isset($_REQUEST['clientid'])) update_option('clientid', trim($_REQUEST['clientid']));
  if (isset($_REQUEST['secretkey'])) update_option('secretkey', trim($_REQUEST['secretkey']));
  if (isset($_REQUEST['code'])) update_option('code', trim($_REQUEST['code']));
  if (isset($_REQUEST['authorizationurl'])) update_option('authorizationurl', trim($_REQUEST['authorizationurl']));*/
  
  if (isset($_REQUEST['identifier'])) update_option('identifier', trim($_REQUEST['identifier']));
  if (isset($_REQUEST['test'])) {
    $testing = true;
    $test_result = test_connection();
    $state = 'testing_completed';
  }
  
  $config_type = get_option('config_type');
  if (strlen($config_type) == 0)
    $config_type = 'ss_12';
	$config_dump = get_option('config_dump');
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
    
  $ss_12_extra = "";
  $ss_11_extra = "";
  if ($config_type == 'ss_11') {
    $ss_12_extra = " style='display:none;'";
    $ss_11_extra = " style='display:table-row-group;'";
  }

//ss_action=plugins.php?page=shopsite_menu
  echo 
    "<script>
    
    \$('#ss_11').live('click', function() {\$('#config_type').val('ss_11'); \$('#ss_12_settings').css({'display':'none'}); \$('#ss_11_settings').css({'display':'table-row-group'}); });
    \$('#ss_12').live('click', function() {\$('#config_type').val('ss_12'); \$('#ss_11_settings').css({'display':'none'}); \$('#ss_12_settings').css({'display':'table-row-group'}); });
    </script>";

	echo 	
    "<h1>ShopSite configuration</h1>
    Don't have a ShopSite store? <a id=get_shopsite target=_blank href='http://saas.shopsite.com/express/'>Get a free 10-product Express store</a>.
		<form method=post>
    <input type=hidden id=config_type name=config_type value=$config_type>
    <table>
    <thead><tr><th colspan=2>Application settings</th></tr></thead>";
  
  echo
    "<tbody id='ss_12_settings' $ss_12_extra><tr><td>Configuration data (paste from ShopSite)
    <br><a id=ss_11>Click here if you have 5 fields to copy and paste in your ShopSite backoffice WordPress config</a></td>
    <td><textarea name=config_dump id=config_dump style='height:100px;width:675px;'>$config_dump</textarea></td></tr></tbody>";
  
    
  echo
    "<tbody id='ss_11_settings' $ss_11_extra>
    <tr><td colspan=2><a id=ss_12>Click here if you only have 1 field to paste in your ShopSite backoffice WordPress config</a></td></tr>
    <tr><td>Client ID:</td><td><input type=text name=clientid id=clientid value='$clientid' size=100></td></tr>
    <tr><td>Secret Key for Signing:</td><td><input type=text name=secretkey id=secretkey value='$secretkey' size=100></td></tr>
    <tr><td>Authorization Code:</td><td><input type=text name=code id=code value='$code' size=100></td></tr>
    <tr><td>Authorization URL:</td><td><input type=text name=authorizationurl id=authorizationurl value='$authorizationurl' size=100></td></tr>
    <tr><td>ShopSite callback URL:</td><td><input type=text name=shopsite_url value='$shopsite_url' size=100></td></tr>
    </tbody>
    ";
    

  
  /*echo "<tr><th colspan=2>Other settings</th></tr>*/
    
    
  echo "<tbody><tr><th colspan=2>Other settings</th></tr>
    <tr><td>Unique product identifier:</td>
    <td>
    <input type=radio name=identifier value='GUID' $GUID_selected/>Global unique ID<br/>
    <input type=radio name=identifier value='SKU' $SKU_selected/>SKU</td></tr>
    </tbody></table>
    <br/><input type=submit name=test id=test_connection value='Test connection'>";
  
  if ($testing) {
    echo "<div id=test_result>";
    if ($test_result['success'] == true)
      echo "<p id=\"test_good\">Connection test successful</p>";
    if ($test_result['success'] == false) {
      echo "<p id=\"test_bad\">Connection test failed, check your settings.<br>Error: ".$test_result["error"]."</p>";
    }
    echo "</div>";
    
  }
  
  echo "<input type=hidden name=state id=state value=$state>";
  echo "<br/><input type=submit id=save_settings value='Save settings'></form>";
}

/*onclick='window.open(\"".plugin_dir_url(__FILE__)."shopsite.php?ss_action=test&clientid=\"+document.forms[0].clientid.value+\"&secretkey=\"+document.forms[0].secretkey.value
      +\"&code=\"+document.forms[0].code.value+\"&authorizationurl=\"+document.forms[0].authorizationurl.value
      ,\"\",\"width=400,height=300\");' */
//document.forms[0].clientid.value





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
  <script type='text/javascript' src='".$jquery_url."'></script>";
  link_tutorial();
  echo"<script type='text/javascript' src='".$tinymce_url."'></script>
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
  //echo ("in:|".$_REQUEST['search_string']."|<br>");
  $search_string = stripslashes(trim($_REQUEST['search_string']));
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
  $search_array = array('search_on'=>$search_on, 'search_term'=>$search_string, 'search_filter'=>'contains');
  if ($search_string == '*')
    $search_array = array();
    
  //echo("ss:|$search_string|");
  
  $products_xml = oauth(
    get_option('clientid'), get_option('secretkey'), get_option('code'), get_option('authorizationurl'), 
    DOWNLOAD, 
    array_merge(array('clientApp'=>'1', 'dbname'=>'products', 'version'=>'11.2', 'fields'=>'|Product GUID|Name|SKU|', 'limit'=>1000), $search_array)
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
  
  
  
    
  if (count($products->Products->Product) > 0) 
  {  
  
    foreach ($products->Products->Product as $product) {
      $products_ar[addslashes($product->Name)] = array($product->ProductGUID, $product->SKU);
    }
  
    ksort($products_ar);
    
    
    //debug_print("products_ar: ".print_r($products_ar, true));
    
    echo "Select a product:";
    echo "<form>";
    echo "<select id=product name=product size=20>";
    
    foreach ($products_ar as $Name => $ids) {
      $Name = stripslashes($Name);
      $GUID = $ids[0];
      $SKU = rawurlencode($ids[1]);
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
          sku_string = 'sku=\''+sku+'\'';
        var shortcode = '<p>[product '+p_id_string+' '+sku_string+']'+p_name+'[/product]</p>';
        
        tinyMCEPopup.execCommand('mceInsertContent', false, shortcode); 
      }
      </script>
    ";
    
    echo "<a id=\"insert_product\" href=# onclick=\"insert_product();\">Insert product</a>";
    echo "<br/><a id=\"close_popup\" href=# onclick=\"tinyMCEPopup.close();\">Close this popup</a>";
    

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
  //link_tutorial();
  wp_enqueue_script("jquery");
  //echo "<script>alert('wp_enq');</script>";
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
  
  //debug_print("dispatched:|$id_list|");
  
  
  echo 
    "<script> var path='".plugin_dir_url(__FILE__)."'; 
    var identifier = \"$identifier\"; 
    var product_map = $product_map; 
    var id_list = \"$id_list\"; 
    var shopsite_url=\"".get_option('shopsite_url')."\";</script>";
  //echo "<script> var product_mapping = ".json_encode($product_list).";</script>";
}

function get_product_data($id_list) {
  $id_list = stripslashes($id_list);
  //debug_print("GPD |$id_list|");
  
  $clientid = get_option('clientid');
  $secretkey = get_option('secretkey');
  $ids = explode(",", $id_list);
  $decoded_id_list = array();
  foreach ($ids as $id) {
    array_push($decoded_id_list, urldecode($id));
  }
  $data = "$clientid:".implode(",",$decoded_id_list);
  $hmachash = hash_hmac("sha1", $data, $secretkey, true);
  $signature = rawurlencode(base64_encode($hmachash));
  
  $identifier = get_option('identifier');
  
  //$shopsite_url = $_REQUEST['shopsite_url'];   
  $shopsite_url = get_option('shopsite_url');
  //$id_list = "\"".str_replace(",","\",\"",$id_list)."\"";
  $url = $shopsite_url."&operation=get_products&".$identifier."_list=".$id_list."&signature=".$signature;
  //debug_print("DATA URL |$url|");
  /*$url_openable = ini_get('allow_url_fopen');
  ini_set('allow_url_fopen', true);
  $handle = fopen($url,'r');
  ini_set('allow_url_fopen', $url_openable);
	print(stream_get_contents($handle));*/
  print(curl_open($url));
}

function test_connection() {

  if  (!in_array  ('curl', get_loaded_extensions())) {
    return array("success"=>false, "error"=>"CURL PHP extension is not installed on your server. Contact your hosting provider.");
  }

  $test_download_xml = oauth(
    get_option('clientid'), get_option('secretkey'), get_option('code'), get_option('authorizationurl'), 
    DOWNLOAD, 
    array('clientApp'=>'1', 'dbname'=>'products', 'version'=>'11.2', 'fields'=>'|Product GUID|Name|SKU|', 'search_term'=>"B0gu5", 'search_on'=>'name', 'search_filter'=>'contains', 'limit'=>1)
  );
  if (!$test_download_xml["success"])
    return $test_download_xml;
    
  
    
  /*$shopsite_url = get_option('shopsite_url');
  $url_openable = ini_get('allow_url_fopen');
  ini_set('allow_url_fopen', true);
  $url = $shopsite_url;
  $handle = fopen($url,'r');
  ini_set('allow_url_fopen', $url_openable);
  
  if ($handle == false)
    return array("success"=>false, "error"=>"Check your callback URL");*/
  
  if (curl_open(get_option('shopsite_url')) == false)
    return array("success"=>false, "error"=>"Check your callback URL");
  
  return array("success"=>true); 
}

function debug_print($text) {
  file_put_contents("log.txt", $text."\n", FILE_APPEND);
}

function curl_open($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
  $html = curl_exec($ch);
  curl_close($ch);
      
  return $html;
}

?>