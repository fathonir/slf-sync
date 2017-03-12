{foreach $column_set as $column}"{$column.column_name}"{if not $column@last},{/if}{/foreach}
{foreach $data_set as $data}
{foreach $column_set as $column}"{preg_replace('/\s+/', ' ', str_replace('"', '""', $data[$column.column_name]))}"{if not $column@last},{/if}{/foreach}
{/foreach}