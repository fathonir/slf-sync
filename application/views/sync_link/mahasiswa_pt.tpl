{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Sinkronisasi Link Mahasiswa PT</h2>
			</div>
			
			<table class="table table-bordered table-condensed" style="width: auto">
				<thead>
					<tr>
						<th>Mahasiswa</th>
						<th>Feeder</th>
						<th>Sistem Langitan</th>
						<th>Link</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Jumlah Data</td>
						<td class="text-center">{$jumlah.feeder}</td>
						<td class="text-center">{$jumlah.langitan}</td>
						<td class="text-center">{$jumlah.linked}</td>
					</tr>
				</tbody>
			</table>
					
			<p><a href="{$url_sync}" class="btn btn-primary">Proses Sinkronisasi</a></p>
		</div>
	</div>
{/block}