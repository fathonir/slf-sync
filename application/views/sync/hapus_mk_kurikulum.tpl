{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Sinkronisasi Hapus Mata Kuliah Kurikulum</h2>
			</div>

			<form class="form-horizontal" method="post" action="{site_url('sync/start/')}/hapus_mk_kurikulum">
				<fieldset>

					<!-- Form Name -->
					<legend>Parameter</legend>

					<!-- Select Basic -->
					<div class="form-group">
						<label class="col-md-2 control-label" for="selectbasic">Program Studi</label>
						<div class="col-md-4">
							<select name="id_sms" class="form-control" required>
								<option value=''>Pilih Program Studi</option>
								{foreach $sms_set as $sms}
									<option value="{$sms.id_sms}">{$sms.nm_lemb}</option>
								{/foreach}
							</select>
						</div>
					</div>

					<!-- Select Basic -->
					<div class="form-group">
						<label class="col-md-2 control-label" for="selectbasic">Kurikulum</label>
						<div class="col-md-4">
							<select name="id_kurikulum_sp" class="form-control" required></select>
						</div>
					</div>

					<!-- Button -->
					<div class="form-group">
						<label class="col-md-2 control-label" for="singlebutton"></label>
						<div class="col-md-4">
							<input type="submit" class="btn btn-primary" value="Proses"/>
						</div>
					</div>

				</fieldset>
			</form>


		</div>
	</div>
{/block}
{block name='footer-script'}
	<script>
		$('select[name="id_sms"]').on('change', function() {
			var id_sms = $(this).val();
			
			jQuery.getJSON('{site_url('sync/ambil_kurikulum')}/'+id_sms, function(data) {
				$('select[name="id_kurikulum_sp"]').html(''); // clear
				$.each(data, function(key, val) {
					$('select[name="id_kurikulum_sp"]').append('<option value="'+val.id_kurikulum_sp+'">'+val.nm_kurikulum_sp+'</option>');
				});
			});

		});
	</script>
{/block}