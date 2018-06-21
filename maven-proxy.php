<?php
define('MAVEN_PROXY_VERSION', '1.0');
class MavenProxy {
    
    var $dbLocation;
    var $db;
    
    function __construct() {
        $this->dbLocation = sys_get_temp_dir().'/maven-proxy-checksums-'.MAVEN_PROXY_VERSION.'.db';
    }
    
    function db() {
        if (!isset($this->db)) {
            $needsInit = false;
            //unlink($this->dbLocation);
            if (!file_exists($this->dbLocation)) {
                $needsInit = true;
            }
            $this->db = new SQLite3($this->dbLocation);
            if ($needsInit){
                $this->db->exec('CREATE TABLE files (groupId TEXT, artifactId TEXT, version TEXT, resource_type TEXT, sha_checksum TEXT, etag TEXT, PRIMARY KEY(groupId, artifactId, version, resource_type))');
            }
            $dbVersion = $this->db->query('PRAGMA user_version')->fetchArray()[0];
			if ($dbVersion < 1) {
				$this->db->exec('ALTER TABLE files ADD last_updated INTEGER');
				$this->db->exec('PRAGMA user_version=1');
			}
            
            
        }
        return $this->db;
    }
    
    function error_not_found_404() {
        header('HTTP/1.1 404 File not found');
        echo "<!doctype html><html><head><title>File not found</title></head><body><h1>File not found</h1></body></html>";
        exit;
    }
    

    
    function get_remote_url($groupId, $artifactId, $version, $resourceType) {
        $pomUrl = null;
        $branch = null;
        if (preg_match('/-SNAPSHOT$/', $version)) {
            $branch = substr($version, 0, strrpos($version, '-'));
        }
        $subdir = '';
        $groupParts = explode('.', $groupId);
        //print_r($groupParts);
        $githubUser = $groupParts[2];
        
        if (count($groupParts) == 4) {
            $subdir = rawurlencode($artifactId).'/';
            $githubRepo = $groupParts[3];
        } else {
            $githubRepo = $artifactId;
        }
        
        
        
        if (isset($branch)) {
            $pomUrl = 'https://github.com/'.rawurlencode($githubUser).'/'.rawurlencode($githubRepo).'/raw/'.rawurlencode($branch).'/'.$subdir.'pom.xml';
        } else {
            $pomUrl = 'https://github.com/'.rawurlencode($githubUser).'/'.rawurlencode($githubRepo).'/raw/'.rawurlencode($version).'/'.$subdir.'pom.xml';
        }
        
        if ($resourceType == 'pom') {
            return $pomUrl;
        }
        if ($resourceType == 'jar') {
            $pomContents = file_get_contents($pomUrl);
            $doc = new DOMDocument();
            $doc->loadXML($pomContents);
            $xpath = new DOMXPath($doc);
            $ns = $doc->documentElement->namespaceURI;
            $xpath->registerNamespace("ns", $ns);
            $jarPaths = $xpath->query('//ns:properties/ns:jar.path');
            if ($jarPaths->length == 0) {
                return null;
            }
            $jarPath = $jarPaths->item(0)->nodeValue;
            //echo "Jar path $jarPath";
            if (isset($branch)) {
                $jarUrl = 'https://github.com/'.rawurlencode($githubUser).'/'.rawurlencode($githubRepo).'/raw/'.rawurlencode($branch).'/'.$subdir.$jarPath;
            } else {
                $jarUrl = 'https://github.com/'.rawurlencode($githubUser).'/'.rawurlencode($githubRepo).'/raw/'.rawurlencode($version).'/'.$subdir.$jarPath;
            }
            return $jarUrl;
            
        }
        return null;
    }
    
    function download_file($url, $filepath) {
        $url = "https://example.com/file-to-download.zip" ;
        
        $ch = curl_init($url);
        

        $complete_save_loc = $filepath;
        
        $fp = fopen($complete_save_loc, 'wb');
        
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }
    
    function sha1_url($url) {
        $filepath = tempnam(sys_get_temp_dir(), 'sha1tmp');
        $this->download_file($url, $filepath);
        $sha1 = sha1_file($filepath);
        unlink($filepath);
        return $sha1;
    }
    
    function get_cooked_pom($groupId, $artifactId, $version) {
        $pomUrl = $this->get_remote_url($groupId, $artifactId, $version, 'pom');
        
        $contents = @file_get_contents($pomUrl);
        if (!$contents) {
            return null;
        }
        
        
        
        $xml = new DOMDocument();
        $xml->loadXML($contents);
        $xpath = new DOMXPath($xml);
        $ns = $xml->documentElement->namespaceURI;
        $xpath->registerNamespace("ns", $ns);
        $xpath->query('/ns:project/ns:groupId')->item(0)->nodeValue = $groupId;
        $xpath->query('/ns:project/ns:artifactId')->item(0)->nodeValue = $artifactId;
        $xpath->query('/ns:project/ns:version')->item(0)->nodeValue = $version;
        
        header('Content-type: application/xml; charset="UTF-8"');
        return $xml->saveXML();
    }
    
    
    function get_last_updated($groupId, $artifactId, $version, $resource_type) {
    	$sha = $this->get_sha1($groupId, $artifactId, $version, $resource_type);

    	$statement = $this->db()->prepare('SELECT last_updated from files where groupId=:groupId and artifactId=:artifactId and version=:version and resource_type=:resource_type');
    	$statement->bindParam(':groupId', $groupId);
    	$statement->bindParam(':artifactId', $artifactId);
    	$statement->bindParam(':version', $version);
    	$statement->bindParam(':resource_type', $resource_type);
    	$result = $statement->execute();
		$mtime = null;
    	if ($row = $result->fetchArray()) {
    		$mtime = $row[0];
    	}
    	$updateStmt = $this->db()->prepare('UPDATE files set last_updated=:last_updated where groupId=:groupId and artifactId=:artifactId and version=:version and resource_type=:resource_type');
    	$updateStmt->bindParam(':groupId', $groupId);
    	$updateStmt->bindParam(':artifactId', $artifactId);
    	$updateStmt->bindParam(':version', $version);
    	$updateStmt->bindParam(':resource_type', $resource_type);
    	$updateStmt->bindParam(':last_updated', time());
    	if (!$mtime) {
    		$updateStmt->execute();
    		$mtime = time();
    	}
    	return $mtime;
    	
    }
    
    function get_sha1($groupId, $artifactId, $version, $resourceType) {
        $statement = $this->db()->prepare('SELECT sha_checksum, etag FROM files where groupId=:groupId and artifactId=:artifactId and version=:version and resource_type=:resource_type');
        $statement->bindParam(':groupId', $groupId);
        $statement->bindParam(':artifactId', $artifactId);
        $statement->bindParam(':version', $version);
        $statement->bindParam(':resource_type', $resourceType);
        $result = $statement->execute();
        $sha = null;
        $etag = null;
        while ($row = $result->fetchArray()) {
            $sha = $row['sha_checksum'];
            $etag = $row['etag'];
        }
        
        if (isset($sha) and !preg_match('/-SNAPSHOT$/', $version)) {
            // we already have the sha1 and it shouldn't change
            return $sha;
        }
        
        $url = $this->get_remote_url($groupId, $artifactId, $version, $resourceType);
        if (!isset($url)) {
            return isset($sha) ? $sha : null;
        }
        
        // We already have a previous sha and etag so 
        // let's just make a head request to get the etag to 
        // see if the file has changed so we can avoid downloading it
        // if possible.
        $options = array('http'=>array('method'=>'HEAD', 'follow_location' => 1));
        $res = file_get_contents($url, null, stream_context_create($options));
        $newEtag = null;
        if (!empty($http_response_header)) {
            //print_r($http_response_header);
            foreach ($http_response_header as $header) {
                if (preg_match('/^etag:/i', $header)) {
                    $newEtag = trim(substr($header, strpos($header, ':')+1));
                    $newEtag = preg_replace('/[^0-f]/', '', $newEtag);
                    break;
                }
            }
            
        }
        //echo "Etag $newEtag";
        if (isset($newEtag) and $newEtag == $etag) {
            return $sha;
        }
        error_log("[MavenProxy] Cache miss on $groupId:$artifactId:$version:$resourceType");
        if ($resourceType == 'pom') {
            $sha = sha1($this->get_cooked_pom($groupId, $artifactId, $version));
        } else {
            $sha = sha1_file($url);
        }
        //$shat = sha1(file_get_contents($url, null, stream_context_create($options)));
        //$sha = $this->sha1_url($url);
        //echo "SHA=".$sha."\nSHA0=".$sha0."\nSHAT=".$shat;
        if (!$sha) {
            return null;
        }
        if (isset($newEtag)) {
            if (isset($etag)) {
                $statement = $this->db()->prepare("UPDATE files set etag=:etag, sha_checksum=:sha_checksum, last_updated=:last_updated where groupId=:groupId and artifactId=:artifactId and version=:version and resource_type=:resource_type");    
            } else {
                $statement = $this->db()->prepare("INSERT INTO files (groupId, artifactId, version, resource_type, sha_checksum, etag, last_updated) values (:groupId, :artifactId, :version, :resource_type, :sha_checksum, :etag, :last_updated)");
            }
            $statement->bindParam(':etag', $newEtag);
            $statement->bindParam(':sha_checksum', $sha);
            $statement->bindParam(':groupId', $groupId);
            $statement->bindParam(':artifactId', $artifactId);
            $statement->bindParam(':version', $version);
            $statement->bindParam(':resource_type', $resourceType);
            $statement->bindParam(':last_updated', time());
            $statement->execute();
        }
        return $sha;
        
    }
    
    function handleRequest() {
        $requestUri = $_SERVER['REQUEST_URI'];
        if (strpos($requestUri, '/com/github/') === false) {
            $this->error_not_found_404();
        }
        
        if (!preg_match('/\.(jar|pom|sha1|xml)$/', $requestUri)) {
            $this->error_not_found_404();
        }
        
        $parts = explode('/com/github/', $requestUri);
        $parts = explode('/', $parts[1]);
        if (count($parts) < 4 or count($parts) > 5) {
            $this->error_not_found_404();
        }
        
        $groupId = 'com.github.'.$parts[0];
        $githubUser = $parts[0];
        $githubRepo = $parts[1];
        
        $artifactId = null;
        $version = null;
        $file = null;
        $pomUrl = null;
        $branch = null;
        $tag = null;
        $pomUrl = null;
        $subDir = '';
        if (count($parts) == 5) {
            $groupId .= '.'.$parts[1];
            $artifactId = $parts[2];
            $version = $parts[3];
            $file = $parts[4];
            $subdir = rawurlencode($artifactId).'/';
        } else {
            $artifactId = $parts[1];
            $version = $parts[2];
            $file = $parts[3];
            
        }
        if (preg_match('/-SNAPSHOT-\d{8}\.\d+$/', $version)) {
        	$version = preg_replace('/-\d{8}\.\d+$/', '', $version);
        }
        //echo $version;exit;
        
        if (preg_match('/-SNAPSHOT$/', $version)) {
            // This is a branch
            $branch = preg_replace('/-SNAPSHOT$/', '', $version);
        } else {
            $tag = $version;
        }
        
        $resourceType = null;
        $isSha1Request = false;
        if (preg_match('/\.(jar|pom)(\.sha1)?$/', $requestUri, $matches)) {
            //print_r($matches);
            $resourceType = $matches[1];
            $isSha1Request = !empty($matches[2]);
        }
        
        if ($isSha1Request) {
            $sha = $this->get_sha1($groupId, $artifactId, $version, $resourceType);
            if (!isset($sha)) {
                $this->error_not_found_404();
            }
            header('Content-type: text/plain');
            echo $sha;
            exit;
        }
        
        if ($resourceType == 'pom') {
            $pomContents = $this->get_cooked_pom($groupId, $artifactId, $version);
            if (!isset($pomContents)) {
                $this->error_not_found_404();
            }
            
            header('Content-type: application/xml; charset="UTF-8"');
            echo $pomContents;
            
            exit;
            
        }
            
        if ($resourceType == 'jar') {
            $jarUrl = $this->get_remote_url($groupId, $artifactId, $version, $resourceType);
            //echo "Jar $jarUrl";
            if (!isset($jarUrl)) {
                $this->error_not_found_404();
                
            }
            
            header('Location: '.$jarUrl);
            exit;
        }
        if (preg_match('/maven-metadata.xml(.sha1)?$/', $requestUri, $matches)) {
        	$snapshotsStr = '';
        	if (preg_match('/-SNAPSHOT$/', $version)) {
        		$jarMTime = $this->get_last_updated($groupId, $artifactId, $version, 'jar');
        		$pomMTime = $this->get_last_updated($groupId, $artifactId, $version, 'pom');
        		$snapshotsStr = "<versioning>\n"
        		    . "    <snapshotVersions>\n"
        			. "      <snapshotVersion>\n"
        			. "        <extension>jar</extension>\n"
        			. "        <value>".htmlentities($version.'-'.gmdate('Ymd', $jarMTime).'.'.$jarMTime)."</value>\n"
        			. "        <updated>".gmdate('YmdHis', $jarMTime)."</updated>\n"
        			. "      </snapshotVersion>\n"
        			. "      <snapshotVersion>\n"
        			. "        <extension>pom</extension>\n"
        			. "        <value>".htmlentities($version.'-'.gmdate('Ymd', $pomMTime).'.'.$pomMTime)."</value>\n"
        			. "        <updated>".gmdate('YmdHis', $pomMTime)."</updated>\n"
        			. "      </snapshotVersion>\n"
        			. "    </snapshotVersions>\n"
        			. "</versioning>";
        			
        		//$snapshotsStr = "<versioning><snapshot><timestamp>".gmdate('YmdHis', $jarMTime)."</timestamp></snapshot></versioning>";
        		
        	}
        
            $metadataStr = <<<END
<metadata>
<groupId>$groupId</groupId>
<artifactId>$artifactId</artifactId>
<version>$version</version>
$snapshotsStr
</metadata>
END;
            if (@$matches[1]) {
                header('Content-type: text/plain');
                echo sha1($metadataStr);
                exit;
            }
            header('Content-type: application/xml');
            echo $metadataStr;
            exit;
        }
        
        $this->error_not_found_404();
    }   
}
$proxy = new MavenProxy();
$proxy->handleRequest();
    
