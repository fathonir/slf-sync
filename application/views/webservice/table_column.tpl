{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Kolom Tabel <strong>{$ci->uri->segment(3, 0)}</strong></h2>
			</div>

			<p><a href="{site_url('webservice/list_table')}">Back</a></p>
			
			<table class="table table-bordered table-condensed" style="width: auto">
				<thead>
					<tr>
						<th>Nama Kolom</th>
						<th>PK</th>
						<th>Type</th>
						<th>Not Null ?</th>
						<th>Default</th>
						<th>Deskripsi</th>
					</tr>
				</thead>
				<tbody>
					{foreach $data_set as $data}
						<tr>
							<td>{$data.column_name}</td>
							<td class="text-center">{if isset($data.pk)}{$data.pk}{/if}</td>
							<td>{$data.type}</td>
							<td class="text-center">{if isset($data.not_null)}{$data.not_null}{/if}</td>
							<td class="text-center">{if isset($data['default'])}{$data['default']}{/if}</td>
							<td>{$data.desc}</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
{/block}