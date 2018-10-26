
{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" title="{l s='Go back to the Checkout' mod='opennode'}">
        {l s='Checkout' mod='opennode'}
    </a>
    <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>
    {l s='opennode payment' mod='opennode'}
{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='opennode'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='opennode'}
    </p>
{else}
    <form action="{$link->getModuleLink('opennode', 'redirect', [], true)|escape:'html':'UTF-8'}" method="post">
        <div class="box cheque-box">
            <h3 class="page-subheading">
                {l s='OpenNode payment' mod='opennode'}
            </h3>

            <p class="cheque-indent">
                <strong class="dark">
                    {l s='You have chosen to pay with Bitcoin via OpenNode.' mod='opennode'} {l s='Here is a short summary of your order:' mod='opennode'}
                </strong>
            </p>

            <p>
                - {l s='The total amount of your order is' mod='opennode'}
                <span id="amount" class="price">{displayPrice price=$total}</span>
                {if $use_taxes == 1}
                    {l s='(tax incl.)' mod='opennode'}
                {/if}
            </p>

            <p>
                - {l s='You will be redirected to OpenNode for payment with Bitcoin.' mod='opennode'}
                <br/>
                - {l s='Please confirm your order by clicking "I confirm my order".' mod='opennode'}
            </p>
        </div>
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='opennode'}
            </a>
            <button class="button btn btn-default button-medium" type="submit">
                <span>{l s='I confirm my order' mod='opennode'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    </form>
{/if}