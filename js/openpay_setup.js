$(document).ready(function() {
  var sandbox = true; // Specify false to enable for production

  if(!sandbox) {
    OpenPay.setId('mwgdi1x7t1wmrhf1dxbj');
    OpenPay.setApiKey('pk_9f5e6fc2a3ac47a5a133bf3b2af6f4c8');
  } else {
    OpenPay.setId('mubypwi638te5k2z4q9c');
    OpenPay.setApiKey('pk_4981f6da36014523948d66211ba56258');
  }
  OpenPay.setSandboxMode(sandbox);
  OpenPay.deviceData.setup("payment-form", "deviceIdHiddenFieldName");

  $('#pay-button').on('click', function(event) {
    event.preventDefault();
    if(true || checkFields()){ // Ignore validations for now
      $("#pay-button").prop("disabled", true);
      OpenPay.token.extractFormAndCreate('payment-form', success_callback, error_callback); 
    }               
  });

  var success_callback = function(response) {
    var token_id = response.data.id;

    var eyDOM = document.getElementById("product");

    var product = eyDOM.children[eyDOM.selectedIndex].value;
    var amount = eyDOM.children[eyDOM.selectedIndex].dataset.amount;

    //Add extra values before submit
    $('<input />').attr('type', 'hidden')
      .attr('name', "token_id")
      .attr('value', token_id)
      .appendTo('#payment-form');
    $('<input />').attr('type', 'hidden')
      .attr('name', "product")
      .attr('value', product)
      .appendTo('#payment-form');
    $('<input />').attr('type', 'hidden')
      .attr('name', "amount")
      .attr('value', amount)
      .appendTo('#payment-form');
    $('#payment-form').submit();
  };

  var error_callback = function(response) {
      var desc = response.data.description != undefined ? response.data.description : response.message;
      alert("ERROR [" + response.status + "] " + desc);
      $("#pay-button").prop("disabled", false);
  };
});
//Full form fields validations 
function checkFields(){
  if( document.getElementById("holder_name").value == "" )
  {

      //alert( "Escribe nombre en la Tarjetas" );
      show_modal("Escribe nombre en la Tarjetas");   
      document.getElementById("holder_name").focus();
      return false;
  }
  if(! OpenPay.card.validateCardNumber(document.getElementById("card_number").value))
  {
      show_modal("Verifica el número de la tarjeta");
      document.getElementById("card_number").focus();
      
      return false;
  }if(! OpenPay.card.validateExpiry(document.getElementById("expiration_month").value,"20"+document.getElementById("expiration_year").value))
  {
      show_modal("Verifica la caducidad de tu tarjeta" );
      document.getElementById("expiration_year").focus();
      
      return false;
  }if(! OpenPay.card.validateCVC(document.getElementById("cvv2").value))
  {
      show_modal("Verifica el numero de seguridad" );
      document.getElementById("cvv2").focus();
      
      return false;
  }if( document.getElementById("name_participant").value == "" )
  {
      show_modal("Escribe el nombre del participante");
      document.getElementById("name_participant").focus();
      
      return false;
  }
  var re = /^\s*[\w\-\+_]+(\.[\w\-\+_]+)*\@[\w\-\+_]+\.[\w\-\+_]+(\.[\w\-\+_]+)*\s*$/;
  if(!re.test(document.getElementById("email").value ))
  {
      show_modal( "Escribe el correo electrónico del participante" );
      document.getElementById("email").focus();
      
      return false;
  }if( document.getElementById("phone_number").value == "" )
  {
      show_modal("Escribe el teléfono del participante" );
      document.getElementById("phone_number").focus();
      
      return false;
  }
  if (!document.getElementById("terms").checked){
    show_modal("Debes aceptar los terminos y condiciones" );


    return false;   
}
return true;

}

function show_modal(message){
          //getting ready the modal window
          var modal = document.getElementById('myModal');
  // Get the <span> element that closes the modal
  var span = document.getElementsByClassName("close")[0];
  // When the user clicks on <span> (x), close the modal
  span.onclick = function() {
      modal.style.display = "none";
  }
  // When the user clicks anywhere outside of the modal, close it
  window.onclick = function(event) {
      if (event.target == modal) {
          modal.style.display = "none";
      }
  }
  //getting ready the modal window
  document.getElementById("modal_text").innerHTML = message;
  modal.style.display = "block";
}