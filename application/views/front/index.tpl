{extends file='front_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">

			<div class="page-header">
				<h2>Sistem Langitan-Feeder Sync</h2>
			</div>

			<form class="form-horizontal" role="form" action="{site_url('auth/login')}" method="post">
				
				{if !empty($error_message)}
				<div class="alert alert-danger alert-dismissable">
					<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
					{$error_message}
				</div>
				{/if}
				
				<div class="form-group">
					<label for="wsdl" class="col-sm-2 control-label">Alamat Aplikasi Feeder</label>
					<div class="col-sm-4">
						<input type="text" class="form-control" autocomplete="on" autofocus="true" id="wsdl" name="wsdl" placeholder="http://localhost:8082/" />
					</div>
				</div>
				
				<div class="form-group">
					<label class="col-md-2 control-label" for="radios">Mode</label>
					<div class="col-md-4"> 
					<label class="radio-inline" for="radios-0">
						<input name="mode" id="radios-0" value="2" checked="checked" type="radio">Sandbox
					</label> 
					<label class="radio-inline" for="radios-1">
						<input name="mode" id="radios-1" value="1" type="radio">Live
					</label>
					</div>
				</div>
				
				<div class="form-group">
					<label for="wsdl" class="col-sm-2 control-label">Alamat Sistem Langitan</label>
					<div class="col-sm-4">
						<input type="text" class="form-control" autocomplete="on" autofocus="true" id="wsdl" name="langitan" value="">
					</div>
				</div>
				
				<div class="form-group">
					<label for="username" class="col-sm-2 control-label">Username</label>
					<div class="col-sm-4">
						<input type="text" class="form-control" autocomplete="on" autofocus="true" id="username" name="username" placeholder="Username" value="{set_value('username')}">
					</div>
				</div>
					
				<div class="form-group">
					<label for="password" class="col-sm-2 control-label">Password</label>
					<div class="col-sm-4">
						<input type="password" class="form-control" id="password" name="password" placeholder="Password">
					</div>
				</div>
					
				<div class="form-group">
					<div class="col-sm-offset-2 col-sm-10">
						<button type="submit" class="btn btn-primary">Login</button>
						
					</div>
				</div>
					
			</form>

			<ul>
				<li>Login yang digunakan sama dengan login untuk Aplikasi Feeder</li>
			</ul>

		</div>
	</div>
{/block}