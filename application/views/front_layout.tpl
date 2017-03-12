<!DOCTYPE html>
<html lang="id">
	<head>
		<title>{block name='title'}{/block}SL-F Sync</title>
		<meta charset="utf-8" />
		<meta name="description" content="Sistem Langitan-Feeder Sync" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="msapplication-config" content="none"/>

		<!-- Source Sans Pro Font -->
		<link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro' rel='stylesheet' type='text/css'>
		
		<!-- Bootstrap -->
		<link href="{base_url('assets/css/bootstrap.min.css')}" rel="stylesheet">
		
		<style type="text/css">
			body { padding-top: 40px; }
		</style>

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		  <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
		<![endif]-->
		{block name='head'}{/block}
	</head>
	
	<body>
		<div class="navbar navbar-default navbar-fixed-top navbar-inverse">
			<div class="container">

				<div class="navbar-header">
					<a href="{site_url()}" class="navbar-brand">Sistem Langitan-Feeder Sync v0.2</a>
					<button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					</button>
				</div>

			</div>
		</div>
		
		<div class="container">
		{block name='body-content'}{/block}
		</div>

		<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
		<script src="{base_url('assets/js/jquery-1.12.3.min.js')}"></script>
		<!-- Include all compiled plugins (below), or include individual files as needed -->
		<script src="{base_url('assets/js/bootstrap.min.js')}"></script>
		{block name='footer-script'}{/block}
	</body>
	<!-- {$smarty.server.REMOTE_ADDR} -->
</html>