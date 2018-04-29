
// This code requires JQuery
$(document).ready(function() {
  // STEP 0
  // Retrieve config info from redis server
  $.get('./php/data.php',function (data){
    // Convert to JSON
    var data = JSON.parse(data);

    // Get and sort products by donation amount
    var products = data.products.sort(function (a,b) {
      return a.amount<b.amount?-1:b.amount<a.amount?1:0;
    });
    // Get and sort eys by name (VAM always goes to the end)
    var eys = data.eys.map(function (el) {
      return {name: el.name};
    }).sort(function (a,b) {
      //VAM always goes to the end
      return a.name === 'VAM' ? 1 :b.name === 'VAM' ? -1 : a.name.localeCompare(b.name,'la');
    });

    // STEP 1
    // Retrieve the LCs and put them in the LCs select
    // 'VAM' must be the name of the Virtual Allocated Markets in the config file
    $('#committee').append($('<option>', {
      value: 'VAM',
      text: 'Oficina Nacional'
    }));
    $.each(eys,function (i,ey) {
      $('#committee').append($('<option>', {
        value: ey.name==='VAM'?'Fondo Perdido':ey.name,
        text: ey.name==='VAM'?'Otro...':ey.name
      }));
    })

    //Refresh materialize's select to show new values
    $('select').formSelect();
    
    //THIS SHOULD BE STEP 2, WERE IT NOT FOR THE VAM EXCEPTION
    /* // STEP 2 
    // Set the donation amounts (depending on whether there's a discount) in the main page
    $.each(products,function (i,product) {
      $('#product').append($('<option>', {
        value: product.id,
        'data-amount': !product.globalDiscount || product.globalDiscount === 0 ? product.amount : product.globalDiscount ,
        text: product.name + ' ($' + (!product.globalDiscount || product.globalDiscount === 0 ? product.amount : product.globalDiscount) +' MXN)'
      }));

      if(product.globalDiscount){
        $('#'+product.id+'-card-amount').html('<s>Donativo: $'+product.amount+' MXN</s>*<br>Donativo: $'+product.globalDiscount+' MXN');
      } else {
        $('#'+product.id+'-card-amount').text('Donativo: $'+product.amount+' MXN');
      }
    }) */

    // STEP 2(bis)
    // Set the donation amounts (depending on whether there's a discount, and if it's VAM) in the main page
    $('#committee').change(function () {
      $('#product').empty();
      $('#product').append($('<option>', {
        value: '',
        'data-amount': '',
        text: 'Selecciona una opción...'
      }));

      $.each(products,function (i,product) {
        var excluded = ['VAM','Fondo Perdido'];
        if(excluded.indexOf(document.getElementById("committee").value) != -1 ) {
          amount = product.amount 
        }
        else {
          amount = !product.globalDiscount || product.globalDiscount === 0 ? product.amount : product.globalDiscount
        }
        $('#product').append($('<option>', {
          value: product.id,
          'data-amount': amount ,
          text: product.name + ' ($' + amount +' MXN)'
        }));

        if(product.globalDiscount){

          $('#'+product.id+'-card-amount').html('<s>Donativo: $'+product.amount+' MXN</s>*<br>Donativo: $'+product.globalDiscount+' MXN');
        } else {
          $('#'+product.id+'-card-amount').text('Donativo: $'+product.amount+' MXN');
        }

        //Refresh materialize's select to show new values
        $('select').formSelect();

      })
    })

    $.each(products,function (i,product) {
      if(product.globalDiscount){
        //If there's at least one discounted product, we need to show the disclaimer
        $('#disclaimer').removeClass('hide');
        $('#'+product.id+'-card-amount').html('<s>Donativo: $'+product.amount+' MXN</s>*<br>Donativo: $'+product.globalDiscount+' MXN');
      } else {
        $('#'+product.id+'-card-amount').text('Donativo: $'+product.amount+' MXN');
      }
    })

  })
});
