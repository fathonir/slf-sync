{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Data Tabel {$ci->uri->segment(3, 0)}</h2>
			</div>

			<p><a href="{site_url('webservice/list_table')}">Back</a></p>
			
			<table class="table table-bordered table-condensed" style="width: auto">
				<thead>
					<tr>
                        <th>#</th>
						{foreach $column_set as $column}
							<th>{$column.column_name}</th>
						{/foreach}
					</tr>
				</thead>
				<tbody>
					{foreach $data_set as $data}
						<tr>
                            <td>{$data@index + 1}</td>
							{foreach $column_set as $column}
								<td>{if isset($data[$column.column_name])}{$data[$column.column_name]}{/if}</td>
							{/foreach}
						</tr>
                    {foreachelse}
                        <tr>
                            <td colspan="{count($column_set) + 1}"><i>Tidak ada data</i></td>
                        </tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
{/block}