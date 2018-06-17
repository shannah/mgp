<?php
define('PAGE_URL', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$mgpURL = PAGE_URL;
if (preg_match('/\/index.php.*$/', $mgpURL)) {
	$mgpURL = substr($mgpURL, 0, strpos($mgpURL, '/index.php'));
} else if ($mgpURL{strlen($mgpURL)-1} == '/') {
	$mgpURL = substr($mgpURL, 0, strlen($mgpURL)-1);
}
?><!doctype html>
<html>
	<head>
		<title>MGP: Maven (Github) Proxy</title>
		<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
		<script src="js/index.js"></script>
	</head>
	<body>
		<h1>MGP: Maven (Github) Proxy</h1>
		
		<p>Host Jars on GitHub and add them as dependencies in Maven and Gradle projects.</p>
		
		<p>
			<label>GitHub Repo URL</label>
			<input type="text" id="repo-url" value="https://github.com/shannah/php4j"/>
		</p>
		
		<p>
			<label>Artifact ID</label>
			<input type="text" id="artifact-id" value="php4j-thin"/>
		</p>
		
		<p>
			<label>Version</label>
			<input type="text" id="version" value="master-SNAPSHOT"/>
		</p>
		
		<p><button id="search">Search</button></p>
		<div id="response">
		
		</div>
		
		<div id="templates" style="display:none">
			<div class="template not-found">
				<h3>Artifact was not found</h3>
			</div>
		
			<div class="template found">
				<h2>Artifact <span class="artifact-id">artifact</span> was found</h2>
			
				<p>To get this artifact into your build:</p>
			
				<p><strong>Step 1.</strong> Add the MGP Repository to your build file</p>
			
				<div class="snippet">
					<div class="snippet maven">
						<code><pre><?php echo htmlentities(<<<END
<repositories>
	<repository>
		<id>mgp</id>
		<url>$mgpURL</url>
	</repository>
</repositories>
END
);
						?></pre></code>
					</div>
					<div class="snippet gradle">
						<p>Add it in your root build.gradle at the end of repositories:</p>
						<code><pre><?php echo htmlentities(<<<END
allprojects {
	repositories {
		...
		maven { url '$mgpURL' }
	}
}
END
);
						?></pre></code>
					</div>
				
					<div class="snippet sbt">
						<p>Add it in your build.sbt at the end of resolvers:</p>
						<code><pre><?php echo htmlentities(<<<END
resolvers += "mgp" at "$mgpURL"
END
);
						?></pre></code>
					</div>
				</div>
			
				<p><strong>Step 2.</strong> Add the dependency</p>
			
				<div class="snippet">
					<div class="snippet maven">
						<code><pre><?php echo htmlentities(<<<END
<dependency>
	    <groupId>com.github.User</groupId>
	    <artifactId>Repo</artifactId>
	    <version>Tag</version>
	</dependency>
END
);
						?></pre></code>
					</div>
					<div class="snippet gradle">
						<code><pre><?php echo htmlentities(<<<END
dependencies {
		implementation 'com.github.User:Repo:Tag'
	}
END
);
						?></pre></code>
					</div>
				
					<div class="snippet sbt">
						<code><pre><?php echo htmlentities(<<<END
libraryDependencies += "com.github.User" % "Repo" % "Tag"
END
);
						?></pre></code>
					</div>
				</div>
			</div>
		</div>
		
	</body>
</html>
