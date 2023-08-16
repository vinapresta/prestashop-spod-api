<div id="spodApiList" class="bootstrap">
    <div class="panel">
        <div class="panel-heading">Products List</div>
        {if $products}
        <ul>
        {foreach from=$products item=product}
            {*{$product|var_dump}*}
            <li>
                <span>{$product.id}</span>
                <span>{$product.title}</span>
                <p>{$product.description}</p>
                <ul class="categories-list">
                {foreach from=$categories item=category}
                    <li class="categories-list__item">
                        <button class="btn categories-list__btn" aria-pressed="false">
                            {$category.name}
                        </button>
                    </li>
                {/foreach}
                </ul>
            </li>
        {/foreach}
        </ul>
        <div class="panel-footer">
            <button id="spodApiListBtn" class="spodApiList__btn btn btn-default pull-right">
                {l s='Create the products csv file' d='Modules.SpodApi.Admin'}
            </button>
        </div>
        {else}
        <div>{l s='There is no product' d='Modules.SpodApi.Admin'}</div>
        {/if}     
    </div>
</div>
