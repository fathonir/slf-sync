{extends file='home_layout.tpl'}
{block name='head'}
	<meta http-equiv="refresh" content="30">
{/block}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Selamat Datang di Sistem Langitan-Feeder Sync</h2>
			</div>

			<table class="table table-bordered table-condensed" style="width: auto">
				<thead>
					<tr>
						<th>Keterangan</th>
						<th>Nilai</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>WSDL</td>
						<td>{$ci->session->userdata('wsdl')}</td>
					</tr>
					<tr>
						<td>Token</td>
						<td>{$ci->session->userdata('token')}</td>
					</tr>
					<tr>
						<td colspan=2></td>
					</tr>
					<tr>
						<td>id_sp</td>
						<td>{$satuan_pendidikan.id_sp}</td>
					</tr>
					<tr>
						<td>Kode PT</td>
						<td>{$satuan_pendidikan.npsn}</td>
					</tr>
					<tr>
						<td>Nama PT</td>
						<td>{$satuan_pendidikan.nm_lemb}</td>
					</tr>
					<tr>
						<td colspan=2></td>
					</tr>
					<tr>
						<td>Tanggal Expired</td>
						<td>{$expired.result}</td>
					</tr>
				</tbody>
			</table>
					
			<h4>Change Log Webservice</h4>
			<pre>{$changelog.result}</pre>
		</div>
	</div>
{/block}