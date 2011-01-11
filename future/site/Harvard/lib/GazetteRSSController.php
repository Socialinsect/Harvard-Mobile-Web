<?php

require_once(LIB_DIR . '/RSS.php');

class GazetteRSScontroller extends RSSDataController
{
    protected $loadMore=true;
    
    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'search':
                $this->addFilter('s',$value);
                $this->addFilter('feed', 'rss2');
                $this->loadMore = false;
                break;
            default:
                return parent::addFilter($var, $value);
        }
    }
    
    public function getItem($id, $page=1)
    {
        $maxPages = $GLOBALS['siteConfig']->getVar('GAZETTE_NEWS_MAX_PAGES');; // to prevent runaway trains
        
        while ($page < $maxPages) {
            $items = $this->loadPage($page++);
            foreach ($items as $item) {
                if ($item->getGUID()==$id) {
                    return $item;
                }
            }
        }            
        
        return null;
    }
    
    public function items(&$start=0,$limit=null, &$totalItems=0) 
    {
        if ($limit && $start % $limit != 0) {
            $start = floor($start/$limit)*$limit;
        }
        
        $items = parent::items(0,null,$totalItems); //get all the items
        $maxPages = $GLOBALS['siteConfig']->getVar('GAZETTE_NEWS_MAX_PAGES');; // to prevent runaway trains
        
        if ($this->loadMore) {
            $page = 1;

            /* load new pages until we have enough content */
            while ( ($start > $totalItems) && ($page < $maxPages)) {
                $moreItems = $this->loadPage(++$page);
                $items = array_merge(array_values($items), array_values($moreItems));
                $totalItems += count($moreItems);
            }
            
            if ($limit) {
                $items = array_slice($items, $start, $limit); //slice off what's not needed
                
                // see if we need to fill it out at the end
                if (count($items)<$limit) {
                    $moreItems = $this->loadPage(++$page);
                    $items = array_merge($items, array_slice($moreItems,0,$limit-count($items)));
                    $totalItems += count($moreItems);
                }
            }
        } elseif ($limit) {
            $items = array_slice($items, $start, $limit); //slice off what's not needed
        }

        return $items;
    }
    
    private function loadPage($page)
    {
        $this->addFilter('paged',$page);
        $items = $this->items();
        return $items;   
    }

    public function getRSSItems($startIndex=0, $limit=null)
    {
        $data = $this->getData();
        if ($startIndex === 0 && is_null($limit)) {
            return $data;
        }

        if ($limit>0) {
            $endIndex = $startIndex + $limit;
        } else {
            $endIndex = PHP_INT_MAX;
        }
        
        $dom = new DomDocument();
        $dom->loadXML($this->getData());
        $items = $dom->getElementsByTagName('item');
        $nodesToRemove = array();

        for ($i=0; $i<$items->length; $i++) {
            $item = $items->item($i);
            if ( ($i < $startIndex) || ($i >= $endIndex)) {
                $nodesToRemove[] = $item;
            } else {
                // translate enclosures to image tags for API compatibility
                $enclosures = $item->getElementsByTagName('enclosure');
                foreach ($enclosures as $enclosure) {
                
                    /* lets see what the dimensions of the image are. */
                    $url = $enclosure->getAttributeNode('url')->value;         
                    $extension = pathinfo($url, PATHINFO_EXTENSION);
                    $extension = $extension ? '.' . $extension : '';
                    $image_file = CACHE_DIR . "/GazetteImages/" . md5($url) .  $extension;
 
                    /* the image has not been downloaded */
                    if (!file_exists($image_file)) {
                        $data = file_get_contents($url);
                        file_get_contents($image_file,  file_get_contents($url));                       
                    }
                    
                    /* get the image size */
                    if ($image_size = getimagesize($image_file)) {
                        $width = intval($image_size[0]);
                        $height = intval($image_size[1]);
                    } else {
                        $width = 0;
                        $height = 0;
                    }

                    /* only send real images */
                    if ($width > 1 && $height > 1) {
                        $image = $dom->createElement('image');
                        $image_url = $dom->createElement('url', $url);
                        $image->appendChild($image_url);
                        $item->appendChild($image);
                    }
                }
            }
        }
        
        foreach ($nodesToRemove as $item) {
            $item->parentNode->removeChild($item);
        }
        
        return $dom->saveXML();
    }

    public function getIndexForItem($id)
    {
        if (!$id) {
            return null;
        }
        
        $items = $this->items();
        
        for ($i=0; $i < count($items); $i++) {
            $item = $items[$i];
            if ($item->getGUID()==$id || $item->getProperty('HARVARD:WPID')==$id) {
                return $i;
            }
        }
                
        return null;
    }
}

class GazetteRSSItem extends RSSItem
{
    public function addElement(RSSElement $element)
    {
        $name = $element->name();
        $value = $element->value();
        
        switch ($name)
        {
            case 'enclosure':
                if ($element->isImage()) {
                    if ($element->getProperty('width')>1) {
                        $this->enclosure = $element;
                    }
                }
                break;
            default:
                parent::addElement($element);
                break;
        }
        
    }
}

class GazetteRSSEnclosure extends RSSEnclosure
{
    protected $width;
    protected $height;
    
    protected function standardAttributes()
    {
        $attributes = array_merge(parent::standardAttributes(),array(
            'width',
            'height'));
        return $attributes;
    }

    public function getHeight()
    {
        if ($this->cacheImage()) {
            return $this->height;
        }
        
        return null;
    }

    public function getWidth()
    {
        if ($this->cacheImage()) {
            return $this->height;
        }
        
        return null;
    }
    
    private function cacheFilename()
    {
        return md5($this->url);
    }

    protected function cacheFolder()
    {
        return CACHE_DIR . "/GazetteImages";
    }
    
    protected function cacheLifespan()
    {
        return $GLOBALS['siteConfig']->getVar('GAZETTE_NEWS_IMAGE_CACHE_LIFESPAN');
    }

    protected function cacheFileSuffix()
    {
        $extension = pathinfo($this->url, PATHINFO_EXTENSION);
        return $extension ? '.' . $extension : '';
    }

    private function cacheImage()
    {
        if (!$this->url) {
            return;
        }
        
        $cacheFilename = $this->cacheFilename();
        $cache = new DiskCache($this->cacheFolder(), $this->cacheLifespan(), TRUE);
        $cache->setSuffix($this->cacheFileSuffix());
        $cache->preserveFormat();
        
        if (!$cache->isFresh($cacheFilename)) {
            if ($data = file_get_contents($this->url)) {
                $cache->write($data, $cacheFilename);
            }
        }

        if ($image_size = getimagesize($cache->getFullPath($cacheFilename))) {
            $this->width = intval($image_size[0]);
            $this->height = intval($image_size[1]);
            return true;
        }
    }
}

