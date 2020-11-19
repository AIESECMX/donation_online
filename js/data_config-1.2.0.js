
// This code requires JQuery
$(document).ready(function() {
  // STEP 0
  // Retrieve config info from redis server
  $.get('./php/data.php',function (data){
    // Convert to JSON
    const { products, eys } = JSON.parse(data);

    // Get and sort products by donation amount
    products.sort((a,b) => a.amount<b.amount?-1:b.amount<a.amount?1:0);
    eys.sort((a,b) => a.name<b.name?-1:b.name<a.name?1:0);

    // STEP 1
    // Retrieve the LCs and put them in the LCs select
    $('#committee').append($('<option>', {
      value: 'VAM',
      text: 'Oficina Nacional',
    }));
    eys
      .filter(({name}) => name !== 'VAM')
      .forEach(({name}) => {
        $('#committee').append($('<option>', {
          value: name,
          text: name,
        }));
      });

    //Refresh materialize's select to show new values
    $('select').formSelect();

    // STEP 2(bis)
    // Set the donation amounts (depending on whether there's a discount, and if it's VAM) in the main page
    $('#committee').change(function () {
      $('#product').empty();
      $('#product').append($('<option>', {
        value: '',
        'data-amount': '',
        disabled: true,
        selected: true,
        text: 'Selecciona una opción...'
      }));

      $.each(products,function (_, {amount, id, name}) {
        $('#product').append($('<option>', {
          value: id,
          'data-amount': amount ,
          text: name + ' ($' + amount +' MXN)'
        }));

        //Refresh materialize's select to show new values
        $('select').formSelect();
      })
    })
  })
});
