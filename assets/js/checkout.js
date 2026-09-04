(function () {
  "use strict";

  var cfg = window.narratoCheckout || {};
  var restUrl = cfg.restUrl || "";
  var nonce = cfg.nonce || "";

  if (!restUrl) return;

  function apiRequest(endpoint, body) {
    return fetch(restUrl + endpoint, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": nonce,
      },
      body: JSON.stringify(body || {}),
    }).then(function (res) {
      return res.json().then(function (data) {
        return { ok: res.ok, data: data };
      });
    });
  }

  function getSelectedPlan() {
    var checked = document.querySelector('input[name="narrato_plan"]:checked');
    return checked ? checked.value : "monthly";
  }

  /* ----------------------------------------------------------
     Gateway tab switching
  ---------------------------------------------------------- */
  function initGatewayTabs() {
    var tabs = document.querySelectorAll(".narrato-gateway-tab");
    var panels = document.querySelectorAll(".narrato-gateway-panel");

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        var target = tab.getAttribute("data-gateway");
        tabs.forEach(function (t) {
          t.classList.remove("is-active");
        });
        panels.forEach(function (p) {
          p.classList.remove("is-active");
        });
        tab.classList.add("is-active");
        var panel = document.querySelector(
          '[data-gateway-panel="' + target + '"]',
        );
        if (panel) panel.classList.add("is-active");
      });
    });
  }

  /* ----------------------------------------------------------
     Stripe Elements
  ---------------------------------------------------------- */
  function initStripe() {
    var container = document.getElementById("narrato-stripe-card-element");
    var submitBtn = document.getElementById("narrato-stripe-submit");
    var errorEl = document.getElementById("narrato-stripe-errors");

    if (!container || !submitBtn || typeof Stripe === "undefined") return;

    var stripe, elements, cardElement, currentSecret;

    function loadStripeForPlan() {
      var plan = getSelectedPlan();

      apiRequest("/membership/checkout", {
        gateway: "stripe",
        plan: plan,
      }).then(function (res) {
        if (!res.ok) {
          errorEl.textContent = res.data.error || cfg.i18n.error;
          submitBtn.disabled = true;
          return;
        }

        currentSecret = res.data.client_secret;

        if (!stripe) {
          stripe = Stripe(res.data.publishable_key);
          elements = stripe.elements();
          cardElement = elements.create("card");
          cardElement.mount("#narrato-stripe-card-element");
        }

        submitBtn.disabled = false;
        errorEl.textContent = "";
      });
    }

    loadStripeForPlan();

    document
      .querySelectorAll('input[name="narrato_plan"]')
      .forEach(function (radio) {
        radio.addEventListener("change", loadStripeForPlan);
      });

    submitBtn.addEventListener("click", function () {
      if (!stripe || !currentSecret) return;

      submitBtn.disabled = true;
      submitBtn.textContent = cfg.i18n.processing;

      stripe
        .confirmCardPayment(currentSecret, {
          payment_method: { card: cardElement },
        })
        .then(function (result) {
          if (result.error) {
            errorEl.textContent = result.error.message;
            submitBtn.disabled = false;
            submitBtn.textContent = cfg.i18n.subscribe;
          } else {
            window.location.href = cfg.successUrl;
          }
        });
    });
  }

  /* ----------------------------------------------------------
     PayPal Smart Buttons
  ---------------------------------------------------------- */
  function initPaypal() {
    var container = document.getElementById("narrato-paypal-buttons");
    var errorEl = document.getElementById("narrato-paypal-errors");

    if (!container || typeof paypal === "undefined") return;

    function renderButtons() {
      container.innerHTML = "";

      paypal
        .Buttons({
          createSubscription: function (data, actions) {
            var plan = getSelectedPlan();

            return apiRequest("/membership/checkout", {
              gateway: "paypal",
              plan: plan,
            }).then(function (res) {
              if (!res.ok) {
                errorEl.textContent = res.data.error || cfg.i18n.error;
                throw new Error("plan fetch failed");
              }

              return actions.subscription.create({
                plan_id: res.data.plan_id,
                custom_id: String(res.data.user_id),
              });
            });
          },

          onApprove: function (data) {
            var plan = getSelectedPlan();

            return apiRequest("/membership/paypal/confirm", {
              subscription_id: data.subscriptionID,
              plan: plan,
            }).then(function (res) {
              if (res.ok) {
                window.location.href = cfg.successUrl;
              } else {
                errorEl.textContent = res.data.error || cfg.i18n.error;
              }
            });
          },

          onError: function () {
            errorEl.textContent = cfg.i18n.error;
          },
        })
        .render("#narrato-paypal-buttons");
    }

    renderButtons();
  }

  /* ----------------------------------------------------------
     Cancel membership
  ---------------------------------------------------------- */
  function initCancel() {
    var btn = document.getElementById("narrato-cancel-membership");
    if (!btn) return;

    var resultEl = document.querySelector(".narrato-cancel-result");

    btn.addEventListener("click", function () {
      if (!confirm(cfg.i18n.confirmCancel)) return;

      btn.disabled = true;

      apiRequest("/membership/cancel", {}).then(function (res) {
        if (res.ok) {
          resultEl.textContent = cfg.i18n.cancelled;
          setTimeout(function () {
            window.location.reload();
          }, 1500);
        } else {
          resultEl.textContent = res.data.error || cfg.i18n.error;
          btn.disabled = false;
        }
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initGatewayTabs();
    initStripe();
    initPaypal();
    initCancel();
  });
})();
