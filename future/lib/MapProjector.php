<?php

class MapProjector {

    private $srcProj = 4326;
    private $dstProj = 4326;
    
    private $srcProjSpec;
    private $dstProjSpec;
    
    private $baseURL; // ESRI geometry service, if any
    
    public function __construct($baseURL=NULL) {
        if (!$baseURL && !function_exists('project_from_latlon')) {
            die('No projection support found.');
        }

        $this->baseURL = $baseURL;
    }
    
    public function projectPoint($point) {
        $x = isset($point['lon']) ? $point['lon'] : $point['x'];
        $y = isset($point['lat']) ? $point['lat'] : $point['y'];
    
        if ($this->srcProj == $this->dstProj) {
            return array('x' => $x, 'y' => $y);
        }
    
        if ($this->baseURL !== NULL) {
            $params = array(
                'inSR' => $this->srcProj,
                'outSR' => $this->dstProj,
                'geometries' => '{"geometryType":"esriGeometryPoint","geometries":[{"x":'.$x.',"y":'.$y.'}]}',
                'f' => 'json',
                );
            $query = ESRI_PROJECTION_SERVER.'?'.http_build_query($params);
            $response = file_get_contents($query);
            $json = json_decode($response, true);
            if ($json && isset($json['geometries']) && is_array($json['geometries'])) {
                $geometry = $json['geometries'][0];
                return array('x' => $geometry['x'], 'y' => $geometry['y']);
            }
        }
        else {
            if ($this->srcProj != 4326) {
                $latlon = project_to_latlon($this->srcProjSpec, $x, $y);
                $x = $latlon[1]; // lon
                $y = $latlon[0]; // lat
            }
            if ($this->dstProj != 4326) {
                $xy = project_from_latlon($this->dstProjSpec, $y, $x); // they will have passed in lat, lon
                $x = $xy[0]; // point x
                $y = $xy[1]; // point y
            }
            return array('x' => $x, 'y' => $y);
        }
    }
    
    public function getSrcProj() {
        return $this->srcProj;
    }
    
    public function getDstProj() {
        return $this->dstProj;
    }
    
    public function setSrcProj($proj) {
        if ($proj != $this->srcProj) {
            if ($this->baseURL === NULL) {
                $proj = $this->convertWkid($proj); // too bad this invalidates the check two lines above
                
                $projspecs = $this->getProjSpecs($proj);
                if ($projspecs) {
                    $this->srcProjSpec = trim($projspecs);
                    $this->srcProj = $proj;
                }
            }
            else {
                $this->srcProj = $proj;
            }
        }
    }
    
    public function setDstProj($proj) {
        if ($proj != $this->dstProj) {
            if ($this->baseURL === NULL) {
                $proj = $this->convertWkid($proj);
            
                $projspecs = $this->getProjSpecs($proj);
                if ($projspecs) {
                    $this->dstProjSpec = trim($projspecs);
                    $this->dstProj = $proj;
                }
            }
            else {
                $this->dstProj = $proj;
            }
        }
    }
    
    private function getProjSpecs($wkid) {
    
        $projCache = new DiskCache($GLOBALS['siteConfig']->getVar('PROJ_CACHE'), null, true);
        $projCache->setSuffix('proj4');
        $projCache->preserveFormat();
        $filename = $wkid;
        if (!$projCache->isFresh($filename)) {
            $url = 'http://spatialreference.org/ref/epsg/'.$wkid.'/proj4/';
            $contents = file_get_contents($url);
            if ($contents)
                $projCache->write($contents, $filename);
        } else {
            $contents = $projCache->read($filename);
        }
        
        return $contents;
    }
    
    private function convertWkid($wkid) {
        
        // hack to convert ESRI-specific web mercator code
        // to standard code with the same spec.
        if ($wkid == 102113) $wkid = 3785;
        
        return $wkid;
    }

}

