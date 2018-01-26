{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>WSDL Daftar Tabel</h2>
			</div>

			<table class="table table-bordered table-condensed" style="width: auto">
				<thead>
					<tr>
						<th>Nama Tabel</th>
						<th>Jenis</th>
						<th style="width: 40%">Keterangan</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					{foreach $data_set as $data}
						<tr>
							<td><strong>{$data.table}</strong></td>
							<td>{$data.jenis}</td>
							<td>{$data.keterangan}</td>
							<td class="text-right">
								<a class="btn btn-success btn-xs" href="{site_url('webservice/table_column')}/{$data.table}" role="button">Column</a>
								<a class="btn btn-default btn-xs" href="{site_url('webservice/table_data')}/{$data.table}" role="button">View Data</a>
								<a class="btn btn-default btn-xs" href="{site_url('webservice/table_data')}/{$data.table}/raw" role="button">View Data RAW</a>
								<a class="btn btn-default btn-xs" href="{site_url('webservice/table_data_csv')}/{$data.table}" role="button" target="_blank">Download CSV</a>
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
{/block}