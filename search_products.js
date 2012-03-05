window.onload = do_stuff;

$ = jQuery;

function do_stuff() {
  $('#search_button').click(function() {
    $('#search_results').html("<div id=please_wait>Searching... please wait</div>");
    var data = "ss_action=search_products&search_string=" + $('#search_string').val() + "&search_on=" + $('#search_on').val() + "&remember_search=" + $('#remember_search').is(':checked');
    //alert(data);
    $.ajax({
      async: true,
      data: data,
      url: "shopsite.php",
      success: 
      function(data) {
        //alert(data);
        $('#search_results').html(data);
      }
    });
  });
  
  $('#search_string').click(function() {
    if ($(this).val() == default_search_string)
      $(this).val('');
  });
}