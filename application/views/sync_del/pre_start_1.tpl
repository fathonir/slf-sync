{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>{$judul_sync_del}</h2>
			</div>
			
			{if !empty($sub_judul_sync_del)}<h5>{$sub_judul_sync_del}</h5>{/if}

			<table class="table table-bordered table-condensed" style="width: auto">
				<thead>
					<tr>
						<th>{$nama_data}</th>
						<th>Feeder</th>
						<th>Sistem Langitan</th>
					</tr>
				</thead>
				<tbody>
					<tr class="jumlahdata">
						<td>Jumlah Data</td>
						<td class="text-center">{$jumlah.feeder}</td>
						<td class="text-center">{$jumlah.langitan}</td>
					</tr>
				</tbody>
			</table>

			<form class="form-horizontal" action="{$url_sync}" method="post">
				<fieldset>

					<!-- Form Name -->
					<legend>Filter Data</legend>

					<!-- Select Basic -->
					<div class="form-group">
						<label class="col-md-2 control-label" for="selectbasic">Semester</label>
						<div class="col-md-4">
							<select name="semester" class="form-control" required>
								<option value=""></option>
								{foreach $semester_set as $s}
									<option value="{$s.ID_SEMESTER}">{$s.TAHUN_AJARAN} {$s.NM_SEMESTER} ({$s.ID_SMT})</option>
								{/foreach}
							</select>
						</div>
					</div>

					<!-- Button -->
					<div class="form-group">
						<label class="col-md-4 control-label" for="singlebutton"></label>
						<div class="col-md-4">
							<input type="submit" class="btn btn-primary" value="Proses Sinkronisasi Restore" />
						</div>
					</div>
					
				</fieldset>
			</form>


			<form action="{$url_sync}" method="post">
				
			</form>
		</div>
	</div>
{/block}
{block name='footer-script'}
	<script type="text/javascript">
		$(function(){
			
			$('input[type=submit]').prop('disabled', true);
			
			var refreshTable = function(semester) {
				var url = '{$url_data}'+semester;
				
				if (semester)
				{
					$.ajax({
						dataType: "json",
						url: url,
						beforeSend: function (xhr) {
							$('input[type=submit]').prop('disabled', true);
							$('body').css('cursor', 'progress');
						},
						success: function (data, textStatus, jqXHR) {
							$('tr.jumlahdata td:eq(1)').text(data.feeder);
							$('tr.jumlahdata td:eq(2)').text(data.langitan);
							$('tr.jumlahdata td:eq(3)').text(data.linked);
							$('tr.jumlahdata td:eq(5)').text('∆' + data.update);
							$('tr.jumlahdata td:eq(6)').text('+' + data.insert);
							$("input[type=submit]").prop('disabled', false);
							$('body').css('cursor', 'default');
						}
					});
				}
				
			};
			
			$('select[name=semester]').on('change', function(){
				refreshTable($('select[name=semester]').val());
			});
		});
	</script>
{/block}