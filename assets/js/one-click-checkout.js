document.addEventListener("DOMContentLoaded", function () {
  var buyNowButton = document.getElementById("buy-now");
  var variationSelects = document.querySelectorAll(
    ".variations_form .variations select"
  );

  // Update the state of the Buy Now button based on the selected variation
  function updateBuyNowButtonState() {
    var allVariationsSelected = true;

    variationSelects.forEach(function (select) {
      if (!select.value) {
        allVariationsSelected = false;
      }
    });

    buyNowButton.disabled = !allVariationsSelected;
  }

  if (variationSelects.length > 0) {
    // Initially set the correct state
    updateBuyNowButtonState();

    // Add change event listeners
    variationSelects.forEach(function (select) {
      select.addEventListener("change", updateBuyNowButtonState);
    });
  }

  if (buyNowButton) {
    buyNowButton.addEventListener("click", function (e) {
      e.preventDefault();

      var productId = this.getAttribute("data-product-id");
      var quantityInput = document.querySelector(".quantity input");
      var quantity = quantityInput ? quantityInput.value : 1; // Default to 1 if no input found

      var data = new FormData();
      data.append("action", "one_click_checkout");
      data.append("product_id", productId);
      data.append("quantity", quantity);
      data.append("nonce", oneClickCheckoutParams.nonce);

      fetch(oneClickCheckoutParams.ajax_url, {
        method: "POST",
        credentials: "same-origin",
        body: data,
      })
        .then((response) => response.json())
        .then((response) => {
          if (response.success) {
            window.location.href = response.data.checkout_url; // Redirect to the order payment URL
          } else {
            // Handle error
          }
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    });
  }
});
