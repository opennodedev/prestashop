{*
* NOTICE OF LICENSE
The MIT License (MIT)

Copyright (c) 2019 OpenNode https://opennode.com

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*
*  @author    OpenNode <hello@opennode.com>
*  @copyright 2019 OpenNode
*  @license   https://github.com/opennodedev/prestashop/blob/master/LICENSE  The MIT License (MIT)
*}
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
          {l s='Visit ' mod='opennode'}<a href="https://opennode.com" target="_blank">{l s='opennode.com' mod='opennode'}</a>
          {l s='and create an account' mod='opennode'}
         </li>
	  		<li>{l s='Generate your API Credentials and copy-paste them to the OpenNode module settings' mod='opennode'}</li>
	  		<li>{l s='Read our ' mod='opennode'}
          <a href="https://medium.com/@OpenNode/how-to-accept-bitcoin-in-your-prestashop-store-de6ffeb1896b" target="_blank">
            {l s='PrestaShop detailed installation guide.' mod='opennode'}
          </a>
	  	</ul>
	  </p>
	  <p><i>{l s='Got questions? Contact support@opennode.com!' mod='opennode'}</i></p>
	</div>
</div>

<div id="Configure Settings" class="tabcontent">
  {html_entity_decode($form|escape:'htmlall':'UTF-8')}
</div>

<script>
	document.getElementById("defaultOpen").click();
</script>
