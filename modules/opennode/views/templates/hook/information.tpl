<div class="tab">
  <button class="tablinks" onclick="changeTab(event, 'Information')" id="defaultOpen">{l s='Information' mod='opennode'}</button>
  <button class="tablinks" onclick="changeTab(event, 'Configure Settings')">{l s='Configure Settings' mod='opennode'}</button>
</div>

<!-- Tab content -->
<div id="Information" class="tabcontent">
	<div class="wrapper">
	  <img src="../modules/opennode/views/img/intro.png" style="float:right;"/>
	  <h2 class="opennode-information-header">
      {l s='Accept bitcoin on your PrestaShop store with OpenNode' mod='opennode'}
    </h2><br/>
	  <strong>{l s='What is opennode?' mod='opennode'}</strong> <br/>
	  <p>
      {l s='OpenNode is the world’s first multi-layered bitcoin payment processor for merchants. Easily accepting bitcoin with instantly confirmed transactions and 0 fees has not been possible... until now.
      Accept Bitcoin and get paid in your native currency directly to your bank account.' mod='opennode'}
    </p><br/>
	  <strong>{l s='Getting started' mod='opennode'}</strong><br/>
	  <p>
	  	<ul>
	  		<li>{l s='Install the OpenNode module on PrestaShop' mod='opennode'}</li>
	  		<li>
          {l s='Visit ' mod='opennode'}<a href="https://opennode.co" target="_blank">{l s='opennode.co' mod='opennode'}</a>
          {l s='and create an account' mod='opennode'}
         </li>
	  		<li>{l s='Generate your API Credentials and copy-paste them to the OpenNode module settings' mod='opennode'}</li>
	  		<li>{l s='Read our ' mod='opennode'}
          <a href="https://medium.com/@OpenNode/how-to-accept-bitcoin-in-your-prestashop-store-de6ffeb1896b" target="_blank">
            {l s='PrestaShop detailed installation guide.' mod='opennode'}
          </a>
	  	</ul>
	  </p>
	  <p><i>{l s='Got questions? Contact support@opennode.co!' mod='opennode'}</i></p>
	</div>
</div>

<div id="Configure Settings" class="tabcontent">
  {html_entity_decode($form|escape:'htmlall':'UTF-8')}
</div>

<script>
	document.getElementById("defaultOpen").click();
</script>
