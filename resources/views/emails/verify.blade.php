<!DOCTYPE html>
<html lang="en-US">
<head>
	<meta charset="utf-8">
</head>
<body>

<div>
	Hi {{ $first_name }},
	<br>
	Thank you for creating an account with us.Don't forget to complete your registration!
	<br>
	Please click on the link below to confirm your email address.
	<br>

	<b></b><a href="{{ url('api/user/verify', $verification_code)}}">Confirm my email address </a></b>

	<br/>
</div>

</body>
</html>