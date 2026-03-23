(function($){
  $(function(){
    var $pref = $('#ou_pref');
    var $city = $('#ou_city');

    function loadCities(prefId, currentCityId){
      if(!prefId){
        $city.html('<option value="">すべて</option>');
        return;
      }
      $.post(OU_GD.ajax_url, {
        action: 'ou_get_cities',
        nonce: OU_GD.nonce,
        pref_id: prefId
      }).done(function(resp){
        if(resp && resp.success){
          var html = '<option value="">すべて</option>';
          resp.data.cities.forEach(function(c){
            var selected = (currentCityId && String(currentCityId) === String(c.id)) ? ' selected' : '';
            html += '<option value="'+ c.id +'"'+ selected +'>' + c.name + '</option>';
          });
          $city.html(html);
        }
      });
    }

    var currentPref = $pref.data('current');
    var currentCity = $city.data('current');
    if(currentPref && !$city.find('option[value!=""]').length){
      loadCities(currentPref, currentCity);
    }

    $pref.on('change', function(){
      loadCities($(this).val(), null);
    });
  });
})(jQuery);
