document.addEventListener("DOMContentLoaded", function () {
  var buyNowButton = document.getElementById("buy-now");
  var variationSelects = document.querySelectorAll(
    ".variations_form .variations select"
  );

  var modal = document.getElementById("one-click-checkout-modal");
  var modalContent = document.getElementById("checkout-form-container");
  var modalClose = document.getElementById("one-click-checkout-close-modal");

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

      // Get the product ID and quantity
      let productId = this.getAttribute("data-product-id");
      let quantityInput = document.querySelector(".quantity input");
      let quantity = quantityInput ? quantityInput.value : 1; // Default to 1 if no input found

      let data = new FormData();

      data.append("action", "fetch_checkout_form");
      data.append("nonce", oneClickCheckoutParams.nonce);
      data.append("product_id", productId);
      data.append("quantity", quantity);

      // AJAX request to fetch checkout form
      fetch(oneClickCheckoutParams.ajax_url, {
        method: "POST",
        credentials: "same-origin",
        body: data,
      })
        .then((response) => response.text())
        .then((html) => {
          modalContent.innerHTML = html;
          modal.style.display = "block";
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    });
  }

  // Close the modal when the "X" button is clicked
  if (modalClose) {
    modalClose.addEventListener("click", function (e) {
      e.preventDefault();
      modal.style.display = "none";
    });
  }

  // Close the modal when the user clicks outside of it
  if (modal) {
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        modal.style.display = "none";
      }
    });
  }

  // Open the coupon form when the "Click here to enter your code" link is clicked
  document.addEventListener("click", function (e) {
    if (e.target.matches("#checkout-form-container .showcoupon")) {
      e.preventDefault();
      var couponContainer = document.querySelector(
        "#checkout-form-container .checkout_coupon.woocommerce-form-coupon"
      );
      couponContainer.style.display = "block";
    }
  });
});
