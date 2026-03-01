<?php
$password_complexity ="	{
			minlength: {
				text: \"Your password at least minLength characters long\",
				minLength: 8,
			},
			containSpecialChars: {
				text: \"Your password should contain at least minLength special character\",
				minLength: 1,
				regex: new RegExp('([^!,%,&,@,#,$,^,*,?,_,~])', 'g')
			},
			containLowercase: {
				text: \"Your password should contain at least minLength lower case character\",
				minLength: 1,
				regex: new RegExp('[^a-z]', 'g')
			},
			containUppercase: {
				text: \"Your password should contain at least minLength upper case character\",
				minLength: 1,
				regex: new RegExp('[^A-Z]', 'g')
			},
			containNumbers: {
				text: \"Your password should contain at least minLength number\",
				minLength: 1,
				regex: new RegExp('[^0-9]', 'g')
			}
        }";
?>