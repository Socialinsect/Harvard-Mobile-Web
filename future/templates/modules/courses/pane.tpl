{if count($myClasses)}
  {include file="findInclude:common/results.tpl" results=$myClasses accessKey=false}
{else}
  <div class="nonfocal">
    You haven't selected any courses yet.  Please choose your current courses to receive the latest updates and information.
  </div>
{/if}
