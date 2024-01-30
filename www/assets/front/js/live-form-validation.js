function validateForm() {
  isValid = true;
  // This function deals with validation of the form fields
  const firstname = $("#firstname");
  //validate only lethers
  if (firstname.val() == "") {
    // set parent invalid
    firstname.addClass("invalid");
    //get parent
    firstname.parent().parent().addClass("invalid");
    isValid = false;
  } else {
    firstname.removeClass("invalid");
    firstname.parent().parent().removeClass("invalid");
  }

  const lastname = $("#lastname");
  //validate
  if (lastname.val() == "") {
    lastname.addClass("invalid");
    lastname.parent().parent().addClass("invalid");
    isValid = false;
  } else {
    lastname.removeClass("invalid");
    lastname.parent().parent().removeClass("invalid");
  }

  const email = $("#email");
  //validate email
  if (
    email.val() == "" ||
    !/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email.val())
  ) {
    email.addClass("invalid");
    email.parent().parent().addClass("invalid");
    isValid = false;
  } else {
    email.removeClass("invalid");
    email.parent().parent().removeClass("invalid");
  }

  const phone = $("#tel");
  //validate phone
  if (phone.val() == "" || !/[0-9\s]+/.test(phone.val())) {
    phone.addClass("invalid");
    phone.parent().parent().addClass("invalid");
    isValid = false;
  } else {
    phone.removeClass("invalid");
    phone.parent().parent().removeClass("invalid");
  }

  const address = $("#address");
  //validate
  if (false) {
    address.addClass("invalid");
  } else {
    address.removeClass("invalid");
  }

  const zip = $("#zip");
  //validate
  if (zip.val() != "" && !/^\d{3}\s?\d{2}$/.test(zip.val())) {
    zip.addClass("invalid");
    zip.parent().parent().addClass("invalid");
    isValid = false;
  } else {
    zip.removeClass("invalid");
    zip.parent().parent().removeClass("invalid");
  }

  const city = $("#city");
  //validate
  if (city.val() != "" && !/^[a-zA-Z]+$/.test(city.val())) {
    city.addClass("invalid");
    city.parent().parent().addClass("invalid");
    isValid = false;
  } else {
    city.removeClass("invalid");
    city.parent().parent().removeClass("invalid");
  }

  const gdpr = $("#gdpr");
  //validate
  if (gdpr.is(":checked") == false) {
    gdpr.addClass("invalid");
    gdpr.parent().parent().addClass("invalid");
    isValid = false;
  } else {
    gdpr.removeClass("invalid");
    gdpr.parent().parent().removeClass("invalid");
  }

  if (isValid) {
    //submit form
    $("#formPartThree").submit();
  }
}
