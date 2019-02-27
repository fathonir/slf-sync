{extends file='home_layout.tpl'}
{block name='head'}
	<style>tr.jumlahdata > td { font-size: 140% }</style>
{/block}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h2>Sinkronisasi Nilai Perkuliahan UMAHA Angkatan &lt; 2014</h2>
			</div>

			<table class="table table-bordered table-condensed" style="width: auto">
				<thead>
					<tr>
						<th>Nilai Perkuliahan</th>
						<th>Feeder</th>
						<th>Sistem Langitan</th>
						<th>Link</th>
						<th></th>
						<th>Update</th>
						<th>Insert</th>
						<th>Delete</th>
					</tr>
				</thead>
				<tbody>
					<tr class="jumlahdata">
						<td>Jumlah Data</td>
						<td class="text-center">{$jumlah.feeder}</td>
						<td class="text-center">{$jumlah.langitan}</td>
						<td class="text-center">{$jumlah.linked}</td>
						<td></td>
						<td class="text-center text-warning">&#8710;{$jumlah.update}</td>
						<td class="text-center text-success">+{$jumlah['insert']}</td>
						<td class="text-center text-danger">-{$jumlah['delete']}</td>
					</tr>
				</tbody>
			</table>

			<p class="lead text-success">Halaman ini untuk sinkronisasi Nilai Perkuliahan UMAHA untuk Angkatan &lt; 2014 karena semua mahasiswa
				sebelum 2014 nilainya dimasukkan sebagai mahasiswa transfer.</p>

			<form class="form-horizontal" action="{$url_sync}" method="post">
				<fieldset>

					<!-- Button -->
					<div class="form-group">
						<div class="col-md-12">
							<input type="submit" class="btn btn-primary" value="Proses Sinkronisasi" />
						</div>
					</div>
					
				</fieldset>
			</form>


			<form action="{$url_sync}" method="post">
				
			</form>
		</div>
	</div>
{/block}