{extends file='home_layout.tpl'}
{block name='head'}
	<style>
		.table { font-size: 12px; }
	</style>
{/block}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Data Tabel {$ci->uri->segment(3, 0)}</h2>
			</div>

			

			<form class="form-inline" style="margin-bottom: 10px">
				<div class="form-group">
					<label for="filter">Filter</label>
					<input type="text" class="form-control input-sm" id="filter" name="filter" style="width: 600px" value="{if isset($smarty.get.filter)}{$smarty.get.filter}{/if}">
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-default btn-sm">Filter</button>
					<a href="{site_url('webservice/list_table')}">Back</a>
				</div>
			</form>

			<div class="table-responsive">
				<table class="table table-bordered table-condensed" style="width: auto">
					<thead>
						<tr>
							<th>

							</th>
							{foreach $column_set as $column}
								<th>
									{$column.column_name}
								</th>
							{/foreach}
						</tr>
					</thead>
					<tbody>
						{foreach $data_set as $data}
							<tr>
								<td>
									<button class="button btn-xs btn-danger" data-toggle="modal" data-target="#myModal" data-table="{$table_name}" data-pkey='{$data['pkey']}'><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
								</td>
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
	</div>

	<!-- Modal -->
	<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="myModalLabel">Konfirmasi</h4>
				</div>
				<div class="modal-body">
					<h5>Apakah data ini akan dihapus ?</h5>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					<form method="post" action="{site_url('webservice/remove-data/')}" id="myForm" style="display: inline;">
						<input type="hidden" name="table" value="" />
						<input type="hidden" name="pkey" value="" />
						<button type="submit" class="btn btn-danger">Hapus</button>
					</form>
				</div>
			</div>
		</div>
	</div>
{/block}
{block name='footer-script'}
	<script type="text/javascript">
		$(document).ready(function() {
			$('#myModal').on('show.bs.modal', function(e) {
				var btn = $(e.relatedTarget);
				$('input[name="table"]').val(btn.data('table'));
				$('input[name="pkey"]').val(JSON.stringify(btn.data('pkey')));
			});
		});
	</script>
{/block}