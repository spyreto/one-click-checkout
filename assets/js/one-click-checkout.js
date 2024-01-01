const modal = document.getElementById("one-click-checkout-modal");
const modalContent = document.querySelector(
  ".one-click-checkout-modal-content"
);
const modalFormContainer = document.getElementById("checkout-form-container");
const modalClose = document.getElementById("one-click-checkout-close-modal");
const spinner = document.querySelector(".one-click-checkout-loader");

// Store the initial values of the address form
let initialAddressFormValues = {};
const buyNowButton = document.getElementById("buy-now");

const variationSelects = document.querySelectorAll(
  ".variations_form .variations select"
);

// Function to check if variations have changed
if (variationSelects.length > 0 && buyNowButton) {
  // Initially set the correct state
  updateBuyNowButtonState();

  // Add change event listeners
  variationSelects.forEach(function (select) {
    select.addEventListener("change", updateBuyNowButtonState);
  });
}

// Function to handle ''Buy Now' button state
if (buyNowButton) {
  buyNowButton.addEventListener("click", function (e) {
    e.preventDefault();

    // Show spinner
    spinner.style.display = "inline-block";
    spinner.style.border = "5px solid #fefefe";

    // Show the modal
    modal.style.display = "block";

    setTimeout(() => {
      modal.style.opacity = 1;
    }, 10); // A minimal timeout to allow CSS to catch up

    // Get the product ID and quantity
    let productId = this.getAttribute("data-product-id");
    const quantityInput = document.querySelector(".quantity input");
    let quantity = quantityInput ? quantityInput.value : 1; // Default to 1 if no input found

    let data = new FormData();

    data.append("action", "fetch_checkout_form");
    data.append("nonce", oneClickCheckoutParams.nonce);
    data.append("product_id", productId);
    data.append("quantity", quantity);
    data.append("is_modal", true);

    // AJAX request to fetch checkout form
    fetch(oneClickCheckoutParams.ajax_url, {
      method: "POST",
      credentials: "same-origin",
      body: data,
    })
      .then((response) => response.text())
      .then((html) => {
        modalFormContainer.innerHTML = "";
        modalFormContainer.innerHTML = html;
        modalContent.style.display = "block";
      })
      .catch((error) => {
        console.error("Error:", error);
      })
      .finally(() => {
        // Hide spinner
        spinner.style.display = "none";
      });
  });
}

if (modal) {
  // When the user clicks anywhere outside of the modal, close it
  window.addEventListener("click", function (event) {
    if (event.target == modal && spinner.style.display === "none") {
      modal.style.display = "none";
    }
  });
}

// Check if the address form has changed and enable/disable the update button
if (modalFormContainer) {
  modalFormContainer.addEventListener("change", (e) => {
    if (e.target.matches("#one-click-checkout-shipping-address-form select")) {
      checkFormChanges();
    }
  });

  modalFormContainer.addEventListener("input", (e) => {
    if (e.target.matches("#one-click-checkout-shipping-address-form input")) {
      checkFormChanges();
    }
  });
}

// Close the modal when the "X" button is clicked
if (modalClose) {
  modalClose.addEventListener("click", (e) => {
    e.preventDefault();
    modal.style.display = "none";
  });
}

// Open the coupon form when the "Click here to enter your code" link is clicked
document.addEventListener("click", (e) => {
  if (e.target.matches(".one-click-checkout-change-shipping-details")) {
    e.preventDefault();
    const addressForm = document.getElementById(
      "one-click-checkout-shipping-address-form"
    );
    const updateButton = document.querySelector(
      ".one-click-checkout-update-address-btn"
    );
    updateButton.disabled = true;
    initialAddressFormValues = { ...getAddressFormValues() };

    addressForm.style.display =
      addressForm.style.display === "none" ? "block" : "none";
  } else if (e.target.matches(".one-click-checkout-update-address-btn")) {
    e.preventDefault();
    updateAddress();
  }
});

document.addEventListener("submit", (e) => {
  // Handle checkout form submission
  if (e.target.matches("#checkout-form-container .woocommerce-checkout")) {
    e.preventDefault();
    spinner.style.display = "inline-block";
    spinner.style.border = "5px solid #3d9cd2";

    let formData = new FormData(e.target);

    formData.append("action", "handle_modal_checkout");
    formData.append("nonce", oneClickCheckoutParams.nonce);

    // AJAX request to handle checkout
    fetch(oneClickCheckoutParams.ajax_url, {
      method: "POST",
      credentials: "same-origin",
      body: formData,
    })
      .then((response) => response.json())
      .then((response) => {
        console.log(response);
        if (response.success) {
          // Display the response in the modal
          if (response.data.redirect_url) {
            // Redirect to the payment gateway or specified URL
            const iframe = document.createElement("iframe");
            iframe.id = "one-click-checkout-iframe";
            iframe.src = response.data.redirect_url + "&from_modal=true";

            iframe.style.width = "100%";
            iframe.style.height = "600px"; // Adjust as needed

            modalFormContainer.innerHTML = ""; // Clear existing content
            modalFormContainer.appendChild(iframe);
          } else if (data.thankyou_page_html) {
            // Display the 'thank you' page HTML in your modal
            modalFormContainer.innerHTML = response.data.thankyou_page_html;
          }
        } else {
          console.log("error");
          // Handle errors, display messages as needed
        }
      })
      .catch((error) => console.error("Error:", error)) // Handle any exceptions in the AJAX call.
      .finally(() => {
        // Remove spinner or loading indication here
        spinner.style.display = "none";
      });
  }
});

// Update the state of the Buy Now button based on the selected variation
function updateBuyNowButtonState() {
  let allVariationsSelected = true;

  variationSelects.forEach(function (select) {
    if (!select.value) {
      allVariationsSelected = false;
    }
  });

  buyNowButton.disabled = !allVariationsSelected;
}

// Function to update specific parts of the address
function updateShippingDetailsSection(
  elementQuery,
  { shippingAddress, shippingCity, shippingState, shippingPostcode }
) {
  const addressElement = document.querySelector(elementQuery);

  // Split the address into parts using <br>
  var parts = addressElement.innerHTML.split("<br>");

  // Update the parts that you want to change
  // Assuming the street is the third part and the postal code is the last part
  parts[2] = shippingAddress;
  parts[3] = shippingCity;
  parts[4] = shippingState;
  parts[5] = shippingPostcode;

  // Join the parts back together and update the address element
  addressElement.innerHTML = parts.join("<br>");
}

// Update the shipping address
function updateAddress() {
  const addressForm = document.getElementById(
    "one-click-checkout-shipping-address-form"
  );

  // Show spinner

  // Create address data object
  let addressData = { ...getAddressFormValues() };

  let formData = new FormData();

  // Add form data to FormData object
  formData.append("action", "update_shipping_address");
  formData.append("nonce", oneClickCheckoutParams.nonce);
  formData.append("address_data", JSON.stringify(addressData));

  // AJAX request to update address
  fetch(oneClickCheckoutParams.ajax_url, {
    method: "POST",
    credentials: "same-origin",
    body: formData,
  })
    .then((response) => response.json())
    .then((response) => {
      if (response.success) {
        // Update the address fields
        const elementQuery = ".one-click-checkout-shipping-address address";

        updateShippingDetailsSection(
          elementQuery,
          response.data.newShippingAddress
        );
        addressForm.style.display = "none";

        modalFormContainer.innerHTML = response.data.checkoutHtml;
      } else {
        // Show the error message
        document.getElementById(
          "one-click-checkout-shipping-address-error"
        ).innerHTML = data.message;
      }
    })
    .catch((error) => console.error("Error:", error))
    .finally(() => {
      // Hide spinner
    });
}

function getAddressFormValues() {
  // Collect form data
  let shippingAddress = document.getElementById(
    "one_click_checkout_shipping_address_1"
  ).value;
  let shippingCity = document.getElementById(
    "one_click_checkout_shipping_city"
  ).value;
  let shippingState = document.getElementById(
    "one_click_checkout_shipping_state"
  ).value;
  let shippingPostcode = document.getElementById(
    "one_click_checkout_shipping_postcode"
  ).value;

  return { shippingAddress, shippingCity, shippingState, shippingPostcode };
}

// Check if the address form has changed
function checkFormChanges() {
  const updateButton = document.querySelector(
    ".one-click-checkout-update-address-btn"
  );
  const addressFormValues = { ...getAddressFormValues() };
  const isFormChanged = Object.keys(initialAddressFormValues).some(
    (key) => initialAddressFormValues[key] !== addressFormValues[key]
  );

  if (isFormChanged) {
    updateButton.disabled = false;
  } else {
    updateButton.disabled = true;
  }
}

// Load JS Script
function loadScript(url, callback) {
  var script = document.createElement("script");
  script.type = "text/javascript";
  script.src = url;

  script.onload = function () {
    if (typeof callback === "function") {
      callback();
    }
  };
  document.head.appendChild(script);
}
