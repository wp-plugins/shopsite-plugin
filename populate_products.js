window.onload = populate_products;

function populate_products() {
  //alert(id_list);
  //alert(path + "shopsite.php");
  $ = jQuery;
  $.ajax({
    async: true,
    type: 'POST',
    data: {ss_action:'get_data', shopsite_url:shopsite_url, id_list: id_list, identifier: identifier},
    url: path + "shopsite.php",
    success: function(data) {
      //alert(data);
      if (data.length > 1) {
        var products = data.split("\7");
        for (i in products) {
          if (products[i].length > 0) {
            var pair = products[i].split("\6");
            //alert(pair[0] + " " +pair[1]);
            for (j in product_map[pair[0]]) {
              $('#product_' + product_map[pair[0]][j]).html(pair[1]);
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