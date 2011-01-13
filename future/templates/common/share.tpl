<div class="share">
Share article:
    
    <ul>
        <li>
            {block name="shareImage"}
              <a href="{$shareEmailUrl}"><img src="/common/images/share.png"/></a>Email
            {/block}
        </li>
        <li>
            <script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>
            <fb:like href="{$urlToBeShared}" layout="button_count" show_faces="false" width="100" action="recommend"></fb:like>
        </li>
        <li>
            <a href="http://twitter.com/share" 
            class="twitter-share-button" 
            data-text="{$shareRemark}"
            data-url="http://bit.ly/twitter-api-announce" data-count="none" data-via="harvard">Tweet</a>
            
            <script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
        </li>
    </ul>
</div>

<br /><br />
