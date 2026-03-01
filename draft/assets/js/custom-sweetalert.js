// Reusable
const buttonProceed = Swal.mixin({
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    heightAuto: false,
    customClass: {
      container: 'popup-modal-container',
      title: 'title-ff',
      popup: 'popup-modal',
      icon: 'popup-modal-icon',
      actions: 'popup-modal-actions',
      confirmButton: 'actions-confirm',
      cancelButton: 'actions-cancel',
      htmlContainer: 'popup-modal-description'
    }
  })

  const buttonWarn = Swal.mixin({
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    heightAuto: false,
    customClass: {
      container: 'popup-modal-container',
      confirmButton: 'wary-btn',
      title: 'title-ff',
      popup: 'popup-modal',
      icon: 'popup-modal-icon',
      actions: 'popup-modal-actions',
      cancelButton: 'actions-cancel',
      htmlContainer: 'popup-modal-description'
    }
  })

  const Toast = Swal.mixin({
    toast: true,
    position: 'top',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener('mouseenter', Swal.stopTimer)
      toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
  })

  const ColoredToast = Swal.mixin({
    toast: true,
    position: 'top-right',
    customClass: {
      popup: 'colored-toast', // Comes from SweetAlert2, just called.
      title: 'coloredToast-title', // Then this is a customClass.
      container: 'colored-toast-container'
    },
    showConfirmButton: false,
    timer: 4000
  })

  const successResponse = Swal.mixin({
    icon: 'success',
    showConfirmButton: false,
    heightAuto: false,
    customClass: {
      container: 'popup-modal-container',
      title: 'title-ff',
      popup: 'popup-modal',
      icon: 'popup-modal-icon'
    }
  })
  <?php if (isset($_SESSION['logging_in_title']) && $_SESSION['logging_in_title'] != '') { ?>

    ColoredToast.fire({
      icon: '<?php echo $_SESSION['logging_in_status']?>',
      title: '<?php echo $_SESSION['logging_in_title']?>',
      timer: 3000
    })

  <?php } unset($_SESSION['logging_in_title']); unset($_SESSION['logging_in_status']); ?>

  // Welcome banner
  <?php if (isset($_SESSION['login_message']) && $_SESSION['login_message'] != '') { ?>

    Toast.fire({
      icon: 'success',
      title: '<?php echo $_SESSION['login_message']; ?>',
      customClass: {
        popup: 'welcome-banner',
        title: 'title-ff'
      }
    });

  <?php unset($_SESSION['login_message']); } ?>

  $(window).on('load', function() {

    // Edit
    $('.edit-pitem').on('click', function (e) {
      e.preventDefault();
      var self = $(this);

      buttonProceed.fire({
        title: 'Edit Details',
        text: "Are you sure to edit the details of this pet?",
        confirmButtonText: 'Yes, edit now!'

      }).then((result) => {

        if (result.isConfirmed) {
          location.href = self.attr('href');
        }
      })
    })

    // Delete
    $('.delete-pitem').on('click', function (e) {
      e.preventDefault();
      var self = $(this);

      buttonWarn.fire({
        title: 'Delete Item',
        text: "You won't be able to revert this!",
        confirmButtonText: 'Yes, delete it!'

      }).then((result) => {

        if (result.isConfirmed) {
          location.href = self.attr('href');
        }
      })
    })

  })

  // Action status
  <?php if (isset($_SESSION['action_status']) && $_SESSION['action_status'] != '') { ?>

    successResponse.fire({
      title: '<?php echo $_SESSION['action_status']; ?>',
      timer: 2000
    })

  <?php } unset($_SESSION['action_status']); ?>

  // Toast response
  <?php if (isset($_SESSION['response_title']) && $_SESSION['response_title'] != '') { ?>

    ColoredToast.fire({
      icon: '<?php echo $_SESSION['response_status']; ?>',
      title: '<?php echo $_SESSION['response_title']; ?>'
    })

  <?php } unset($_SESSION['response_title']); unset($_SESSION['response_status']); ?>

  // Logout
  $(window).on('load', function() {

    $('#logout-admin').on('click', function (e) {
      e.preventDefault();
      var self = $(this);

      buttonWarn.fire({
        title: 'Logout',
        text: "Are you sure to logout?",
        showCancelButton: true,
        confirmButtonText: 'Logout'

      }).then((result) => {

        if (result.isConfirmed) {
          location.href = self.attr('href');
        }
      })
    })

  })

  // Adoption Form
  $(document).ready(function () {
    $('#adopt-req-frm').submit(function (e) {
      e.preventDefault();

      var formData = new FormData(this);

      buttonProceed.fire({
        title: 'Submit Form',
        text: "Are you sure to submit your form now?",
        confirmButtonText: 'Yes, submit'

      }).then((result) => {

        if (result.isConfirmed) {

          $.ajax({
            url: 'adoption-form-process.php',
            type: 'post',
            data: formData,
            success: function() {

              $('#adopt-req-frm')[0].reset();

              buttonProceed.fire({
                title: 'Schedule Interview',
                html:
                  '<p class="prgph-heading">Message us to schedule your virtual interview?</p>' +
                  '<p class="prgph-subheading">You will be redirect to our Facebook page after clicking the <span>Proceed</span> button.</p>',
                confirmButtonText: '<i class="fa-brands fa-facebook-messenger"></i> Proceed',
                cancelButtonText: 'Not now'

              }).then((result) => {

                if (result.isConfirmed) {
                  window.open('https://www.facebook.com/cookieandrescuedstrays', '_blank');

                } else if (result.dismiss === Swal.DismissReason.cancel) {
                  successResponse.fire({
                    title: 'Form Submitted',
                    timer: 2500
                  })
                }

              })

            },
            cache: false,
            contentType: false,
            processData: false
          })
        }
      })
    })
  })

  // Reset
  <?php if (isset($_SESSION['mail-response']) && $_SESSION['mail-response'] != '') { ?>

    successResponse.fire({
      title: '<?php echo $_SESSION['mail-response']; ?>',
      text: 'You may now check your Email.',
      showConfirmButton: true,
      confirmButtonColor: '#3085d6',
      customClass: {
        container: 'popup-modal-container',
        title: 'title-ff',
        popup: 'popup-modal',
        icon: 'popup-modal-icon',
        actions: 'popup-modal-actions',
        confirmButton: 'actions-confirm',
        cancelButton: 'actions-cancel',
        htmlContainer: 'popup-modal-description'
      }
    })
  
  <?php } unset($_SESSION['mail-response']); ?>

  // Delete Request
  $('.deletereq-button').on('click', function (e) {
    e.preventDefault();
    var self = $(this);

    buttonWarn.fire({
      title: 'Delete Request',
      text: "You won't be able to revert this!",
      confirmButtonText: 'Yes, delete it!'

    }).then((result) => {

      if (result.isConfirmed) {
        location.href = self.attr('href');
      }
    })
  })