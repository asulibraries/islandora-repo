Drupal.behaviors.self_deposit_debounce = {
    attach: function (context, settings) {
  
      // Attach a click listener to the submit button.
      var form = document.getElementById("webform-submission-self-deposit-add-form");
      var sBtn = document.getElementById('edit-actions-wizard-next');
      sBtn.addEventListener('click', function() {
          console.log('Submit button clicked!');
          sBtn.disabled = true;
          form.submit();
      }, false);
  
    }
  };