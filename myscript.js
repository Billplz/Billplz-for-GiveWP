jQuery( function ( $ ) {
  init_billplz_meta();
  $(".billplz_customize_billplz_donations_field input:radio").on("change", function() {
    init_billplz_meta();
  });

  function init_billplz_meta(){
    if ("enabled" === $(".billplz_customize_billplz_donations_field input:radio:checked").val()){
      $(".billplz_api_key_field").show();
      $(".billplz_collection_id_field").show();
      $(".billplz_x_signature_key_field").show();
      $(".billplz_description_field").show();
      $(".billplz_reference_1_label_field").show();
      $(".billplz_reference_1_field").show();
      $(".billplz_reference_2_label_field").show();
      $(".billplz_reference_2_field").show();
      $(".billplz_collect_billing_field").show();
    } else {
      $(".billplz_api_key_field").hide();
      $(".billplz_collection_id_field").hide();
      $(".billplz_x_signature_key_field").hide();
      $(".billplz_description_field").hide();
      $(".billplz_reference_1_label_field").hide();
      $(".billplz_reference_1_field").hide();
      $(".billplz_reference_2_label_field").hide();
      $(".billplz_reference_2_field").hide();
      $(".billplz_collect_billing_field").hide();
    }
  }
});