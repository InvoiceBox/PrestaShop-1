<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module">
			<a href="javascript:$('#invoicebox').submit();" title="{l s='Pay with Invoicebox' mod='invoicebox'}">
				<img src="{$module_template_dir}invoicebox.jpg" alt="{l s='Pay with Invoicebox' mod='invoicebox'}" />
				{l s='Pay with Invoicebox' mod='invoicebox'}
			</a>
		</p>
	</div>
</div>

<form id="invoicebox" name="payment" action="/modules/invoicebox/validation.php" method="post" enctype="application/x-www-form-urlencoded" accept-charset="utf-8">
    {foreach $data as $key => $val}
		<input type="hidden" name="{$key}" value="{$val}">
    {/foreach}
</form>