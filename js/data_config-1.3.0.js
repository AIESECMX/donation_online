
// This code requires JQuery
$(document).ready(function() {
  // STEP 0
  // Retrieve config info from redis server
  $.get('./php/data.php',function (data){
    // Convert to JSON
    const { products, eys } = JSON.parse(data);

    // Get and sort products by donation amount
    const uniqueProducts = Array.from(new Set(
      products.map(({name}) => name)
    ));
    console.log(products);

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

    // Set the donation amounts (depending on whether there's a discount, and if it's VAM) in the main page
    $('#product').empty();
    $('#product').append($('<option>', {
      value: '',
      disabled: true,
      selected: true,
      text: 'Selecciona una opción...'
    }));

    uniqueProducts.forEach((product) => {
      $('#product').append($('<option>', {
        value: product,
        text: product
      }));
    });

    document
      .getElementById('product')
      .addEventListener('change', (event) => {
        $('#duration').empty();
        const selected = event.target.value;
        const matchingProduct = products.filter(
          ({name}) => name === selected
        ).sort((a,b) => a.amount - b.amount);

        if (matchingProduct.length > 1) {
          $('#duration').append($('<option>', {
            value: '',
            disabled: true,
            selected: true,
            text: 'Selecciona una opción...'
          }));
        }

        matchingProduct.forEach(({id, amount}) => {
          $('#duration').append($('<option>', {
            value: id,
            'data-amount': amount,
            text: parseDuration(id) + ' ($' + amount +' MXN)'
          }));
        });

        //Refresh materialize's select to show new values
        $('select').formSelect();
      })

    //Refresh materialize's select to show new values
    $('select').formSelect();
  })
});

function parseDuration(id) {
  const idParts = id.split('_');
  const [timeUnit, number] = [idParts.pop(), idParts.pop()];
  return `${number} ${timeUnit}`;
}