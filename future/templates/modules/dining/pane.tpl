{if count($foodTypes)}
  <ul class="results">
    {foreach $foodTypes as $foodType => $foods}
      <li>
        {$foodType}: 
        <span class="smallprint">
          {foreach $foods as $food}
            {$food['item']}{if !$food@last}, {/if}
          {/foreach}
        </span>
      </li>
    {/foreach}
  </ul>
{/if}
