
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

    var localDiscounts = data.localDiscounts.reduce((acc, ey) => {
      var obj = acc;
      obj[ey.name] = {
        ogv: ey.ogv,
        ogt: ey.ogt,
        oge: ey.oge,
      }
      return obj;
    },{});

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

    // STEP 2(bis)
    // Set the donation amounts (depending on whether there's a discount, and if it's VAM) in the main page
    $('#committee').change(function () {
      $('#product').empty();
      $('#product').append($('<option>', {
        value: '',
        'data-amount': '',
        text: 'Selecciona una opción...'
      }));
    
      var disclaimer = false;
      $.each(products,function (i,product) {
        var amount;
        var eyName = document.getElementById("committee").value;
        //Check for Global Discounts
        if(product.globalDiscount && product.globalDiscount !== 0) {
          amount = product.globalDiscount;
          $('#'+product.id+'-card-amount').html('<s>Donativo: $'+product.amount+' MXN</s>*<br>Donativo: $'+amount+' MXN');
          disclaimer = true;
        }
        //Then check for Local Discounts
        else if (localDiscounts[eyName] && localDiscounts[eyName][product.id] ) {
          amount = localDiscounts[eyName][product.id];
          $('#'+product.id+'-card-amount').html('<s>Donativo: $'+product.amount+' MXN</s>*<br>Donativo: $'+amount+' MXN');
          disclaimer = true;
        }
        //If there are neither, then go for normal donation
        else {
          amount = product.amount;
          $('#'+product.id+'-card-amount').text('Donativo: $'+amount+' MXN');
        }

        $('#product').append($('<option>', {
          value: product.id,
          'data-amount': amount ,
          text: product.name + ' ($' + amount +' MXN)'
        }));

        //Refresh materialize's select to show new values
        $('select').formSelect();

      })

      if(disclaimer) {
        $('#disclaimer').removeClass('hide');
      } else {
        $('#disclaimer').addClass('hide');
      }
      
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
