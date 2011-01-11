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
        //if ($startIndex === 0 && is_null($limit)) {
        //    return $data;
        //}

        if ($limit>0) {
            $endIndex = $startIndex + $limit;
        } else {
            $endIndex = PHP_INT_MAX;
        }
        
        $dom = new DomDocument();
        $dom->loadXML($this->getData());
        $items = $dom->getElementsByTagName('item');
        $totalCount = $items->length;
        $nodesToRemove = array();
        
        for ($i = 0; $i < $items->length; $i++) {
            $item = $items->item($i);
            if ( ($i < $startIndex) || ($i >= $endIndex)) {
                $nodesToRemove[] = $item;
            } else {
                // translate enclosures to image tags for API compatibility
                $enclosures = $item->getElementsByTagName('enclosure');
                foreach ($enclosures as $enclosure) {
                    $type = $enclosure->getAttributeNode('type')->value;
                    if (strpos($type, 'image/') === FALSE) { continue; }
                    
                    $url = $enclosure->getAttributeNode('url')->value;
                    $width = 0;
                    $height = 0;
                    
                    $url = GazetteRSSEnclosure::getImageLoaderURL($url, $width, $height);
                    
                    /* only send real images */
                    if ($width > 1 && $height > 1) {
                        $enclosure->setAttributeNode(new DOMAttr('url', $url));
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
        
        // iPhone expects this items attr to be set on the channel
        $channels = $dom->getElementsByTagName('channel');
        $channels->item(0)->setAttributeNode(new DOMAttr('items', $totalCount));
        
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
    protected $width = null;
    protected $height = null;
    
    public function __construct($attribs) {
        parent::__construct($attribs);
      
        if ($this->isImage()) {
          $this->url = self::getImageLoaderURL($this->url, $this->width, $this->height);
        }
    }
    
    protected function standardAttributes()
    {
        $attributes = array_merge(parent::standardAttributes(),array(
            'width',
            'height'));
        return $attributes;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getWidth()
    {
        return $this->height;
    }

    public static function getImageLoaderURL($url, &$width, &$height) {
        if ($url && strpos($url, '/photo-placeholder.gif') !== FALSE) {
            $url = ''; // skip empty placeholder image 
        }
        
        if ($url) {
            switch ($GLOBALS['deviceClassifier']->getPagetype()) {
                case 'compliant':
                    $width = 140;
                    $height = 140;
                    break;
                
                case 'basic':
                case 'touch':
                default:
                    $width = 70;
                    $height = 70;
                    break;
            }
          
            $extension = pathinfo($url, PATHINFO_EXTENSION);
            if ($extension) { $extension = ".$extension"; }
  
            $url = ImageLoader::precache($url, $width, $height, 'Gazette_'.md5($url).$extension);
        }
        
        return $url;
    }
}

