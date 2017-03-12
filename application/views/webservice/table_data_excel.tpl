<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="id">
	<head>
		<meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
	</head>
	<body>
		<table border="1">
			<thead>
				<tr>
					{foreach $column_set as $column}
						<th>{$column.column_name}</th>
					{/foreach}
				</tr>
			</thead>
			<tbody>
				{foreach $data_set as $data}
					<tr>
					{foreach $column_set as $column}
						<td>{$data[$column.column_name]}</td>
					{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>
	</body>
</html>