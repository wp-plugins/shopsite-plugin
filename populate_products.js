window.onload = populate_products;

function rfc3986EncodeURIComponent (str) {  
    return encodeURIComponent(str).replace(/[!'()*]/g, escape);  
}

function populate_products() {
  //alert(id_list);
  //alert(path + "shopsite.php");
  $ = jQuery;
  $.ajax({
    async: true,
    type: 'POST',
    data: {ss_action:'get_data', shopsite_url:shopsite_url, id_list: id_list, identifier: identifier},
    url: ss_path + "shopsite.php",
    success: function(data) {
      //alert(data);
      if (data.length > 1) {
        var products = data.split("\7");
        for (i in products) {
          if (products[i].length > 0) {
            var pair = products[i].split("\6");
            var id = rfc3986EncodeURIComponent(pair[0]);
            //alert(id);
            for (j in product_map[id]) {
              $('#product_' + product_map[id][j]).html(pair[1]);
            }
          }
        }
      }
    }
  });
  /*var wp_id = 1;
  while (typeof product_list['wp' + wp_id] != 'undefined') {
    //alert(wp_id + " " + product_list['wp' + wp_id]);
    wp_id++;
  }*/
}