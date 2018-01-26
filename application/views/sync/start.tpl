{extends file='home_layout.tpl'}
{block name='body-content'}
	<div class="row">
		<div class="col-md-12">
			<div class="page-header">
				<h3>Sinkronisasi <u><b>{$jenis_sinkronisasi}</b></u></h3>
			</div>
			
			<form id="form1" class="form-horizontal">
				{if count($smarty.post) > 0}
					{$keys = array_keys($smarty.post)}
					{foreach $keys as $key}
					<input type="hidden" name="{$key}" value="{$smarty.post[$key]}"/>
					{/foreach}
				{/if}
			</form>
			
			<div class="row">
				<div class="col-md-2">
					<button class="btn btn-success" id='syncButton'>Start Sinkronisasi</button>
					<img src="{base_url('assets/ajax-loader.gif')}" style="display: none" id="loadingImg" />
				</div>
				<div class="col-md-2">
					<button class="btn btn-danger" id='stopButton' disabled="disabled">Stop Sinkronisasi</button>
				</div>
			</div>
			
			<p></p>
			<pre id='log' style="height: 500px;"></pre>
		</div>
	</div>
{/block}
{block name='footer-script'}
	<script type="text/javascript">
		
		var ajaxHandles = [];
		
		/**
		 * @param json data 
		 * @returns 
		 */
		function syncMessage(syncData)
		{	
			/* Sinkronisasi Selesai */
			if (syncData.status === 'done')
			{
				$('#log').append('['+ (new Date()).toLocaleString() + '] ' + syncData.message+'\n');
				$("#log").scrollTop($("#log")[0].scrollHeight);
				
				$('#syncButton').removeAttr('disabled');
				$('#stopButton').attr('disabled', 'disabled');
				$('#loadingImg').hide();
			}
			/* Sinkronisasi masih ada */
			else if (syncData.status === 'proses')
			{
				$('#log').append('['+ (new Date()).toLocaleString() + '] ' + syncData.message+'\n');
				$("#log").scrollTop($("#log")[0].scrollHeight);
				
				var ajaxHandle = $.ajax({
					type: 'POST',
					url: syncData.nextUrl,
					data: syncData.params,
					dataType: 'json'
				}).done(function(data){
					syncMessage(data);
				}).fail(function(jqXHR, textStatus, errorThrown){
					$('#log').append('['+ (new Date()).toLocaleString() + '] Fail : ' + jqXHR.responseText+'\n');
					
					$('#syncButton').removeAttr('disabled');
					$('#stopButton').attr('disabled', 'disabled');
					$('#loadingImg').hide();
				});
				
				ajaxHandles.push(ajaxHandle);
			}
			/* Kegagal sinkronisasi */
			else
			{
				
			}
		}
		
		$('#syncButton').on('click', function() {
			
			$('#log').append('['+ (new Date()).toLocaleString() + '] Mulai sinkronisasi ...\n');
			$("#log").scrollTop($("#log")[0].scrollHeight);
			
			$('#syncButton').attr('disabled', 'disabled');
			$('#stopButton').removeAttr('disabled');
			$('#loadingImg').show();
			
			var ajaxHandle = $.ajax({
				type: 'POST',
				url: '{$url}',
				data: $('#form1').serialize(),
				dataType: 'json',
				beforeSend: function() {
					
				}
			}).done(function(data) {
				syncMessage(data);
			}).fail(function(jqXHR) {
				$('#log').append('['+ (new Date()).toLocaleString() + '] Fail : ' + jqXHR.responseText + '\n');
				$('#syncButton').removeAttr('disabled');
				$('#stopButton').attr('disabled', 'disabled');
				$('#loadingImg').hide();
			}).always(function() {
				
			});
			
			ajaxHandles.push(ajaxHandle);
		});
		
		$('#stopButton').on('click', function() {
			
			$.each(ajaxHandles, function(index, ajaxReq) {
				ajaxReq.abort();
			});
			
			$('#log').append('['+ (new Date()).toLocaleString() + '] Stop by user !\n');
			$("#log").scrollTop($("#log")[0].scrollHeight);
			
			$('#syncButton').removeAttr('disabled');
			$('#stopButton').attr('disabled', 'disabled');
			$('#loadingImg').hide();
		});
	</script>
{/block}