{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Hapus Data Aktivitas Mahasiswa</h2>
			</div>
			
			{if isset($deleted)}
			<div class="alert alert-info" role="alert">Data berhasil di hapus !</div>
			{/if}
			
			<form class="form-horizontal" action="{site_url('delete-data/kuliah-mahasiswa')}" method="post">
				<fieldset>
					
					<!-- Text input-->
					<div class="form-group">
						<label class="col-md-2 control-label" for="textinput">NIM</label>  
						<div class="col-md-4">
							<input name="nipd" class="form-control input-md" id="nipd" type="text">
						</div>
					</div>

					<!-- Select Basic -->
					<div class="form-group">
						<label class="col-md-2 control-label" for="selectbasic">Semester</label>
						<div class="col-md-4">
							<input name="id_smt" class="form-control input-md" id="id_smt" type="text">
							<span class="help-block">Misal : 20151</span>  
						</div>
					</div>
					
					<!-- Button -->
					<div class="form-group">
						<label class="col-md-2 control-label" for="singlebutton"></label>
						<div class="col-md-4">
							<input type="submit" class="btn btn-danger" value="Hapus" />
						</div>
					</div>
					
				</fieldset>
			</form>
				
			
		</div>
	</div>
{/block}