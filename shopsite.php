<?php
/**
 * @package ShopSite
 * @version 1.5.3
 */
/*
Plugin Name: ShopSite
Plugin URI: http://shopsite.com/
Description: ShopSite plugin to put products into your WordPress blog
Author: ShopSite
Version: 1.5.3
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


register_uninstall_hook(__FILE__, 'on_uninstall');

register_activation_hook( __FILE__, 'on_activate' );
function on_activate() {
  add_option('ss_just_activated','yes');
}

function on_uninstall() {
  $option_list = array('shopsite_url', 'config_dump', 'config_type', 'clientid', 'secretkey', 'code', 'authorizationurl', 'identifier', 'remember_search', 'remembered_search_string', 'media_url', 'version');
  foreach ($option_list as $option) {
    delete_option('ss_'.$option);
  }
}

function load_plugin() {
    $version = "1.5.3";
    if ( is_admin() ) {  
      $running_version = get_option('ss_version');
      if (!$running_version) 
        $running_version = "1";
        
      update_option('ss_version', $version);
      if ($running_version < "1.5.1") {
        
        //updating, rename to ss_ options to prevent conflicts with other plugins
        {
          $option_list = array('shopsite_url', 'config_dump', 'config_type', 'clientid', 'secretkey', 'code', 'authorizationurl', 'identifier', 'remember_search', 'remembered_search_string', 'media_url');
          foreach ($option_list as $option) {
            $option_val = get_option($option);
            if ($option_val) {
              update_option('ss_'.$option, $option_val);
              delete_option($option);
            } else
              add_option('ss_'+$option);
          }
        }
      }
      
      if (get_option('ss_just_activated')) {
        delete_option( 'ss_just_activated' );
        $shopsite_url = get_option('ss_shopsite_url');
        if ($shopsite_url == false || strlen($shopsite_url) < 10) {
          add_action( 'admin_head', 'start_tutorial');
        }
      }

      
    }
}

function start_tutorial() {
  echo "<script>start_tutorial();</script>";  
}

function request_output_url() {
  echo "<script>request_output_url();</script>";  
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
add_shortcode( 'ss_product', 'product_handler' );

function add_shopsite_menu() {
	add_submenu_page( "options-general.php", "ShopSite", "ShopSite", "manage_options", "shopsite_menu", "show_shopsite_menu" );
}

function show_shopsite_menu() {
	global $wpdb;
  include_once "oauth.php";
  $testing = false;
  $state = 'new_config';
  
  /*$option_list = array('shopsite_url', 'config_dump', 'config_type', 'clientid', 'secretkey', 'code', 'authorizationurl', 'identifier', 'remember_search', 'remembered_search_string', 'media_url');
  foreach ($option_list as $option) {
    echo "<br>$option  |".get_option($option)."| |".get_option('ss_'.$option)."|";
  }*/
  
  
  if (isset($_REQUEST['config_type'])) {
  
    $config_type = trim($_REQUEST['config_type']);
    //echo "<script>alert('$config_type');</script>";

    update_option('ss_config_type', $config_type);
    $state = 'settings_saved';
    
    if ($config_type == 'ss_12') {
      update_option('ss_config_dump', trim($_REQUEST['config_dump']));
      
      
      $decoded = base64_decode(trim($_REQUEST['config_dump']));
      $decoded = explode('^',$decoded);
      
      update_option('ss_clientid', trim($decoded[0]));
      update_option('ss_secretkey', trim($decoded[1]));
      update_option('ss_code', trim($decoded[2]));
      update_option('ss_authorizationurl', trim($decoded[3]));
      update_option('ss_shopsite_url', trim($decoded[4]));
    } else {
      
      $clientid = trim($_REQUEST['clientid']); update_option('ss_clientid', $clientid);
      $secretkey = trim($_REQUEST['secretkey']); update_option('ss_secretkey', $secretkey);
      $code = trim($_REQUEST['code']); update_option('ss_code', $code);
      $authorizationurl = trim($_REQUEST['authorizationurl']); update_option('ss_authorizationurl', $authorizationurl);
      $shopsite_url = trim($_REQUEST['shopsite_url']); update_option('ss_shopsite_url', $shopsite_url);
      $config_dump = base64_encode("$clientid^$secretkey^$code^$authorizationurl^$shopsite_url");
      update_option('ss_config_dump', $config_dump);
    }
  }
	
	/*if (isset($_REQUEST['shopsite_url'])) { update_option('shopsite_url', trim($_REQUEST['shopsite_url'])); $state = 'settings_saved';}
  if (isset($_REQUEST['clientid'])) update_option('clientid', trim($_REQUEST['clientid']));
  if (isset($_REQUEST['secretkey'])) update_option('secretkey', trim($_REQUEST['secretkey']));
  if (isset($_REQUEST['code'])) update_option('code', trim($_REQUEST['code']));
  if (isset($_REQUEST['authorizationurl'])) update_option('authorizationurl', trim($_REQUEST['authorizationurl']));*/
  
  if (isset($_REQUEST['identifier'])) update_option('ss_identifier', trim($_REQUEST['identifier']));
  if (isset($_REQUEST['test'])) {
    $testing = true;
    $test_result = test_connection();
    $state = 'testing_completed';
  }
  
  $config_type = get_option('ss_config_type');
  if (strlen($config_type) == 0)
    $config_type = 'ss_12';
	$config_dump = get_option('ss_config_dump');
	$shopsite_url = get_option('ss_shopsite_url');
  $clientid = get_option('ss_clientid');
  $secretkey = get_option('ss_secretkey');
  $code = get_option('ss_code');
  $authorizationurl = get_option('ss_authorizationurl');
  
  
  $identifier = get_option('ss_identifier');
  
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
    <tr><td>ShopSite callback URL:</td><td><input type=text name=shopsite_url value='$shopsite_url' size=100></td></tr>";
    
  /*echo
    "<tr id=store_url_instructions><td colspan=2>You can find Store URL under 'Preferences'->'Hosting Service'</td></tr>
    <tr><td>Store URL:</td><td><input type=text name=store_url value='$store_url' size=100></td></tr>";*/
    
  echo
    "</tbody>";
    //http://eval.shopsite.com/cgi-bin/ilya/ss/preview.cgi?&storeid=*0c5125a8823496a6&image_preview=1&page=2

  
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
  echo 
  "<script>
  var ss_path='".plugin_dir_url(__FILE__)."'; 
  </script>";
  
  $path = plugin_dir_url(__FILE__).'editor_plugin.js';
  $plugin_array['shopsite'] = $path;
  return $plugin_array;
}
 





function show_search_form() {

  $tinymce_url = includes_url()."js/tinymce/tiny_mce_popup.js";
  $jquery_url = includes_url()."js/jquery/jquery.js";
  
  $search_string = "*";
  
  $remember_search = "";
  $selected_name = "";
  $selected_sku = "";
  if (get_option('ss_remember_search') == 'true') {
      $remember_search = "checked";
      
    if (strlen(get_option('ss_remembered_search_string')))
      $search_string = get_option('ss_remembered_search_string');
    
  }
  
  echo "
  <html>
  <head>
  <script type='text/javascript' src='".$jquery_url."'></script>\n";
  link_tutorial();
  echo"<script type='text/javascript' src='".$tinymce_url."'></script>\n
  <script type='text/javascript' src='".plugin_dir_url(__FILE__)."search_products.js?".time()."'></script>
  <script type='text/javascript'> var ss_remembered_search_string = '".$search_string."';</script>
  
  
  </head>
  <body>";
  
  
  
  echo "<div id=top_bar>";
  echo "<div id=message>You can use CTRL and SHIFT keys to select multiple products.</div>";  
  echo "<div id=tabs>";
  echo "<div class='tab selected_tab' id=list_all>List all products</div><div class=tab id=search>Search</div>";
  echo "</div>";
  echo "</div>";
  echo "<div id=search_results></div>";
  
  $extra_space = "";
  $message = "";
  if (!get_option('ss_media_url')) 
  {
    $extra_space = " style='height:80px;'";
    $message = "<div id=extra_message>Upgrade your store to ShopSite v12 sp1 or greater to see product images above.</div>";
  }
  
  echo "<div id=\"bottom_fix\"$extra_space>$message<div id=\"insert_button\">Insert selected products into post</div></div>";
  
  echo "
  </body>
  </html>";
}



function get_product_list() {
  include_once "oauth.php";
  $limit = 1000;
  //echo ("in:|".$_REQUEST['search_string']."|<br>");
  $search_string = stripslashes(trim($_REQUEST['search_string']));
  
  
  $remember_search = $_REQUEST['remember_search'];
  
  ///debug_print("Updating remember_search to $remember_search");
  update_option('ss_remember_search', $remember_search);
  
  if ($remember_search == 'true') {
    update_option('ss_remembered_search_string', $search_string);
  }

  
  $shopsite_url = get_option('ss_shopsite_url');

  
  $media_url = false;//get_option('media_url');
  if (!$media_url) {
    $shopsite_url = get_option('ss_shopsite_url');
    $url = $shopsite_url."&operation=get_setting&setting=output_url";  
    $curl_res = curl_open($url);
    $outputurl = $curl_res[0];
    if ($outputurl && strlen($outputurl) > 10) {
      $media_url = $outputurl."/media/";
      update_option('ss_media_url', $media_url);
    }
  }
  
  
  
  $list_all = false;
  if ($search_string == '*') {
    $search_array = array();
    $list_all = true;
  }
   
  
  if (!$list_all) {
    if ($media_url)
      $search_array = array('search_on'=>"name,sku", 'search_term'=>$search_string, 'search_filter'=>'contains');
    else
      $search_array = array('search_on'=>"name", 'search_term'=>$search_string, 'search_filter'=>'contains');
  }
    
  $products_xml = oauth(
    get_option('ss_clientid'), get_option('ss_secretkey'), get_option('ss_code'), get_option('ss_authorizationurl'), 
    DOWNLOAD, 
    array_merge(array('clientApp'=>'1', 'dbname'=>'products', 'version'=>'11.2', 'fields'=>'|Product GUID|Name|SKU|Graphic|', 'limit'=>$limit), $search_array)
  );
  
  //debug_print(print_r($products_xml,true));
  
  if (!$products_xml['success']) {
    echo "<div id=error_head>Unfortunately, something went wrong.</div>";
    echo "<div id=error_message>Detailed error message: <br>".$products_xml['error']."</div>";
    exit(1);
  }
  $products_ar = array();
  
  $products = new SimpleXMLElement($products_xml['data']);
  if (count($products->Products->Product) > 0) {
    foreach ($products->Products->Product as $product) {
      $products_ar[addslashes($product->Name)] = array($product->ProductGUID, $product->SKU, $product->Graphic);
    }
  }
  
  //if media_url is absent, that means shopsite's XML API can't handle searches on multiple fields at once. We'll perform two separate searches then.
  if (!$list_all && !$media_url) {
    $search_array = array('search_on'=>"sku", 'search_term'=>$search_string, 'search_filter'=>'contains');
    $products_xml = oauth(
      get_option('ss_clientid'), get_option('ss_secretkey'), get_option('ss_code'), get_option('ss_authorizationurl'), 
      DOWNLOAD, 
      array_merge(array('clientApp'=>'1', 'dbname'=>'products', 'version'=>'11.2', 'fields'=>'|Product GUID|Name|SKU|Graphic|', 'limit'=>$limit), $search_array)
    );
    $products = new SimpleXMLElement($products_xml['data']);
    if (count($products->Products->Product) > 0) { 
      foreach ($products->Products->Product as $product) {
        if (!array_key_exists(addslashes($product->Name), $products_ar))
          $products_ar[addslashes($product->Name)] = array($product->ProductGUID, $product->SKU, $product->Graphic);
      }
    }
  }
  
  if (count($products_ar) == 0) {
    echo "<div id=no_products>No matching products.</div>";
    return;
  }
  

  ksort($products_ar);
  $products_ar = array_slice($products_ar,0,$limit);
  
  echo "<div id=\"products\">";
  if (count($products_ar) == $limit) {
    echo "<div id=\"over_limit_warning\">Displaying first $limit products only. Please use \"Search\" button to narrow down your results.</div>";
  }
  $count = 1;
  foreach ($products_ar as $name => $data) {
    print_product($count, stripslashes($name), $data, $media_url);
    $count++;
  }
  echo "</div>";

}

function print_product ($count, $name, $data, $media_url) {
  
  $id = $data[0];
  $sku = rawurlencode($data[1]);
  $image = $data[2];
  
  if (!strstr($image, "://")) {
    if ($image && strstr($image,".") && $image != "no-image.png") {
      $image_ar = explode("/", $image,2);
      if (count($image_ar) == 2)
        $image = $image_ar[0]."/ss_size2/".$image_ar[1];
      else
        $image = "ss_size2/".$image_ar[0];
    } else
      $image = "ss_size2/no-image.png";
  }
  
  $outdated = "";
  if (!$media_url)
    $outdated = " outdated";
  
  echo "<div class=\"wp_product$outdated\" id=\"wp_product_$count\">";
  if ($media_url)
    if (strstr($image, "://"))
      echo "<img class=\"product_image\" src=\"".$image."\">";
    else
      echo "<img class=\"product_image\" src=\"".$media_url.$image."\">";
  if (strlen($sku) > 0)
    echo "<div class='product_sku'>".strip_tags($sku)."</div>";
  echo "<a class=\"product_name\" title=\"".str_replace("\"","&quot;",$name)."\">".strip_tags($name)."</a>";
  
  echo '<input type=hidden class="name_input" name="name_input" value="'.str_replace("\"","&quot;",$name).'">';
  echo '<input type=hidden class="guid_input" name="guid_input" value="'.$id.'">';
  echo '<input type=hidden class="sku_input" name="sku_input" value="'.str_replace("\"","&quot;",$sku).'">';
  echo "</div>";
}


function product_handler( $atts, $content=null, $code="" ) {
  global $product_list, $wp_id;
  ///debug_print("product_handler entered");
	//return "<iframe src='$content' width='100%' height='300px' ><p>IFRAME FAIL!</p></iframe>";
 
  extract( shortcode_atts( array(
		'id' => '',
    'sku'=>''
	), $atts ) );
  
  if (get_option('ss_identifier') == 'SKU')
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
  $identifier = get_option('ss_identifier');
  $id_list = implode(",",array_unique(array_keys($product_list))); 
  
  //debug_print("dispatched:|$id_list|");
  
  
  echo 
    "<script> var ss_path='".plugin_dir_url(__FILE__)."'; 
    var ss_identifier = \"$identifier\"; 
    var ss_product_map = $product_map; 
    var ss_id_list = \"$id_list\"; 
    var ss_shopsite_url=\"".get_option('ss_shopsite_url')."\";</script>";
  //echo "<script> var product_mapping = ".json_encode($product_list).";</script>";
}


function get_product_data($id_list) {
  $id_list = stripslashes($id_list);
  //debug_print("GPD |$id_list|");
  $clientid = get_option('ss_clientid');
  $secretkey = get_option('ss_secretkey');
  $ids = explode(",", $id_list);
  $decoded_id_list = array();
  foreach ($ids as $id) {
    array_push($decoded_id_list, urldecode($id));
  }
  $data = "$clientid:".implode(",",$decoded_id_list);
  $hmachash = hash_hmac("sha1", $data, $secretkey, true);
  $signature = rawurlencode(base64_encode($hmachash));
  
  $identifier = get_option('ss_identifier');
  
  //$shopsite_url = $_REQUEST['shopsite_url'];   
  $shopsite_url = get_option('ss_shopsite_url');
  //$id_list = "\"".str_replace(",","\",\"",$id_list)."\"";
  $url = $shopsite_url."&operation=get_products&".$identifier."_list=".$id_list."&signature=".$signature;
  //debug_print("DATA URL |$url|");
  /*$url_openable = ini_get('allow_url_fopen');
  ini_set('allow_url_fopen', true);
  $handle = fopen($url,'r');
  ini_set('allow_url_fopen', $url_openable);
	print(stream_get_contents($handle));*/
  $curl_res = curl_open($url);
  $product_data = $curl_res[0];
  if ($product_data)
    print($product_data);
  else
    print($product_data[1]);
}

function test_connection() {

  if  (!in_array  ('curl', get_loaded_extensions())) {
    return array("success"=>false, "error"=>"CURL PHP extension is not installed on your server. Contact your hosting provider.");
  }

  $test_download_xml = oauth(
    get_option('ss_clientid'), get_option('ss_secretkey'), get_option('ss_code'), get_option('ss_authorizationurl'), 
    DOWNLOAD, 
    array('clientApp'=>'1', 'dbname'=>'products', 'version'=>'11.2', 'fields'=>'|Product GUID|Name|SKU|', 'search_term'=>"B0gu5", 'search_on'=>'name', 'search_filter'=>'contains', 'limit'=>1)
  );
  if (!$test_download_xml["success"])
    return $test_download_xml;
    
  
  $res = curl_open(get_option('ss_shopsite_url'));
  if ($res[0] == false)
    return array("success"=>false, "error"=>$res[1]);
  
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
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  $html = curl_exec($ch);
  if (curl_errno($ch))
    $retval = array(false, curl_error($ch));
  else
    $retval = array($html);

  curl_close($ch);
      
  return $retval;
}

?>