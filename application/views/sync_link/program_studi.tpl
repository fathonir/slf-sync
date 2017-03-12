{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Sinkronisasi Link Program Studi</h2>
			</div>

			<table class="table table-bordered table-condensed" style="width: auto">
				<thead>
					<tr>
						<th>Program Studi</th>
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

			<p><a href="{site_url('sync/start/link_program_studi')}" class="btn btn-primary">Proses Sinkronisasi</a></p>
		</div>
	</div>
{/block}