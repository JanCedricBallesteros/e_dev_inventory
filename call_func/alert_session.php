<style>
	.colored-toast.swal2-icon-success {
		background-color: #a5dc86 !important;
	}

	.colored-toast.swal2-icon-error {
		background-color: #f27474 !important;
	}

	.colored-toast.swal2-icon-warning {
		background-color: #f8bb86 !important;
	}

	.colored-toast.swal2-icon-info {
		background-color: #3fc3ee !important;
	}

	.colored-toast.swal2-icon-question {
		background-color: #87adbd !important;
	}

	.colored-toast .swal2-title {
		color: white;
	}

	.colored-toast .swal2-close {
		color: white;
	}

	.colored-toast .swal2-html-container {
		color: white;
	}
</style>

<script>
	// Swal.fire({
	// 	icon: 'success',
	// 	title: 'Your work has been saved',
	// 	showConfirmButton: false,
	// 	timer: 1500
	// })

	function msg_alert(status, msg) {
		const Toast = Swal.mixin({
			toast: true,
			position: 'top-right',
			iconColor: 'white',
			showConfirmButton: false,
			timer: 3000,
			timerProgressBar: true,
			didOpen: (toast) => {
				toast.addEventListener('mouseenter', Swal.stopTimer)
				toast.addEventListener('mouseleave', Swal.resumeTimer)
			},
			customClass: {
				popup: 'colored-toast'
			}
		})
		Toast.fire({
			title: msg,
			icon: status
		})
	}

	function msg_html(img, name, position, status) {
		Swal.fire({
			html: '<div class="row">\
			<div class="col-12">' + img + '</div>\
			<div class="col-12">\
			<div class="row">\
			<div class="col-12 fw-bold fs-3 text-dark">' + name + '</div>\
			<div class="col-12">' + position + '</div>\
			</div>',
			footer: status,
			showConfirmButton: false,
			timerProgressBar: true,
			width: 450,
			// background: '#438ff4',
			padding: 10,
			timer: 2000
		});
	}

	function msg_error(title, msg) {
		Swal.fire({
			// position: 'top-end',
			title: title,
			text: msg,
			icon: 'error',
			timerProgressBar: true,
			showConfirmButton: false,
			timer: 2000
		});
	}

	function msg_warning(title, msg) {
		Swal.fire({
			// position: 'top-end',
			title: title,
			text: msg,
			icon: 'warning',
			timerProgressBar: true,
			showConfirmButton: false,
			timer: 2000
		});
	}

	function msg_success(title, msg) {
		Swal.fire({
			// position: 'top-end',
			title: title,
			text: msg,
			icon: 'success',
			timerProgressBar: true,
			showConfirmButton: false,
			timer: 2000
		});
	}

</script>