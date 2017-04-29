{**
 * BukoliDelivery: module for PrestaShop 1.5-1.6
 *
 * @author    muratbastas <muratbsts@gmail.com>
 * @copyright 2017 muratbastas
 * @license   http://addons.prestashop.com/en/content/12-terms-and-conditions-of-use Terms and conditions of use (EULA)
 *}

{if !empty($bukoli_details->details)}
	{if $ps_version == 1.6}
		<div class="row">
			<div class="col-lg-12">
				<div class="panel">
					<h3><i class="icon-truck "></i> {l s='Bukoli delivery info' mod='bukolidelivery'}</h3>
					<div class="bukolidelivery_data">
						{$bukoli_details->details|escape:'htmlall':'UTF-8'}
					</div>
				</div>
			</div>
		</div>
	{else}
		<br />
		<fieldset>
			<legend><img src="../img/admin/tab-shipping.gif" /> {l s='Bukoli delivery info' mod='bukolidelivery'}</legend>
			{$bukoli_details->details|escape:'htmlall':'UTF-8'}
		</fieldset>
	{/if}
{/if}
